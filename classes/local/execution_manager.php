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
 * Manager class for handling query executions.
 *
 * @package    report_customsql
 * @copyright  2025 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_customsql\local;

use context_system;
use moodle_exception;
use stdClass;

/**
 * Manager class for handling query executions.
 *
 * @package    report_customsql
 * @copyright  2025 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class execution_manager {
    /**
     * Create a new background execution record and queue the task.
     *
     * @param int $queryid Query ID
     * @param int $userid User ID
     * @param array $params Query parameters
     * @return int Execution ID
     * @throws moodle_exception
     */
    public static function create_background_execution(int $queryid, int $userid, array $params = []): int {
        global $DB;

        // Check if background execution is enabled.
        if (!get_config('report_customsql', 'enablebackgroundexecution')) {
            throw new moodle_exception('backgroundexecutiondisabled', 'report_customsql');
        }

        // Validate query exists.
        $query = $DB->get_record('report_customsql_queries', ['id' => $queryid], '*', MUST_EXIST);

        // Lock to prevent parallel duplicate requests for the same user/query.
        $factory = \core\lock\lock_config::get_lock_factory('report_customsql');
        $lockkey = 'exec_' . $queryid . '_' . $userid;
        if (!($lock = $factory->get_lock($lockkey, 5))) {
            throw new moodle_exception('executionalreadyqueued', 'report_customsql');
        }

        try {
            // Check execution limits inside the lock to avoid TOCTOU race condition.
            self::check_execution_limit($userid);
            $execution = new stdClass();
            $execution->queryid = $queryid;
            $execution->userid = $userid;
            $execution->executionmode = 'background';
            $execution->status = 'pending';
            $execution->queryparams = !empty($params) ? json_encode($params) : null;
            $execution->timecreated = time();

            $executionid = $DB->insert_record('report_customsql_executions', $execution);
        } finally {
            $lock->release();
        }

        // Queue adhoc task.
        $task = new \report_customsql\task\execute_query_adhoc();
        $task->set_custom_data([
            'executionid' => $executionid,
        ]);
        $task->set_userid($userid);
        $task->set_component('report_customsql');

        \core\task\manager::queue_adhoc_task($task);

        return $executionid;
    }

    /**
     * Check if user has reached concurrent execution limit.
     *
     * @param int $userid User ID
     * @throws moodle_exception
     */
    public static function check_execution_limit(int $userid): void {
        global $DB;

        $maxconcurrent = get_config('report_customsql', 'maxconcurrentexecutions');
        if ($maxconcurrent === false) {
            $maxconcurrent = 10; // Default value.
        }

        // Also check per-user limit.
        $maxuserexecutions = get_config('report_customsql', 'maxuserexecutions');
        if ($maxuserexecutions === false) {
            $maxuserexecutions = 3; // Default value.
        }

        // Count pending and running executions for this user.
        $usercount = $DB->count_records_select(
            'report_customsql_executions',
            'userid = :userid AND status IN (:pending, :running)',
            [
                'userid' => $userid,
                'pending' => 'pending',
                'running' => 'running',
            ]
        );

        if ($usercount >= $maxuserexecutions) {
            throw new moodle_exception('userexecutionlimitreached', 'report_customsql', '', $maxuserexecutions);
        }

        // Count total pending and running executions globally.
        $totalcount = $DB->count_records_select(
            'report_customsql_executions',
            'status IN (:pending, :running)',
            [
                'pending' => 'pending',
                'running' => 'running',
            ]
        );

        if ($totalcount >= $maxconcurrent) {
            throw new moodle_exception('executionlimitreached', 'report_customsql', '', $maxconcurrent);
        }
    }

    /**
     * Get executions for a query with permission checking.
     *
     * @param int $queryid Query ID
     * @param int $userid User ID requesting the list
     * @param bool $canviewall Whether user can view all executions
     * @param string $statusfilter Status filter ('all', 'completed', 'failed', 'running')
     * @param int $limitfrom Starting record
     * @param int $limitnum Number of records
     * @return array Array of execution records
     */
    public static function get_executions(
        int $queryid,
        int $userid,
        bool $canviewall = false,
        string $statusfilter = 'all',
        int $limitfrom = 0,
        int $limitnum = 0
    ): array {
        global $DB;

        $conditions = ['queryid' => $queryid];

        if (!$canviewall) {
            $conditions['userid'] = $userid;
        }

        if ($statusfilter !== 'all') {
            $conditions['status'] = $statusfilter;
        }

        return $DB->get_records(
            'report_customsql_executions',
            $conditions,
            'timecreated DESC',
            '*',
            $limitfrom,
            $limitnum
        );
    }

    /**
     * Count executions for a query with permission checking.
     *
     * @param int $queryid Query ID
     * @param int $userid User ID requesting the count
     * @param bool $canviewall Whether user can view all executions
     * @param string $statusfilter Status filter
     * @return int Count of executions
     */
    public static function count_executions(
        int $queryid,
        int $userid,
        bool $canviewall = false,
        string $statusfilter = 'all'
    ): int {
        global $DB;

        $conditions = ['queryid' => $queryid];

        if (!$canviewall) {
            $conditions['userid'] = $userid;
        }

        if ($statusfilter !== 'all') {
            $conditions['status'] = $statusfilter;
        }

        return $DB->count_records('report_customsql_executions', $conditions);
    }

    /**
     * Delete an execution and its associated file.
     *
     * @param int $executionid Execution ID
     * @param bool $checkpermissions Whether to check permissions
     * @throws moodle_exception
     */
    public static function delete_execution(int $executionid, bool $checkpermissions = true): void {
        global $DB, $USER;

        $execution = $DB->get_record('report_customsql_executions', ['id' => $executionid], '*', MUST_EXIST);

        if ($checkpermissions) {
            $context = context_system::instance();
            $canviewall = has_capability('report/customsql:viewallexecutions', $context);

            if (!$canviewall && $execution->userid != $USER->id) {
                throw new moodle_exception('nopermissiontodeleteexecution', 'report_customsql');
            }
        }

        // Transaction for atomic File + DB Deletion.
        $transaction = $DB->start_delegated_transaction();
        try {
            // Delete execution record first.
            $DB->delete_records('report_customsql_executions', ['id' => $executionid]);

            // Delete associated file from file storage.
            if (!empty($execution->filename)) {
                $fs = get_file_storage();
                $context = context_system::instance();

                $file = $fs->get_file(
                    $context->id,
                    'report_customsql',
                    'execution',
                    $executionid,
                    '/',
                    $execution->filename
                );

                if ($file) {
                    $file->delete();
                }
            }

            $transaction->allow_commit();
        } catch (\Exception $e) {
            $transaction->rollback($e);
            throw $e;
        }
    }

    /**
     * Cancel a pending or running execution.
     *
     * @param int $executionid Execution ID
     * @throws moodle_exception
     */
    public static function cancel_execution(int $executionid): void {
        global $DB;

        $execution = $DB->get_record('report_customsql_executions', ['id' => $executionid], '*', MUST_EXIST);

        if (!in_array($execution->status, ['pending', 'running'])) {
            throw new moodle_exception('cannotcancelexecution', 'report_customsql');
        }

        // Set cancelled flag (Task checks this flag periodically).
        $update = new stdClass();
        $update->id = $executionid;
        $update->cancelled = 1;
        $update->status = 'failed';
        $update->errormessage = get_string('executioncancelled', 'report_customsql');
        $update->timecompleted = time();

        $DB->update_record('report_customsql_executions', $update);

        // Trigger event.
    }

    /**
     * Get queue statistics for a specific query.
     *
     * @param int $queryid Query ID to get statistics for
     * @return array Statistics array
     */
    public static function get_query_statistics(int $queryid): array {
        global $DB;

        $stats = [
            'queryid' => $queryid,
            'pending' => $DB->count_records(
                'report_customsql_executions',
                ['queryid' => $queryid, 'status' => 'pending']
            ),
            'running' => $DB->count_records(
                'report_customsql_executions',
                ['queryid' => $queryid, 'status' => 'running']
            ),
            'completed_total' => $DB->count_records(
                'report_customsql_executions',
                ['queryid' => $queryid, 'status' => 'completed']
            ),
            'failed_total' => $DB->count_records(
                'report_customsql_executions',
                ['queryid' => $queryid, 'status' => 'failed']
            ),
            'failed_last_hour' => 0,
            'avg_execution_time' => 0,
            'success_rate' => 0,
        ];

        // Failed in last hour for this query.
        $onehourago = time() - HOURSECS;
        $stats['failed_last_hour'] = $DB->count_records_select(
            'report_customsql_executions',
            'queryid = :queryid AND status = :status AND timecompleted > :time',
            ['queryid' => $queryid, 'status' => 'failed', 'time' => $onehourago]
        );

        // Average execution time of last 20 successful executions for this query.
        // Using PHP to compute the average for cross-DB compatibility (LIMIT in subquery is not portable).
        $recentexecutions = $DB->get_records_select(
            'report_customsql_executions',
            'queryid = :queryid AND status = :status AND executiontime IS NOT NULL',
            ['queryid' => $queryid, 'status' => 'completed'],
            'timecompleted DESC',
            'executiontime',
            0,
            20
        );
        if (!empty($recentexecutions)) {
            $total = 0;
            foreach ($recentexecutions as $exec) {
                $total += $exec->executiontime;
            }
            $stats['avg_execution_time'] = round($total / count($recentexecutions));
        }

        // Success rate calculation.
        $total = $stats['completed_total'] + $stats['failed_total'];
        if ($total > 0) {
            $stats['success_rate'] = round(($stats['completed_total'] / $total) * 100, 1);
        }

        return $stats;
    }

    /**
     * Get global queue statistics (all queries).
     *
     * @return array Global statistics
     */
    public static function get_global_queue_statistics(): array {
        global $DB;

        $stats = [
            'total_pending' => $DB->count_records('report_customsql_executions', ['status' => 'pending']),
            'total_running' => $DB->count_records('report_customsql_executions', ['status' => 'running']),
            'total_failed_last_hour' => 0,
        ];

        // Failed in last hour (all queries).
        $onehourago = time() - HOURSECS;
        $stats['total_failed_last_hour'] = $DB->count_records_select(
            'report_customsql_executions',
            'status = :status AND timecompleted > :time',
            ['status' => 'failed', 'time' => $onehourago]
        );

        return $stats;
    }
}
