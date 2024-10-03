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

define('NO_OUTPUT_BUFFERING', true);

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once(dirname(__FILE__) . '/view_form.php');
require_once($CFG->libdir . '/adminlib.php');

$id = required_param('id', PARAM_INT);
$urlparams = ['id' => $id];
$report = $DB->get_record('report_customsql_queries', ['id' => $id]);
if (!$report) {
    throw new moodle_exception('invalidreportid', 'report_customsql', report_customsql_url('index.php'), $id);
}

$category = $DB->get_record('report_customsql_categories', ['id' => $report->categoryid], '*', MUST_EXIST);

$embed = optional_param('embed', 0, PARAM_BOOL);
$urlparams['embed'] = $embed;

// Setup the page.
admin_externalpage_setup('report_customsql', '', $urlparams,
        '/report/customsql/view.php', ['pagelayout' => 'report']);
$PAGE->set_title(format_string($report->displayname));
$PAGE->navbar->add(format_string($category->name), report_customsql_url('category.php', ['id' => $report->categoryid]));
$PAGE->navbar->add(format_string($report->displayname));

if ($embed) {
    $PAGE->set_pagelayout('embedded');
}

$output = $PAGE->get_renderer('report_customsql');

$context = context_system::instance();
if (!empty($report->capability)) {
    require_capability($report->capability, $context);
}

report_customsql_log_view($id);

// We don't want slow reports blocking the session in other tabs.
\core\session\manager::write_close();

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
            echo $OUTPUT->header();
            echo $OUTPUT->heading(format_string($report->displayname));
            if (!html_is_blank($report->description)) {
                echo html_writer::tag('p', format_text($report->description, FORMAT_HTML));
            }
            $mform->display();

            echo $output->render_report_actions($report, $category, $context);

            echo $OUTPUT->footer();
            die;
        }
    }

    try {
        $csvtimestamp = report_customsql_generate_csv($report, time());
        // Get the updated execution times.
        $report = $DB->get_record('report_customsql_queries', ['id' => $id]);
    } catch (Exception $e) {
        throw new moodle_exception('queryfailed', 'report_customsql', report_customsql_url('index.php'),
                    $e->getMessage());
    }
} else {
    // Runs on schedule.
    $csvtimestamp = optional_param('timestamp', null, PARAM_INT);
    if ($csvtimestamp === null) {
        [$csvtimestamp] = report_customsql_get_starts($report, time());
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
                ['name' => html_writer::tag('b', str_replace('_', ' ', $name)),
                'value' => s($value)]));
    }
}

$count = 0;
if (is_null($csvtimestamp)) {
    echo html_writer::tag('p', get_string('nodatareturned', 'report_customsql'));
} else {
    list($csvfilename, $csvtimestamp) = report_customsql_csv_filename($report, $csvtimestamp);
    if (!is_readable($csvfilename)) {
        if (empty($report->lastrun) || $csvtimestamp > $report->lastrun) {
            echo html_writer::tag('p', get_string('notrunyet', 'report_customsql'));
        } else {
            echo html_writer::tag('p', get_string('notanyresults', 'report_customsql', userdate($csvtimestamp)));
        }
    } else {
        $handle = fopen($csvfilename, 'r');

        if ($report->runable != 'manual' && !$report->singlerow) {
            echo $OUTPUT->heading(get_string('reportfor', 'report_customsql',
                    userdate($csvtimestamp)), 3);
        }

        $table = new html_table();
        $table->id = 'report_customsql_results';
        list($table->head, $linkcolumns) = report_customsql_get_table_headers(
                report_customsql_read_csv_row($handle));

        $rowlimitexceeded = false;
        while ($row = report_customsql_read_csv_row($handle)) {
            $data = report_customsql_display_row($row, $linkcolumns);
            if (isset($data[0]) && $data[0] === REPORT_CUSTOMSQL_LIMIT_EXCEEDED_MARKER) {
                $rowlimitexceeded = true;
            } else {
                $table->data[] = $data;
                $count += 1;
            }
        }

        // For scheduled reports that accumulate one row at a time,
        // show most recent data first.
        if ($report->runable != 'manual' && $report->singlerow) {
            $table->data = array_reverse($table->data);
        }

        fclose($handle);
        echo html_writer::table($table);

        if ($rowlimitexceeded) {
            echo html_writer::tag('p', get_string('recordlimitreached', 'report_customsql',
            !empty($report->querylimit) ? $report->querylimit : get_config('report_customsql', 'querylimitdefault')),
                    ['class' => 'admin_note']);
        } else {
            echo html_writer::tag('p', get_string('recordcount', 'report_customsql', $count),
                    ['class' => 'admin_note']);
        }

        echo report_customsql_time_note($report, 'p');

        $urlparams = [];
        if (!empty($paramvalues)) {
            $urlparams = $paramvalues;
        }
        $urlparams['timestamp'] = $csvtimestamp;
        $downloadurl = report_customsql_downloadurl($id, $urlparams);
        echo $OUTPUT->download_dataformat_selector(get_string('downloadthisreportas', 'report_customsql'),
            $downloadurl, 'dataformat', $urlparams);
    }
}

if (!empty($queryparams)) {
    echo html_writer::tag('p',
            $OUTPUT->action_link(
                    report_customsql_url('view.php', ['id' => $id]),
                    $OUTPUT->pix_icon('t/editstring', '') . ' ' .
                    get_string('changetheparameters', 'report_customsql')));
}

echo $output->render_report_actions($report, $category, $context);

if ($report->runable != 'manual') {
    echo $OUTPUT->heading(get_string('archivedversions', 'report_customsql'), 3);

    $archivetimes = report_customsql_get_archive_times($report);
    if (!$archivetimes) {
        echo html_writer::tag('p', get_string('notrunyet', 'report_customsql'));

    } else {
        echo html_writer::start_tag('ul');
        foreach ($archivetimes as $time) {
            $formattedtime = userdate($time);
            echo html_writer::start_tag('li');
            if ($time == $csvtimestamp) {
                echo html_writer::tag('b', $formattedtime);
            } else {
                echo html_writer::tag('a', $formattedtime,
                        ['href' => report_customsql_url('view.php',
                                ['id' => $id, 'timestamp' => $time])]);
            }
            echo '</li>';
        }
        echo html_writer::end_tag('ul');
    }
}

echo $OUTPUT->footer();
