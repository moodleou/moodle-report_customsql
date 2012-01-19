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
 * Script to delete a particular custom SQL report, with confirmation.
 *
 * @package report_customsql
 * @copyright 2009 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(__FILE__) . '/../../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once($CFG->libdir . '/adminlib.php');

$id = required_param('id', PARAM_INT);
$report = get_record('report_customsql_queries', 'id', $id);
if (!$report) {
    print_error('invalidreportid', 'report_customsql', report_customsql_url('index.php'), $id);
}

require_login();
$context = get_context_instance(CONTEXT_SYSTEM);
require_capability('report/customsql:definequeries', $context);

if (optional_param('confirm', false, PARAM_BOOL)) {
    $ok = delete_records('report_customsql_queries', 'id', $id);
    if (!$ok) {
        print_error('errordeletingreport', 'report_customsql', report_customsql_url('index.php'));
    }
    report_customsql_log_delete($id);
    redirect(report_customsql_url('index.php'));
}

$runnableoptions = report_customsql_runable_options();

// Start the page.
admin_externalpage_setup('reportcustomsql');
admin_externalpage_print_header();
print_heading(get_string('deleteareyousure', 'report_customsql'));

echo '<p>' . get_string('displaynamex', 'report_customsql', '<b>' .
        format_string($report->displayname) . '</b>') . '</p>';
echo '<p>' . get_string('querysql', 'report_customsql') . '</p>';
echo '<pre>' . htmlspecialchars($report->querysql) . '</pre>';
echo '<p>' . get_string('runablex', 'report_customsql', $runnableoptions[$report->runable]) . '</p>';


notice_yesno(get_string('deleteareyousure', 'report_customsql'),
        report_customsql_url('delete.php'), report_customsql_url('index.php'),
        array('id' => $id, 'confirm' => 1), null, 'post', 'get');

admin_externalpage_print_footer();