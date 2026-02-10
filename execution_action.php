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
 * Unified handler for execution actions (cancel, delete, etc.).
 *
 * @package    report_customsql
 * @copyright  2025 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once($CFG->libdir . '/adminlib.php');

$executionid = optional_param('id', 0, PARAM_INT);
$queryid = optional_param('queryid', 0, PARAM_INT);
$action = required_param('action', PARAM_ALPHA);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

// Validate action.
$validactions = ['cancel', 'delete', 'run'];
if (!in_array($action, $validactions)) {
    throw new moodle_exception('invalidaction', 'error');
}

// Validate required parameters based on action.
if ($action === 'run' && $queryid <= 0) {
    throw new moodle_exception('missingqueryid', 'report_customsql');
}
if (in_array($action, ['cancel', 'delete']) && $executionid <= 0) {
    throw new moodle_exception('missingexecutionid', 'report_customsql');
}

require_login();
$context = context_system::instance();
require_capability('report/customsql:view', $context);

// Run action additionally requires executebackground capability.
if ($action === 'run') {
    require_capability('report/customsql:executebackground', $context);
}

// Get records based on action type.
if ($action === 'run') {
    // For run action, get query record.
    $query = $DB->get_record('report_customsql_queries', ['id' => $queryid], '*', MUST_EXIST);
    $execution = null;

    // Check if query supports async execution.
    if ($query->runable !== 'manual_async') {
        throw new moodle_exception(
            'querynotasync',
            'report_customsql',
            new moodle_url('/report/customsql/index.php')
        );
    }
} else {
    // For cancel/delete actions, get execution record.
    $execution = $DB->get_record('report_customsql_executions', ['id' => $executionid], '*', MUST_EXIST);
    $query = $DB->get_record('report_customsql_queries', ['id' => $execution->queryid], '*', MUST_EXIST);

    // Check permissions: user must own the execution or have viewallexecutions capability.
    $canviewall = has_capability('report/customsql:viewallexecutions', $context);
    $isowner = $execution->userid == $USER->id;

    if (!$canviewall && !$isowner) {
        $errorkey = 'nopermissionto' . $action . 'execution';
        throw new moodle_exception(
            $errorkey,
            'report_customsql',
            new moodle_url('/report/customsql/index.php')
        );
    }
}

// Action-specific validation.
if ($action === 'cancel') {
    $validstatuses = ['pending', 'running'];
    if (!in_array($execution->status, $validstatuses) || $execution->cancelled) {
        // Build detailed error message for debugging.
        $debuginfo = sprintf(
            'Cannot cancel execution: status=%s (valid: %s), cancelled=%d',
            $execution->status,
            implode(', ', $validstatuses),
            $execution->cancelled
        );
        throw new moodle_exception(
            'cannotcancelexecution',
            'report_customsql',
            new moodle_url('/report/customsql/executions.php', ['queryid' => $execution->queryid]),
            $debuginfo
        );
    }
}

// Determine return URL.
if (empty($returnurl)) {
    if ($action === 'run') {
        $returnurl = new moodle_url('/report/customsql/executions.php', ['queryid' => $queryid]);
    } else {
        $returnurl = new moodle_url('/report/customsql/executions.php', ['queryid' => $execution->queryid]);
    }
} else {
    $returnurl = new moodle_url($returnurl);
}

// Setup page.
$PAGE->set_url('/report/customsql/execution_action.php', ['id' => $executionid, 'action' => $action]);
$PAGE->set_context($context);
$PAGE->set_pagelayout('report');

// Action-specific strings.
$pagetitle = get_string($action . 'execution', 'report_customsql');
$PAGE->set_title($pagetitle);
$PAGE->set_heading(get_string('pluginname', 'report_customsql'));

// Navigation.
$PAGE->navbar->add(get_string('pluginname', 'report_customsql'), new moodle_url('/report/customsql/index.php'));
if ($action !== 'run') {
    $PAGE->navbar->add(
        get_string('viewexecutions', 'report_customsql'),
        new moodle_url('/report/customsql/executions.php', ['queryid' => $execution->queryid])
    );
}
$PAGE->navbar->add($pagetitle);

