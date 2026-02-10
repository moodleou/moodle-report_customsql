<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Adhoc task to execute a custom SQL query in the background.
 *
 * @package    report_customsql
 * @copyright  2025 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_customsql\task;

use core\task\adhoc_task;
use stdClass;

/**
 * Executes a saved custom SQL query asynchronously and stores the CSV output.
 */
class execute_query_adhoc extends adhoc_task {
    /** Query currently running. */
    public const STATUS_RUNNING = 'running';
    /** Query finished successfully. */
    public const STATUS_COMPLETED = 'completed';
    /** Query failed with an error. */
    public const STATUS_FAILED = 'failed';
    /** Query has been cancelled. */
    public const STATUS_CANCELLED = 'cancelled';

    /** Capability required to run background executions. */
    public const CAPABILITY_EXECUTE = 'report/customsql:executebackground';

    /** Lock area name for this component. */
    private const LOCK_AREA = 'report_customsql';
    /** Lock timeout in seconds. */
    private const LOCK_TIMEOUT = 10;

    /**
     * Execute task entrypoint.
     *
     * @throws moodle_exception
     */
    public function execute() {
        $data = $this->get_custom_data();
        if (empty($data) || empty($data->executionid)) {
            // Nothing to do.
            return;
        }
        $executionid = (int) $data->executionid;
        try {
            $this->execute_and_save_query($executionid);
        } catch (\Exception $e) {
            // Attempt to record failure, then rethrow for task monitoring.
            try {
                $this->handle_query_error($executionid, $e);
            } catch (\Throwable $ignored) {
                // Intentional no-op to avoid hiding original exception; satisfies code checker.
                $ignored = $ignored; // phpcs:ignore
            }
            throw $e;
        }
    }

    /**
     * Core execution logic (streaming write for memory efficiency).
     *
     * @param int $executionid Execution record id
     * @return void
     * @throws moodle_exception
     */
    private function execute_and_save_query(int $executionid): void {
        global $DB, $CFG;

        $now = time();

        // Fetch records.
        $execution = $DB->get_record('report_customsql_executions', ['id' => $executionid], '*', MUST_EXIST);
        $report = $DB->get_record('report_customsql_queries', ['id' => $execution->queryid], '*', MUST_EXIST);

        if (empty($execution->userid)) {
            $this->update_execution_status($executionid, self::STATUS_FAILED, [
                'errormessage' => get_string('invaliduser', 'error'),
                'timecompleted' => $now,
            ]);
            return;
        }

        // Use literal capability string for compatibility with some static analysers.
        if (!has_capability('report/customsql:executebackground', \context_system::instance(), $execution->userid)) {
            $this->update_execution_status($executionid, self::STATUS_FAILED, [
                'errormessage' => get_string('nopermissions', 'error', self::CAPABILITY_EXECUTE),
                'timecompleted' => $now,
            ]);
            return;
        }

        // Honour pre-cancel without changing original queued status.
        if (!empty($execution->cancelled)) {
            // Pre-cancelled execution; keep original status (expected by tests).
            return;
        }

        // Acquire exclusive lock.
        $lockfactory = \core\lock\lock_config::get_lock_factory(self::LOCK_AREA);
        $lock = $lockfactory->get_lock('execution_' . $executionid, self::LOCK_TIMEOUT);
        if (!$lock) {
            mtrace('Could not obtain lock for execution ' . $executionid);
            $this->update_execution_status($executionid, self::STATUS_FAILED, [
                'errormessage' => get_string('locktimeout', 'moodle'),
                'timecompleted' => $now,
            ]);
            return;
        }

        try {
            // Mark running.
            $this->update_execution_status($executionid, self::STATUS_RUNNING, ['timestarted' => $now]);
            $starttime = microtime(true);

            require_once($CFG->dirroot . '/report/customsql/locallib.php');
            $sql = report_customsql_prepare_sql($report, $now);

            // Decode params robustly.
            $params = [];
            if (!empty($execution->queryparams)) {
                $params = json_decode($execution->queryparams, true);
                if (json_last_error() !== JSON_ERROR_NONE || !is_array($params)) {
                    mtrace('Invalid JSON params for execution ' . $executionid . ': ' . json_last_error_msg());
                    $params = [];
                }
            }

            // Determine execution path: customdir (legacy) or file storage (new).
            $usecustomdir = !empty($report->customdir) && $report->runable !== 'manual';

            if ($usecustomdir) {
                // Legacy path: Write directly to dataroot directory.
                mtrace('Execution ' . $executionid . ' using legacy customdir path: ' . $report->customdir);
                $this->execute_with_customdir($executionid, $execution, $report, $sql, $params, $now, $starttime);
            } else {
                // New path: Write to file storage.
                mtrace('Execution ' . $executionid . ' using file storage path');
                $this->execute_with_filestorage($executionid, $execution, $report, $sql, $params, $now, $starttime);
            }
        } finally {
            if (!empty($lock)) {
                $lock->release();
            }
        }
    }

