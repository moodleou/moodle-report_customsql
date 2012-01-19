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
 * Script for editing a custom SQL report.
 *
 * @package report_customsql
 * @copyright 2009 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(__FILE__) . '/../../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once(dirname(__FILE__) . '/edit_form.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
$context = get_context_instance(CONTEXT_SYSTEM);
require_capability('report/customsql:definequeries', $context);

$relativeurl = 'edit.php';
$report = null;

// Are we editing an existing report, or creating a new one.
$id = optional_param('id', 0, PARAM_INT);
if ($id) {
    $report = get_record('report_customsql_queries', 'id', $id);
    if (!$report) {
        print_error('invalidreportid', 'report_customsql', report_customsql_url('index.php'), $id);
    }
    $relativeurl .= '?id=' . $id;
}

$mform = new report_customsql_edit_form(report_customsql_url($relativeurl));
if ($report) {
    $mform->set_data($report);
}

if ($mform->is_cancelled()) {
    redirect(report_customsql_url('index.php'));

} else if ($newreport = $mform->get_data()) {
    if ($newreport->runable == 'manual' || empty($newreport->singlerow)) {
        $newreport->singlerow = 0;
    }

    if ($id) {
        $newreport->id = $id;
        $ok = update_record('report_customsql_queries', $newreport);
        if (!$ok) {
            print_error('errorupdatingreport', 'report_customsql', report_customsql_url('edit.php?id=' . $id));
        }

    } else {
        $id = insert_record('report_customsql_queries', $newreport);
        if (!$id) {
            print_error('errorinsertingreport', 'report_customsql', report_customsql_url('edit.php'));
        }
    }

    report_customsql_log_edit($id);
    if ($newreport->runable == 'manual') {
        redirect(report_customsql_url('view.php?id=' . $id));
    } else {
        redirect(report_customsql_url('index.php'));
    }

} else {
    admin_externalpage_setup('reportcustomsql');
    admin_externalpage_print_header();
    print_heading(get_string('editingareport', 'report_customsql'));
 
    $mform->display();

    admin_externalpage_print_footer();
 
}

