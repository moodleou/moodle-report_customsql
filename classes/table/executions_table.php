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
 * Dynamic table for listing query executions.
 *
 * @package    report_customsql
 * @copyright  2025 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_customsql\table;

use context_system;
use core_table\dynamic as dynamic_table;
use core_table\local\filter\filterset;
use html_writer;
use moodle_url;
use pix_icon;
use stdClass;

/**
 * Dynamic table for listing query executions.
 *
 * @package    report_customsql
 * @copyright  2025 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class executions_table extends \table_sql implements dynamic_table {
    /** @var bool Whether user can view all executions */
    private $canviewall;

    /**
     * Sets up the table.
     *
     * @param filterset $filterset The filterset
     */
    public function __construct(filterset $filterset) {
        parent::__construct('report-customsql-executions-table');

        $context = context_system::instance();
        $this->canviewall = has_capability('report/customsql:viewallexecutions', $context);

        // Define columns.
        $columns = [
            'queryname',
            'username',
            'status',
            'timecreated',
            'timecompleted',
            'executiontime',
            'rowsreturned',
            'filesize',
            'actions',
        ];

        $headers = [
            get_string('query', 'report_customsql'),
            get_string('user'),
            get_string('status', 'report_customsql'),
            get_string('created', 'report_customsql'),
            get_string('completed', 'report_customsql'),
            get_string('executiontime', 'report_customsql'),
            get_string('rows', 'report_customsql'),
            get_string('filesize', 'report_customsql'),
            get_string('actions', 'report_customsql'),
        ];

        $this->define_columns($columns);
        $this->define_headers($headers);

        // Make table sortable.
        $this->sortable(true, 'timecreated', SORT_DESC);
        $this->no_sorting('actions');

        // Setup SQL.
        $this->setup_sql($filterset);

        // Table settings.
        $this->collapsible(false);
        $this->pageable(true);
    }

    /**
     * Check capability for users accessing the dynamic table.
     *
     * @return bool True if user has capability to view executions
     */
    public function has_capability(): bool {
        $context = context_system::instance();
        return has_capability('report/customsql:view', $context);
    }

    /**
     * Setup SQL query based on filters.
     *
     * @param filterset $filterset The filterset
     */
    private function setup_sql(filterset $filterset): void {
        global $DB, $USER;

        $fields = 'e.*, q.displayname as queryname, q.customdir, q.runable, ' .
                  $DB->sql_concat('u.firstname', "' '", 'u.lastname') . ' as username, ' .
                  'e.userid as execuserid';

        $from = '{report_customsql_executions} e
                 JOIN {report_customsql_queries} q ON e.queryid = q.id
                 JOIN {user} u ON e.userid = u.id';

        $where = '1=1';
        $params = [];

        // Apply filters.
        $filters = $filterset->get_filters();
        foreach ($filters as $filter) {
            $filtervalues = $filter->get_filter_values();
            if (empty($filtervalues)) {
                continue;
            }

            switch ($filter->get_name()) {
                case 'queryid':
                    $queryid = reset($filtervalues);
                    if ($queryid > 0) {
                        $where .= ' AND e.queryid = :queryid';
                        $params['queryid'] = $queryid;
                    }
                    break;

                case 'status':
                    $status = reset($filtervalues);
                    if ($status !== 'all' && !empty($status)) {
                        $where .= ' AND e.status = :status';
                        $params['status'] = $status;
                    }
                    break;

                case 'userid':
                    $userid = reset($filtervalues);
                    if ($userid > 0) {
                        $where .= ' AND e.userid = :userid';
                        $params['userid'] = $userid;
                    }
                    break;
            }
        }

        // If user cannot view all executions, only show their own.
        if (!$this->canviewall) {
            $where .= ' AND e.userid = :currentuserid';
            $params['currentuserid'] = $USER->id;
        }

        $this->set_sql($fields, $from, $where, $params);
    }

    /**
     * Query name column.
     *
     * @param stdClass $row Table row
     * @return string Formatted column
     */
    public function col_queryname(stdClass $row): string {
        $queryurl = new moodle_url('/report/customsql/view.php', ['id' => $row->queryid]);
        return html_writer::link($queryurl, format_string($row->queryname));
    }

    /**
     * Username column.
     *
     * @param stdClass $row Table row
     * @return string Formatted column
     */
    public function col_username(stdClass $row): string {
        $userurl = new moodle_url('/user/profile.php', ['id' => $row->execuserid]);
        return html_writer::link($userurl, $row->username);
    }

    /**
     * Status column with badge.
     *
     * @param stdClass $row Table row
     * @return string Formatted column
     */
    public function col_status(stdClass $row): string {
        $statusclass = 'badge ';

        // If cancelled flag is set but status is still pending/running, show as cancelling.
        if ($row->cancelled && in_array($row->status, ['pending', 'running'])) {
            $statusclass .= 'badge-warning';
            $statustext = get_string('cancelpending', 'report_customsql');
        } else {
            // Show normal status.
            switch ($row->status) {
                case 'queued':
                case 'pending':
                    $statusclass .= 'badge-info';
                    break;
                case 'running':
                    $statusclass .= 'badge-primary';
                    break;
                case 'completed':
                    $statusclass .= 'badge-success';
                    break;
                case 'failed':
                    $statusclass .= 'badge-danger';
                    break;
                case 'cancelled':
                    $statusclass .= 'badge-warning';
                    break;
            }
            $statustext = get_string('status_' . $row->status, 'report_customsql');
        }

        return html_writer::tag('span', $statustext, ['class' => $statusclass]);
    }

    /**
     * Created time column.
     *
     * @param stdClass $row Table row
     * @return string Formatted column
     */
    public function col_timecreated(stdClass $row): string {
        return userdate($row->timecreated, get_string('strftimedatetime'));
    }

    /**
     * Completed time column.
     *
     * @param stdClass $row Table row
     * @return string Formatted column
     */
    public function col_timecompleted(stdClass $row): string {
        return $row->timecompleted ? userdate($row->timecompleted, get_string('strftimedatetime')) : '-';
    }

    /**
     * Execution time column.
     *
     * @param stdClass $row Table row
     * @return string Formatted column
     */
    public function col_executiontime(stdClass $row): string {
        return $row->executiontime ? format_time($row->executiontime) : '-';
    }

    /**
     * Rows returned column.
     *
     * @param stdClass $row Table row
     * @return string Formatted column
     */
    public function col_rowsreturned(stdClass $row): string {
        return $row->rowsreturned ?? '-';
    }

    /**
     * File size column.
     *
     * @param stdClass $row Table row
     * @return string Formatted column
     */
    public function col_filesize(stdClass $row): string {
        return $row->filesize ? display_size($row->filesize) : '-';
    }

    /**
     * Actions column with icons.
     *
     * @param stdClass $row Table row
     * @return string Formatted column
     */
    public function col_actions(stdClass $row): string {
        global $OUTPUT, $USER;

        $context = context_system::instance();
        $actions = [];

        // View/Download action - distinguish between manual_async and scheduled queries.
        if ($row->status === 'completed') {
            if ($row->runable === 'manual_async' && !empty($row->filename)) {
                // Manual async: Download file via pluginfile.
                $downloadurl = moodle_url::make_pluginfile_url(
                    $context->id,
                    'report_customsql',
                    'execution',
                    $row->id,
                    '/',
                    $row->filename,
                    true  // Force download.
                );
                $actions[] = $OUTPUT->action_icon(
                    $downloadurl,
                    new pix_icon('t/download', get_string('download'))
                );
            } else {
                // Scheduled queries: View query results page.
                $viewurl = new moodle_url('/report/customsql/view.php', ['id' => $row->queryid]);
                $actions[] = $OUTPUT->action_icon(
                    $viewurl,
                    new pix_icon('t/preview', get_string('view'))
                );
            }
        }

        // Show error message if failed.
        if ($row->status === 'failed' && $row->errormessage) {
            $actions[] = $OUTPUT->action_icon(
                new moodle_url('#'),
                new pix_icon('i/warning', $row->errormessage),
                null,
                ['onclick' => 'return false;']
            );
        }

        // Cancel action (only if pending or running and not already cancelled).
        if (in_array($row->status, ['pending', 'running']) && !$row->cancelled) {
            $isowner = $row->execuserid == $USER->id;
            if ($this->canviewall || $isowner) {
                $cancelurl = new moodle_url(
                    '/report/customsql/execution_action.php',
                    ['id' => $row->id, 'action' => 'cancel', 'returnurl' => '/report/customsql/executions.php']
                );
                $actions[] = $OUTPUT->action_icon(
                    $cancelurl,
                    new pix_icon('t/stop', get_string('cancel', 'report_customsql'))
                );
            }
        }

        // Delete action.
        $isowner = $row->execuserid == $USER->id;
        if ($this->canviewall || $isowner) {
            $deleteurl = new moodle_url(
                '/report/customsql/execution_action.php',
                ['id' => $row->id, 'action' => 'delete', 'returnurl' => '/report/customsql/executions.php']
            );
            $actions[] = $OUTPUT->action_icon(
                $deleteurl,
                new pix_icon('t/delete', get_string('delete'))
            );
        }

        return implode(' ', $actions);
    }

    /**
     * Get the context for the table.
     *
     * @return \context
     */
    public function get_context(): \context {
        return context_system::instance();
    }

    /**
     * Guess the base url for the table.
     */
    public function guess_base_url(): void {
        $this->baseurl = new moodle_url('/report/customsql/executions.php');
    }
}