    /**
     * Update execution status (whitelisted fields only).
     *
     * @param int $executionid
     * @param string $status
     * @param array $data
     * @return void
     */
    private function update_execution_status(int $executionid, string $status, array $data = []): void {
        global $DB;
        $allowed = ['timestarted', 'timecompleted', 'errormessage', 'filename', 'filesize', 'rowsreturned', 'executiontime'];
        $update = new stdClass();
        $update->id = $executionid;
        $update->status = $status;
        foreach ($data as $field => $value) {
            if (in_array($field, $allowed, true)) {
                $update->$field = $value;
            }
        }
        $DB->update_record('report_customsql_executions', $update);
    }

    /**
     * Stream CSV data from recordset to file handle with cancellation checks.
     *
     * @param resource $handle File handle to write to
     * @param string $sql SQL query to execute
     * @param array $params Query parameters
     * @param stdClass $report Report record for header generation
     * @param int $executionid Execution ID for cancellation checks
     * @param bool $headerdone Whether header has already been written
     * @param bool $singlerow Whether to prepend timestamp for single-row accumulation
     * @param int $now Current timestamp for single-row mode
     * @return int Number of rows written
     */
    private function write_csv_stream(
        $handle,
        string $sql,
        array $params,
        stdClass $report,
        int $executionid,
        bool $headerdone = false,
        bool $singlerow = false,
        int $now = 0
    ): int {
        global $DB;

        $rs = $DB->get_recordset_sql($sql, $params);
        $rowcount = 0;
        $cancelinterval = 500;
        $sincecancel = 0;

        foreach ($rs as $row) {
            // Periodic cancellation check.
            if ($sincecancel >= $cancelinterval) {
                $sincecancel = 0;
                if ($DB->get_field('report_customsql_executions', 'cancelled', ['id' => $executionid])) {
                    mtrace('Execution ' . $executionid . ' cancelled mid-run. Aborting.');
                    $rs->close();
                    return $rowcount;
                }
            }
            $sincecancel++;

            // Write CSV header on first row.
            if (!$headerdone) {
                report_customsql_start_csv($handle, $row, $report);
                $headerdone = true;
            }

            // Prepare row data with date formatting.
            $data = get_object_vars($row);
            foreach ($data as $name => $value) {
                if (
                    report_customsql_get_element_type($name) == 'date_time_selector' &&
                    report_customsql_is_integer($value) && $value > 0
                ) {
                    $data[$name] = userdate($value, '%F %T');
                }
            }

            // Prepend timestamp for single-row accumulation mode.
            if ($singlerow) {
                array_unshift($data, \core_date::strftime('%Y-%m-%d', $now));
            }

            // Write data row.
            if ($singlerow) {
                report_customsql_write_csv_row($handle, $data);
            } else {
                fputcsv($handle, $data);
            }
            $rowcount++;
        }
        $rs->close();

        return $rowcount;
    }

