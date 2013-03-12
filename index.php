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
 * Custom SQL report.
 *
 * Users with the report/customsql:definequeries capability can enter custom
 * SQL SELECT statements. Other users with the moodle/site:viewreports capability
 * can see the list of available queries and run them. Reports are displayed as
 * a table. Every data value is a string, and field names come from the database
 * results set.
 *
 * This page shows the list of queries, with edit icons, and an add new button
 * if you have the report/customsql:definequeries capability.
 *
 * @package report_customsql
 * @copyright 2009 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
$context = get_context_instance(CONTEXT_SYSTEM);
require_capability('report/customsql:view', $context);

$manualreports = $DB->get_records('report_customsql_queries', array('runable' => 'manual'),
                                  'displayname');
$scheduledreports = $DB->get_records_list('report_customsql_queries', 'runable',
                                          array('weekly', 'monthly'), 'displayname');
$dailyreports = $DB->get_records('report_customsql_queries', array('runable' => 'daily'), 'displayname');

// Start the page.
admin_externalpage_setup('report_customsql');
echo $OUTPUT->header();

if (empty($manualreports) && empty($scheduledreports)) {
    echo $OUTPUT->heading(get_string('availablereports', 'report_customsql')).
         html_writer::tag('p', get_string('noreportsavailable', 'report_customsql'));

} else {
    if (!empty($manualreports)) {
        echo $OUTPUT->heading(get_string('availablereports', 'report_customsql')).
             html_writer::tag('p', get_string('manualnote', 'report_customsql'));
        report_customsql_print_reports($manualreports);
    }
    if (!empty($dailyreports)) {
        echo $OUTPUT->heading(get_string('dailyqueries', 'report_customsql')).
        html_writer::tag('p', get_string('dailynote', 'report_customsql'));
        report_customsql_print_reports($dailyreports);
    }
    if (!empty($scheduledreports)) {
        echo $OUTPUT->heading(get_string('scheduledqueries', 'report_customsql')).
             html_writer::tag('p', get_string('schedulednote', 'report_customsql'));
        report_customsql_print_reports($scheduledreports);
    }
}

if (has_capability('report/customsql:definequeries', $context)) {
    echo $OUTPUT->single_button(report_customsql_url('edit.php'),
                                get_string('addreport', 'report_customsql'));
}

echo $OUTPUT->footer();