// Handle confirmation.
if ($confirm && confirm_sesskey()) {
    try {
        switch ($action) {
            case 'cancel':
                \report_customsql\local\execution_manager::cancel_execution($executionid);
                $successmsg = get_string('executioncancelled', 'report_customsql');
                break;

            case 'delete':
                \report_customsql\local\execution_manager::delete_execution($executionid);
                $successmsg = get_string('executiondeleted', 'report_customsql');
                break;

            case 'run':
                // Create background execution (includes execution limit check internally).
                $newexecutionid = \report_customsql\local\execution_manager::create_background_execution(
                    $queryid,
                    $USER->id,
                    [] // Empty params for now - can be extended later.
                );
                $successmsg = get_string('executionqueued', 'report_customsql');

                // Redirect to executions page to see the new execution.
                $returnurl = new moodle_url('/report/customsql/executions.php', ['queryid' => $queryid]);
                break;
        }

        redirect($returnurl, $successmsg, null, \core\output\notification::NOTIFY_SUCCESS);
    } catch (Exception $e) {
        redirect(
            $returnurl,
            get_string('actionfailed', 'report_customsql'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
}

// Show confirmation form.
echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle, 2);

// Show action-specific warning.
if ($action === 'cancel') {
    echo $OUTPUT->notification(get_string('cancelexecution_warning', 'report_customsql'), 'warning');
}

// Show details based on action.
if ($action === 'run') {
    // For run action, show query details.
    echo html_writer::tag('p', get_string('confirmrunexecution', 'report_customsql'));

    $table = new html_table();
    $table->attributes['class'] = 'generaltable';
    $table->data = [];

    $table->data[] = [
        html_writer::tag('strong', get_string('query', 'report_customsql')),
        format_string($query->displayname),
    ];

    if (!empty($query->description)) {
        $table->data[] = [
            html_writer::tag('strong', get_string('description')),
            format_text($query->description, FORMAT_HTML),
        ];
    }

    echo html_writer::table($table);
} else {
    // For cancel/delete actions, show execution details.
    $executioninfo = new stdClass();
    $executioninfo->created = userdate($execution->timecreated, get_string('strftimedatetime'));
    $executioninfo->status = get_string('status_' . $execution->status, 'report_customsql');

    $confirmmsg = get_string('confirm' . $action . 'execution', 'report_customsql', $executioninfo);
    echo html_writer::tag('p', $confirmmsg);

    // Show details table.
    $table = new html_table();
    $table->attributes['class'] = 'generaltable';
    $table->data = [];

    $table->data[] = [
        html_writer::tag('strong', get_string('query', 'report_customsql')),
        format_string($query->displayname),
    ];

    $table->data[] = [
        html_writer::tag('strong', get_string('status', 'report_customsql')),
        get_string('status_' . $execution->status, 'report_customsql'),
    ];

    $table->data[] = [
        html_writer::tag('strong', get_string('created', 'report_customsql')),
        userdate($execution->timecreated, get_string('strftimedatetime')),
    ];

    // Cancel: show running details.
    if ($action === 'cancel' && $execution->timestarted) {
        $table->data[] = [
            html_writer::tag('strong', get_string('started', 'report_customsql')),
            userdate($execution->timestarted, get_string('strftimedatetime')),
        ];

        $runningtime = time() - $execution->timestarted;
        $table->data[] = [
            html_writer::tag('strong', get_string('runningfor', 'report_customsql')),
            format_time($runningtime),
        ];
    }

    // Delete: show completion details.
    if ($action === 'delete') {
        if ($execution->timecompleted) {
            $table->data[] = [
                html_writer::tag('strong', get_string('completed', 'report_customsql')),
                userdate($execution->timecompleted, get_string('strftimedatetime')),
            ];
        }

        if ($execution->rowsreturned) {
            $table->data[] = [
                html_writer::tag('strong', get_string('rows', 'report_customsql')),
                $execution->rowsreturned,
            ];
        }

        if ($execution->filesize) {
            $table->data[] = [
                html_writer::tag('strong', get_string('filesize', 'report_customsql')),
                display_size($execution->filesize),
            ];
        }

        if ($execution->status === 'failed' && $execution->errormessage) {
            $table->data[] = [
                html_writer::tag('strong', get_string('error')),
                s($execution->errormessage),
            ];
        }
    }

    echo html_writer::table($table);
}

// Confirmation buttons.
$confirmparams = [
    'action' => $action,
    'confirm' => 1,
    'sesskey' => sesskey(),
];

if ($action === 'run') {
    $confirmparams['queryid'] = $queryid;
} else {
    $confirmparams['id'] = $executionid;
}

if (!empty($returnurl)) {
    $confirmparams['returnurl'] = $returnurl->out_as_local_url(false);
}

$confirmurl = new moodle_url('/report/customsql/execution_action.php', $confirmparams);

echo $OUTPUT->confirm(
    get_string('areyousure'),
    $confirmurl,
    $returnurl
);

echo $OUTPUT->footer();
