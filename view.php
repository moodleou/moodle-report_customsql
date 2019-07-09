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
 * Script to view a particular custom SQL report.
 *
 * @package report_customsql
 * @copyright 2009 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once(dirname(__FILE__) . '/view_form.php');
require_once($CFG->libdir . '/adminlib.php');

$id = required_param('id', PARAM_INT);
$urlparams = ['id' => $id];
$report = $DB->get_record('report_customsql_queries', array('id' => $id));
if (!$report) {
    print_error('invalidreportid', 'report_customsql', report_customsql_url('index.php'), $id);
}

$embed = optional_param('embed', 0, PARAM_BOOL);
$urlparams['embed'] = $embed;

// Setup the page.
admin_externalpage_setup('report_customsql', '', $urlparams,
        '/report/customsql/view.php', ['pagelayout' => 'report']);
$PAGE->set_title(format_string($report->displayname));
$PAGE->navbar->add(format_string($report->displayname));

if ($embed) {
    $PAGE->set_pagelayout('embedded');
}

$context = context_system::instance();
if (!empty($report->capability)) {
    require_capability($report->capability, $context);
}

report_customsql_log_view($id);

if ($report->runable == 'manual') {

    // Allow query parameters to be entered.
    if (!empty($report->queryparams)) {
        $queryparams = report_customsql_get_query_placeholders_and_field_names($report->querysql);

        // Get any query param values that are given in the URL.
        $paramvalues = [];
        foreach ($queryparams as $queryparam => $notused) {
            $value = optional_param($queryparam, null, PARAM_RAW);
            if ($value !== null && $value !== '') {
                $paramvalues[$queryparam] = $value;
            }
        }

        $relativeurl = 'view.php?id=' . $id;
        $mform = new report_customsql_view_form(report_customsql_url($relativeurl), $queryparams);
        $formdefaults = [];
        if ($report->queryparams) {
            foreach (unserialize($report->queryparams) as $queryparam => $defaultvalue) {
                $formdefaults[$queryparams[$queryparam]] = $defaultvalue;
            }
        }
        foreach ($paramvalues as $queryparam => $value) {
            $formdefaults[$queryparams[$queryparam]] = $value;
        }
        $mform->set_data($formdefaults);

        if ($mform->is_cancelled()) {
            redirect(report_customsql_url('index.php'));
        }

        if (($newreport = $mform->get_data()) || count($paramvalues) == count($queryparams)) {

            // Pick up named parameters into serialised array.
            if ($newreport) {
                foreach ($queryparams as $queryparam => $formparam) {
                    $paramvalues[$queryparam] = $newreport->{$formparam};
                }
            }
            $report->queryparams = serialize($paramvalues);

        } else {

            admin_externalpage_setup('report_customsql', '', $urlparams,
                    '/report/customsql/view.php');
            $PAGE->set_title(format_string($report->displayname));
            $PAGE->navbar->add(format_string($report->displayname));
            echo $OUTPUT->header();
            echo $OUTPUT->heading(format_string($report->displayname));
            if (!html_is_blank($report->description)) {
                echo html_writer::tag('p', format_text($report->description, FORMAT_HTML));
            }
            $mform->display();

            echo $OUTPUT->footer();
            die;
        }
    }

    try {
        $csvtimestamp = report_customsql_generate_csv($report, time());
        // Get the updated execution times.
        $report = $DB->get_record('report_customsql_queries', array('id' => $id));
    } catch (Exception $e) {
        print_error('queryfailed', 'report_customsql', report_customsql_url('index.php'),
                    $e->getMessage());
    }
} else {
    // Runs on schedule.
    $csvtimestamp = optional_param('timestamp', null, PARAM_INT);
    if ($csvtimestamp === null) {
        $archivetimes = report_customsql_get_archive_times($report);
        $csvtimestamp = array_shift($archivetimes);
    }
    if ($csvtimestamp === null) {
        $csvtimestamp = time();
    }
    $urlparams['timestamp'] = $csvtimestamp;
}

// Output.
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($report->displayname));

if (!html_is_blank($report->description)) {
    echo html_writer::tag('p', format_text($report->description, FORMAT_HTML));
}