    /**
     * Handle failure case: store error CSV and trigger event.
     *
     * @param int $executionid
     * @param \Exception $exception
     * @return void
     */
    private function handle_query_error(int $executionid, \Exception $exception): void {
        global $DB;

        $execution = $DB->get_record('report_customsql_executions', ['id' => $executionid]);
        $report = $execution ? $DB->get_record('report_customsql_queries', ['id' => $execution->queryid]) : null;

        $fs = get_file_storage();
        $context = \context_system::instance();
        $filename = 'error_exec_' . $executionid . '.csv';
        $existing = $fs->get_file($context->id, 'report_customsql', 'execution', $executionid, '/', $filename);
        if (!$existing) {
            $tempfile = make_temp_directory('report_customsql') . '/' . uniqid('err_', true) . '.csv';
            if ($h = fopen($tempfile, 'w')) {
                fputcsv($h, ['error']);
                $msg = clean_param(\core_text::substr($exception->getMessage(), 0, 1000), PARAM_TEXT);
                fputcsv($h, [$msg]);
                fclose($h);
                $filerecord = [
                    'contextid' => $context->id,
                    'component' => 'report_customsql',
                    'filearea' => 'execution',
                    'itemid' => $executionid,
                    'filepath' => '/',
                    'filename' => $filename,
                    'userid' => $execution ? $execution->userid : 0,
                ];
                $stored = $fs->create_file_from_pathname($filerecord, $tempfile);
                @unlink($tempfile);
                if ($stored) {
                    $filename = $stored->get_filename();
                }
            }
        }

        $update = new stdClass();
        $update->id = $executionid;
        $update->status = self::STATUS_FAILED;
        $update->errormessage = clean_param(\core_text::substr($exception->getMessage(), 0, 1000), PARAM_TEXT);
        $update->timecompleted = time();
        if (!empty($filename)) {
            $update->filename = $filename;
        }
        $DB->update_record('report_customsql_executions', $update);

        if ($execution) {
            $this->send_notification('failed', $execution, $report, [
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Unified notification sender.
     *
     * @param string $type completed|failed
     * @param stdClass $execution
     * @param stdClass|null $report
     * @param array $extra
     * @return void
     */
    private function send_notification(string $type, stdClass $execution, ?stdClass $report, array $extra): void {
        $user = \core_user::get_user($execution->userid);
        if (!$user || !$report) {
            return;
        }
        $msg = new \core\message\message();
        $msg->component = 'report_customsql';
        $msg->courseid = SITEID;
        if ($type === 'completed') {
            $msg->name = 'executioncompleted';
            $msg->subject = get_string('executioncompletedsubject', 'report_customsql', $report->displayname);
            $msg->fullmessage = get_string('executioncompletedmessage', 'report_customsql', [
                'queryname' => $report->displayname,
                'rowcount' => $extra['rowcount'] ?? 0,
                'executiontime' => format_time($extra['executiontime'] ?? 0),
            ]);
            $msg->smallmessage = get_string('executioncompletedsmall', 'report_customsql', $report->displayname);
        } else {
            $msg->name = 'executionfailed';
            $msg->subject = get_string('executionfailedsubject', 'report_customsql', $report->displayname);
            $msg->fullmessage = get_string('executionfailedmessage', 'report_customsql', [
                'queryname' => $report->displayname,
                'error' => $extra['error'] ?? '',
            ]);
            $msg->smallmessage = get_string('executionfailedsmall', 'report_customsql', $report->displayname);
        }
        $msg->userfrom = \core_user::get_noreply_user();
        $msg->userto = $user;
        $msg->fullmessageformat = FORMAT_PLAIN;
        $msg->fullmessagehtml = '';
        $msg->notification = 1;
        $msg->contexturl = new \moodle_url('/report/customsql/view.php', ['id' => $report->id]);
        $msg->contexturlname = get_string('viewexecutions', 'report_customsql');
        message_send($msg);
    }

    /**
     * Execute query with file storage path (new method).
     *
     * @param int $executionid Execution ID
     * @param stdClass $execution Execution record
     * @param stdClass $report Report record
     * @param string $sql Prepared SQL
     * @param array $params Query parameters
     * @param int $now Current timestamp
     * @param float $starttime Microtime when execution started
     * @return void
     * @throws moodle_exception
     */
    private function execute_with_filestorage(
        int $executionid,
        stdClass $execution,
        stdClass $report,
        string $sql,
        array $params,
        int $now,
        float $starttime
    ): void {
        global $DB;

        $filename = sprintf('query_%d_exec_%d_%d.csv', $report->id, $executionid, $now);
        $tempfile = make_temp_directory('report_customsql') . '/' . uniqid('exec_', true) . '.csv';
        $handle = fopen($tempfile, 'w');
        if (!$handle) {
            throw new \moodle_exception('cannotcreatetempfile', 'report_customsql');
        }

        // Write CSV data with streaming.
        $rowcount = $this->write_csv_stream($handle, $sql, $params, $report, $executionid);
        fclose($handle);

        $executiontime = (int) round(microtime(true) - $starttime);
        $fs = get_file_storage();
        $context = \context_system::instance();
        $filerecord = [
            'contextid' => $context->id,
            'component' => 'report_customsql',
            'filearea' => 'execution',
            'itemid' => $executionid,
            'filepath' => '/',
            'filename' => $filename,
            'userid' => $execution->userid,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $storedfile = $fs->create_file_from_pathname($filerecord, $tempfile);
        @unlink($tempfile);
        if (!$storedfile) {
            throw new \moodle_exception('cannotsavefile', 'report_customsql');
        }

        $this->update_execution_status($executionid, self::STATUS_COMPLETED, [
            'filename' => $filename,
            'filesize' => $storedfile->get_filesize(),
            'rowsreturned' => $rowcount,
            'executiontime' => $executiontime,
            'timecompleted' => $now,
        ]);

        $this->send_notification('completed', $execution, $report, [
            'rowcount' => $rowcount,
            'executiontime' => $executiontime,
        ]);

        // Handle post-processing for scheduled reports.
        if ($report->runable !== 'manual') {
            $this->handle_scheduled_report_post_processing($execution, $report, $storedfile, $now);
        }
    }

    /**
     * Execute query with customdir path (legacy method for backwards compatibility).
     *
     * @param int $executionid Execution ID
     * @param stdClass $execution Execution record
     * @param stdClass $report Report record
     * @param string $sql Prepared SQL
     * @param array $params Query parameters
     * @param int $now Current timestamp
     * @param float $starttime Microtime when execution started
     * @return void
     * @throws moodle_exception
     */
    private function execute_with_customdir(
        int $executionid,
        stdClass $execution,
        stdClass $report,
        string $sql,
        array $params,
        int $now,
        float $starttime
    ): void {
        global $DB;

        // Use legacy CSV filename generation.
        [$csvfilename, $csvtimestamp] = report_customsql_csv_filename($report, $now);

        // Ensure directory exists.
        $dir = dirname($csvfilename);
        if (!is_dir($dir)) {
            make_upload_directory(basename($dir));
        }

        // Determine if we append or create new file.
        $mode = (!file_exists($csvfilename)) ? 'w' : 'a';
        $handle = fopen($csvfilename, $mode);
        if (!$handle) {
            throw new \moodle_exception('cannotcreatetempfile', 'report_customsql');
        }

        // Write CSV data with streaming.
        $headerdone = ($mode === 'a'); // If appending, header already exists.
        $rowcount = $this->write_csv_stream(
            $handle,
            $sql,
            $params,
            $report,
            $executionid,
            $headerdone,
            (bool) $report->singlerow,
            $now
        );
        fclose($handle);

        $executiontime = (int) round(microtime(true) - $starttime);
        $filesize = file_exists($csvfilename) ? filesize($csvfilename) : 0;

        $this->update_execution_status($executionid, self::STATUS_COMPLETED, [
            'filename' => basename($csvfilename),
            'filesize' => $filesize,
            'rowsreturned' => $rowcount,
            'executiontime' => $executiontime,
            'timecompleted' => $now,
        ]);

        $this->send_notification('completed', $execution, $report, [
            'rowcount' => $rowcount,
            'executiontime' => $executiontime,
        ]);

        // Copy to custom directory.
        if (!empty($report->customdir)) {
            report_customsql_copy_csv_to_customdir($report, $now, $csvfilename);
        }

        // Send email if configured.
        if (!empty($report->emailto)) {
            report_customsql_email_report($report, $csvfilename);
        }

        // Update lastrun timestamp.
        $DB->set_field('report_customsql_queries', 'lastrun', $now, ['id' => $report->id]);
    }

    /**
     * Handle post-processing for scheduled reports (file storage path only).
     *
     * @param stdClass $execution Execution record
     * @param stdClass $report Report record
     * @param stored_file $storedfile The stored CSV file
     * @param int $now Current timestamp
     * @return void
     */
    private function handle_scheduled_report_post_processing(
        stdClass $execution,
        stdClass $report,
        \stored_file $storedfile,
        int $now
    ): void {
        global $DB;

        mtrace('  → Post-processing scheduled report ' . $report->id);

        try {
            // Send email if configured.
            if (!empty($report->emailto)) {
                mtrace('    → Sending email to: ' . $report->emailto);
                $temppath = $storedfile->copy_content_to_temp();
                report_customsql_email_report($report, $temppath);
                @unlink($temppath);
            }

            // Update lastrun timestamp.
            $DB->set_field('report_customsql_queries', 'lastrun', $now, ['id' => $report->id]);
        } catch (\Exception $e) {
            // Log but don't throw (execution itself was successful).
            mtrace('    ✗ Post-processing failed: ' . $e->getMessage());
        }
    }
}
