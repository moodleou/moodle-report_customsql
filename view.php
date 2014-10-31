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
require_once($CFG->libdir . '/validateurlsyntax.php');

$id = required_param('id', PARAM_INT);
$report = $DB->get_record('report_customsql_queries', array('id' => $id));
if (!$report) {
    print_error('invalidreportid', 'report_customsql', report_customsql_url('index.php'), $id);
}

require_login();
$context = context_system::instance();
if (!empty($report->capability)) {
    require_capability($report->capability, $context);
}

report_customsql_log_action('viewed', $id);

if ($report->runable == 'manual') {

    // Allow query parameters to be entered.
    if (!empty($report->queryparams)) {

        $queryparams = array();
        foreach (report_customsql_get_query_placeholders($report->querysql) as $queryparam) {
            $queryparams[substr($queryparam, 1)] = 'queryparam'.substr($queryparam, 1);
        }

        $PAGE->set_url(new moodle_url('/report/customsql/view.php'));
        $PAGE->set_context($context);
        $PAGE->set_title($report->displayname);
        $PAGE->navbar->add($report->displayname);
        $relativeurl = 'view.php?id=' . $id;
        $mform = new report_customsql_view_form(report_customsql_url($relativeurl), $queryparams);

        if ($mform->is_cancelled()) {
            redirect(report_customsql_url('index.php'));
        }

        if ($newreport = $mform->get_data()) {

            // Pick up named parameters into serialised array.
            if ($queryparams) {
                foreach ($queryparams as $queryparam => $formparam) {
                    $queryparams[$queryparam] = $newreport->{$formparam};
                    unset($newreport->{$formparam});
                }
                $report->queryparams = serialize($queryparams);
            }
        } else {

            admin_externalpage_setup('report_customsql');
            $PAGE->set_title($report->displayname);
            $PAGE->navbar->add($report->displayname);
            echo $OUTPUT->header();
            echo $OUTPUT->heading(format_string($report->displayname));
            if (!html_is_blank($report->description)) {
                echo html_writer::tag('p', format_text($report->description, FORMAT_HTML));
            }

            $report->description = strip_tags($report->description);
            $queryparams = unserialize($report->queryparams);
            foreach ($queryparams as $param => $value) {
                $report->{'queryparam'.$param} = $value;
            }
            $mform->set_data($report);
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
    $csvtimestamp = optional_param('timestamp', time(), PARAM_INT);
}

// Start the page.
admin_externalpage_setup('report_customsql');
$PAGE->set_title($report->displayname);
$PAGE->navbar->add($report->displayname);
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($report->displayname));

if (!html_is_blank($report->description)) {
    echo html_writer::tag('p', format_text($report->description, FORMAT_HTML));
}

if (!empty($queryparams)) {
    foreach ($queryparams as $name => $value) {
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
        $table->head = fgetcsv($handle);

        while ($row = fgetcsv($handle)) {
            $rowdata = array();
            foreach ($row as $value) {
                if (validateUrlSyntax($value, 's+H?S?F?E?u-P-a?I?p?f?q?r?')) {
                    $rowdata[] = '<a href="' . $value . '">' . $value . '</a>';
                } else {
                    $rowdata[] = $value;
                }
            }
            $table->data[] = $rowdata;
            $count += 1;
        }

        fclose($handle);
        echo html_writer::table($table);

        if ($count >= REPORT_CUSTOMSQL_MAX_RECORDS) {
            echo html_writer::tag('p', get_string('recordlimitreached', 'report_customsql',
                                                  REPORT_CUSTOMSQL_MAX_RECORDS),
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
    $imgedit = html_writer::tag('img', '', array('src' => $OUTPUT->pix_url('t/edit'),
                                'class' => 'iconsmall',
                                'alt' => get_string('edit')));
    $imgdelete = html_writer::tag('img', '', array('src' => $OUTPUT->pix_url('t/delete'),
                                  'class' => 'iconsmall',
                                  'alt' => get_string('delete')));
    echo html_writer::start_tag('p').
         $OUTPUT->action_link(new moodle_url(report_customsql_url('edit.php'),
                                             array('id' => $id)), $imgedit.' '.
                                             get_string('editthisreport', 'report_customsql')).
         html_writer::end_tag('p').
         html_writer::start_tag('p').
         $OUTPUT->action_link(new moodle_url(report_customsql_url('delete.php'),
                                             array('id' => $id)), $imgdelete.' '.
                                             get_string('deletethisreport', 'report_customsql')).
         html_writer::end_tag('p');
}

$imglarrow = html_writer::tag('img', '', array('src' => $OUTPUT->pix_url('t/collapsed_rtl'),
                              'class' => 'iconsmall',
                              'alt' => ''));
echo html_writer::start_tag('p').
     $OUTPUT->action_link(new moodle_url(report_customsql_url('index.php')), $imglarrow.
                                         get_string('backtoreportlist', 'report_customsql')).
     html_writer::end_tag('p').
     $OUTPUT->footer();
