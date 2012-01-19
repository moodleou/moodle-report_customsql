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


require_once(dirname(__FILE__) . '/../../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/validateurlsyntax.php');

$id = required_param('id', PARAM_INT);
$report = get_record('report_customsql_queries', 'id', $id);
if (!$report) {
    print_error('invalidreportid', 'report_customsql', report_customsql_url('index.php'), $id);
}

require_login();
$context = get_context_instance(CONTEXT_SYSTEM);
if (!empty($report->capability)) {
    require_capability($report->capability, $context);
}

report_customsql_log_view($id);

if ($report->runable == 'manual') {
    try {
        $cvstimestamp = report_customsql_generate_csv($report, time());
        // Get the updated execution times.
        $report = get_record('report_customsql_queries', 'id', $id);
    } catch (Exception $e) {
        print_error('queryfailed', 'report_customsql', report_customsql_url('index.php'), $e);
    }
} else {
    $cvstimestamp = optional_param('timestamp', time(), PARAM_INT);
}

// Start the page.
admin_externalpage_setup('reportcustomsql');
admin_externalpage_print_header();
print_heading(format_string($report->displayname));

if (!html_is_blank($report->description)) {
    echo '<p>' . format_text($report->description, FORMAT_HTML) . '</p>';
}

$count = 0;
if (is_null($cvstimestamp)) {
    echo '<p>' . get_string('nodatareturned', 'report_customsql') . '</p>';

} else {
    list($csvfilename, $cvstimestamp) = report_customsql_csv_filename($report, $cvstimestamp);
    if (!is_readable($csvfilename)) {
        echo '<p>' . get_string('notrunyet', 'report_customsql') . '</p>';

    } else {
        $handle = fopen($csvfilename, 'r');

        if ($report->runable != 'manual' && !$report->singlerow) {
            print_heading(get_string('reportfor', 'report_customsql',
                    userdate($cvstimestamp, get_string('strftimedate'))), '', 3);
        }

        $table = new stdClass;
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
        print_table($table);

        if ($count >= REPORT_CUSTOMSQL_MAX_RECORDS) {
            echo '<p class="admin_note">' . get_string('recordlimitreached', 'report_customsql', REPORT_CUSTOMSQL_MAX_RECORDS) . '</p>';
        }
        echo report_customsql_time_note($report, 'p');
        echo '<p><a href="' . report_customsql_url('download.php?id=' . $id . '&amp;timestamp=' . $cvstimestamp) .
                '">' . get_string('downloadthisreportascsv', 'report_customsql') . '</a></p>';

        $archivetimes = report_customsql_get_archive_times($report);
        if (count($archivetimes) > 1) {
            print_heading(get_string('archivedversions', 'report_customsql'), '', 3);
            echo '<ul>';
            foreach ($archivetimes as $time) {
                $formattedtime = userdate($time, get_string('strftimedate'));
                echo '<li>';
                if ($time == $cvstimestamp) {
                    echo '<b>' . $formattedtime . '</b>';
                } else {
                    echo '<a href="' . report_customsql_url('view.php?id=' . $id . '&amp;timestamp=' . $time) .
                            '">' . $formattedtime . '</a>';
                }
                echo '</li>';
            }
            echo '</ul>';
        }
    }
}

if (has_capability('report/customsql:definequeries', $context)) {
    echo '<p><img src="' . $CFG->pixpath . '/t/edit.gif" class="iconsmall" alt="' .
            get_string('edit') . '" /> <a href="' . report_customsql_url('edit.php?id=' . $id) .
            '">' . get_string('editthisreport', 'report_customsql') . '</a></p>';
    echo '<p><img src="' . $CFG->pixpath . '/t/delete.gif" class="iconsmall" alt="' .
            get_string('delete') . '" /> <a href="' . report_customsql_url('delete.php?id=' . $id) .
            '">' . get_string('deletethisreport', 'report_customsql') . '</a></p>';
}

echo '<p>' . $THEME->larrow . ' ' . '<a href="' . report_customsql_url('index.php') .
        '">' . get_string('backtoreportlist', 'report_customsql') . '</a></p>';

admin_externalpage_print_footer();