if (!empty($paramvalues)) {
    foreach ($paramvalues as $name => $value) {
        if (report_customsql_get_element_type($name) == 'date_time_selector') {
            $value = userdate($value, '%F %T');
        }
        echo html_writer::tag('p', get_string('parametervalue', 'report_customsql',
                array('name' => html_writer::tag('b', str_replace('_', ' ', $name)),
                'value' => s($value))));
    }
}

$count = 0;
if (is_null($csvtimestamp)) {
    echo html_writer::tag('p', get_string('nodatareturned', 'report_customsql'));
} else {
    list($csvfilename, $csvtimestamp) = report_customsql_csv_filename($report, $csvtimestamp);
    if (!is_readable($csvfilename)) {
        echo html_writer::tag('p', get_string('notrunyet', 'report_customsql'));
    } else {
        $handle = fopen($csvfilename, 'r');

        if ($report->runable != 'manual' && !$report->singlerow) {
            echo $OUTPUT->heading(get_string('reportfor', 'report_customsql',
                    userdate($csvtimestamp, get_string('strftimedate'))), 3);
        }

        $table = new html_table();
        $table->id = 'report_customsql_results';
        list($table->head, $linkcolumns) = report_customsql_get_table_headers(fgetcsv($handle));

        while ($row = fgetcsv($handle)) {
            $table->data[] = report_customsql_display_row($row, $linkcolumns);
            $count += 1;
        }

        fclose($handle);
        echo html_writer::table($table);

        if ($count >= REPORT_CUSTOMSQL_MAX_RECORDS) {
            echo html_writer::tag('p', get_string('recordlimitreached', 'report_customsql',
                                                  REPORT_CUSTOMSQL_MAX_RECORDS),
                                                  array('class' => 'admin_note'));
        } else {
            echo html_writer::tag('p', get_string('recordcount', 'report_customsql', $count),
                    array('class' => 'admin_note'));
        }

        echo report_customsql_time_note($report, 'p').
             html_writer::start_tag('p').
             html_writer::tag('a', get_string('downloadthisreportascsv', 'report_customsql'),
                              array('href' => new moodle_url(report_customsql_url('download.php'),
                              array('id' => $id, 'timestamp' => $csvtimestamp)))).
             html_writer::end_tag('p');

        $archivetimes = report_customsql_get_archive_times($report);
        if (count($archivetimes) > 1) {
            echo $OUTPUT->heading(get_string('archivedversions', 'report_customsql'), 3).
                 html_writer::start_tag('ul');
            foreach ($archivetimes as $time) {
                $formattedtime = userdate($time, get_string('strftimedate'));
                echo html_writer::start_tag('li');
                if ($time == $csvtimestamp) {
                    echo html_writer::tag('b', $formattedtime);
                } else {
                    echo html_writer::tag('a', $formattedtime,
                                array('href' => new moodle_url(report_customsql_url('view.php'),
                                array('id' => $id, 'timestamp' => $time))));
                }
                echo '</li>';
            }
            echo html_writer::end_tag('ul');
        }
    }
}

if (!empty($queryparams)) {
    echo html_writer::tag('p', html_writer::link(
            new moodle_url(report_customsql_url('view.php'), array('id' => $id)),
            get_string('changetheparameters', 'report_customsql')));
}

if (has_capability('report/customsql:definequeries', $context)) {
    $imgedit = $OUTPUT->pix_icon('t/edit', get_string('edit'));
    $imgdelete = $OUTPUT->pix_icon('t/delete', get_string('delete'));
    echo html_writer::start_tag('p').
         $OUTPUT->action_link(new moodle_url(report_customsql_url('edit.php'),
                 array('id' => $id)), $imgedit.' '.
                 get_string('editreportx', 'report_customsql', format_string($report->displayname))).
         html_writer::end_tag('p').
         html_writer::start_tag('p').
         $OUTPUT->action_link(new moodle_url(report_customsql_url('delete.php'), ['id' => $id]),
                 $imgdelete . ' ' . get_string('deletereportx', 'report_customsql', format_string($report->displayname))) .
         html_writer::end_tag('p');
}

$imglarrow = $OUTPUT->pix_icon('t/left', '');
echo html_writer::start_tag('p').
     $OUTPUT->action_link(new moodle_url(report_customsql_url('index.php')), $imglarrow.
             get_string('backtoreportlist', 'report_customsql')).
     html_writer::end_tag('p').
     $OUTPUT->footer();
