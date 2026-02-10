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
 * Page to list and manage background query executions.
 *
 * @package    report_customsql
 * @copyright  2025 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

use report_customsql\table\executions_table;
use report_customsql\table\executions_table_filterset;
use report_customsql\output\executions_page;

// Parameters for filters.
$queryid = optional_param('queryid', 0, PARAM_INT);
$status = optional_param('status', 'all', PARAM_ALPHA);
$onlymine = optional_param('onlymine', 0, PARAM_BOOL);
$reset = optional_param('reset', 0, PARAM_BOOL);

require_login();
$context = context_system::instance();
require_capability('report/customsql:view', $context);

$canviewall = has_capability('report/customsql:viewallexecutions', $context);

// Setup page.
$urlparams = ['queryid' => $queryid, 'status' => $status, 'onlymine' => $onlymine];
$PAGE->set_url('/report/customsql/executions.php', $urlparams);
$PAGE->set_context($context);
$PAGE->set_pagelayout('report');

// Page heading.
if ($queryid) {
    $query = $DB->get_record('report_customsql_queries', ['id' => $queryid], '*', MUST_EXIST);
    $pagetitle = get_string('executionsfor', 'report_customsql', format_string($query->displayname));
} else {
    $pagetitle = get_string('backgroundexecutions', 'report_customsql');
}
$PAGE->set_title($pagetitle);
$PAGE->set_heading(get_string('pluginname', 'report_customsql'));

// Navigation.
$PAGE->navbar->add(get_string('pluginname', 'report_customsql'), new moodle_url('/report/customsql/index.php'));
$PAGE->navbar->add(get_string('manageexecutions', 'report_customsql'));

echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle);

// Get statistics.
if ($queryid) {
    $stats = \report_customsql\local\execution_manager::get_query_statistics($queryid);
} else {
    $stats = \report_customsql\local\execution_manager::get_global_queue_statistics();
}

// Get available queries for filter.
$queries = $DB->get_records_menu('report_customsql_queries', null, 'displayname', 'id, displayname');

// Setup filterset.
$filterset = new executions_table_filterset();
if ($queryid > 0) {
    $filterset->add_filter_from_params('queryid', null, [(int)$queryid]);
}
if ($status !== 'all' && !empty($status)) {
    $filterset->add_filter_from_params('status', null, [(string)$status]);
}
if ($onlymine || !$canviewall) {
    $filterset->add_filter_from_params('userid', null, [(int)$USER->id]);
}

// Create and capture table output.
$table = new executions_table($filterset);
$table->is_downloading('', '', '');
$table->define_baseurl($PAGE->url);

ob_start();
$table->out(50, false);
$tablehtml = ob_get_clean();

// Prepare back URL and link text.
if ($queryid) {
    $backurl = new moodle_url('/report/customsql/view.php', ['id' => $queryid]);
    $backlinktext = get_string('back');
} else {
    $backurl = new moodle_url('/report/customsql/index.php');
    $backlinktext = get_string('backtoreportlist', 'report_customsql');
}

// Create renderable and render.
$renderablepage = new executions_page(
    $queryid,
    $status,
    $onlymine,
    $canviewall,
    $stats ?? [],
    $queries,
    $tablehtml,
    $PAGE->url,
    $backurl,
    $backlinktext
);

echo $OUTPUT->render($renderablepage);

echo $OUTPUT->footer();
