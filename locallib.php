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
 * Library code for the custom SQL report.
 *
 * @package report_customsql
 * @copyright 2009 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('REPORT_CUSTOMSQL_MAX_RECORDS', 5000);
define('REPORT_CUSTOMSQL_START_OF_WEEK', 6); // Saturday.

function report_customsql_execute_query($sql, $params = null, $limitnum = REPORT_CUSTOMSQL_MAX_RECORDS) {
    global $CFG, $DB;

    $sql = preg_replace('/\bprefix_(?=\w+)/i', $CFG->prefix, $sql);
    // Note: throws Exception if there is an error
    return $DB->get_recordset_sql($sql, $params, 0, $limitnum);
}

function report_customsql_prepare_sql($report, $timenow) {
    global $USER;
    $sql = $report->querysql;
    if ($report->runable != 'manual') {
        list($end, $start) = report_customsql_get_starts($report, $timenow);
        $sql = report_customsql_substitute_time_tokens($sql, $start, $end);
    }
    $sql = report_customsql_substitute_user_token($sql, $USER->id);
    return $sql;
}

function report_customsql_generate_csv($report, $timenow) {
    global $DB;
    $starttime = microtime(true);

    $sql = report_customsql_prepare_sql($report, $timenow);

    $queryparams = !empty($report->queryparams) ? unserialize($report->queryparams) : array();
    $rs = report_customsql_execute_query($sql, $queryparams);

    $cvstimestamp = null;
    foreach ($rs as $row) {
        if (!$cvstimestamp) {
            list($csvfilename, $cvstimestamp) = report_customsql_csv_filename($report, $timenow);

            if (!file_exists($csvfilename)) {
                $handle = fopen($csvfilename, 'w');
                report_customsql_start_csv($handle, $row, $report->singlerow);
            } else {
                $handle = fopen($csvfilename, 'a');
            }
        }

        $data = get_object_vars($row);
        if ($report->singlerow) {
            array_unshift($data, strftime('%Y-%m-%d', $timenow));
        }
        report_customsql_write_csv_row($handle, $data);
    }
    $rs->close();

    if (!empty($handle)) {
        fclose($handle);
    }

    // Update the execution time in the DB.
    $updaterecord = new stdClass;
    $updaterecord->id = $report->id;
    $updaterecord->lastrun = time();
    $updaterecord->lastexecutiontime = round((microtime(true) - $starttime) * 1000);
    $DB->update_record('report_customsql_queries', $updaterecord);

    return $cvstimestamp;
}

function report_customsql_csv_filename($report, $timenow) {
    if ($report->runable == 'manual') {
        return report_customsql_temp_cvs_name($report->id, $timenow);

    } else if ($report->singlerow) {
        return report_customsql_accumulating_cvs_name($report->id);

    } else {
        list($timestart) = report_customsql_get_starts($report, $timenow);
        return report_customsql_scheduled_cvs_name($report->id, $timestart);
    }
}

function report_customsql_temp_cvs_name($reportid, $timestamp) {
    global $CFG;
    $path = 'admin_report_customsql/temp/'.$reportid;
    make_upload_directory($path);
    return array($CFG->dataroot.'/'.$path.'/'.strftime('%Y%m%d-%H%M%S', $timestamp).'.csv',
                 $timestamp);
}

function report_customsql_scheduled_cvs_name($reportid, $timestart) {
    global $CFG;
    $path = 'admin_report_customsql/'.$reportid;
    make_upload_directory($path);
    return array($CFG->dataroot.'/'.$path.'/'.strftime('%Y%m%d-%H%M%S', $timestart).'.csv',
                 $timestart);
}

function report_customsql_accumulating_cvs_name($reportid) {
    global $CFG;
    $path = 'admin_report_customsql/'.$reportid;
    make_upload_directory($path);
    return array($CFG->dataroot.'/'.$path.'/accumulate.csv', 0);
}

function report_customsql_get_archive_times($report) {
    global $CFG;
    if ($report->runable == 'manual' || $report->singlerow) {
        return array();
    }
    $files = glob($CFG->dataroot.'/admin_report_customsql/'.$report->id.'/*.csv');
    $archivetimes = array();
    foreach ($files as $file) {
        if (preg_match('|/(\d\d\d\d)(\d\d)(\d\d)-(\d\d)(\d\d)(\d\d)\.csv$|', $file, $matches)) {
            $archivetimes[] = mktime($matches[4], $matches[5], $matches[6], $matches[2],
                                     $matches[3], $matches[1]);
        }
    }
    rsort($archivetimes);
    return $archivetimes;
}

function report_customsql_substitute_time_tokens($sql, $start, $end) {
    return str_replace(array('%%STARTTIME%%', '%%ENDTIME%%'), array($start, $end), $sql);
}

function report_customsql_substitute_user_token($sql, $userid) {
    return str_replace('%%USERID%%', $userid, $sql);
}

function report_customsql_url($relativeurl) {
    global $CFG;
    return $CFG->wwwroot.'/'.$CFG->admin.'/report/customsql/'.$relativeurl;
}

function report_customsql_capability_options() {
    return array(
        'report/customsql:view' => get_string('anyonewhocanveiwthisreport', 'report_customsql'),
        'moodle/site:viewreports' => get_string('userswhocanviewsitereports', 'report_customsql'),
        'moodle/site:config' => get_string('userswhocanconfig', 'report_customsql')
    );
}

function report_customsql_runable_options() {
    return array('manual' => get_string('manually', 'report_customsql'),
                 'weekly' => get_string('automaticallyweekly', 'report_customsql'),
                 'monthly' => get_string('automaticallymonthly', 'report_customsql')
    );
}

function report_customsql_bad_words_list() {
    return array('ALTER', 'CREATE', 'DELETE', 'DROP', 'GRANT', 'INSERT', 'INTO',
                 'TRUNCATE', 'UPDATE');
}

function report_customsql_contains_bad_word($string) {
    return preg_match('/\b('.implode('|', report_customsql_bad_words_list()).')\b/i', $string);
}

function report_customsql_log_action($action, $relativeurl, $id) {
    global $CFG;
    add_to_log(0, 'admin', $action.' query',
               '../'.$CFG->admin.'/report/customsql/'.$relativeurl, $id);
}

function report_customsql_log_delete($id) {
    report_customsql_log_action('delete', 'index.php', $id);
}

function report_customsql_log_edit($id) {
    report_customsql_log_action('edit', 'view.php?id='.$id, $id);
}

function report_customsql_log_view($id) {
    report_customsql_log_action('view', 'view.php?id='.$id, $id);
}

function report_customsql_print_reports($reports) {
    global $CFG, $OUTPUT;

    $context = get_context_instance(CONTEXT_SYSTEM);
    $canedit = has_capability('report/customsql:definequeries', $context);
    $capabilities = report_customsql_capability_options();
    foreach ($reports as $report) {
        if (!empty($report->capability) && !has_capability($report->capability, $context)) {
            continue;
        }

        echo html_writer::start_tag('p');
        echo html_writer::tag('a', format_string($report->displayname),
                              array('href' => report_customsql_url('view.php?id='.$report->id))).
             ' '.report_customsql_time_note($report, 'span');
        if ($canedit) {
            $imgedit = html_writer::tag('img', '', array('src' => $OUTPUT->pix_url('t/edit'),
                                                         'class' => 'iconsmall',
                                                         'alt' => get_string('edit')));
            $imgdelete = html_writer::tag('img', '', array('src' => $OUTPUT->pix_url('t/delete'),
                                                           'class' => 'iconsmall',
                                                           'alt' => get_string('delete')));
            echo ' '.html_writer::tag('span', get_string('availableto', 'report_customsql',
                                      $capabilities[$report->capability]),
                                      array('class' => 'admin_note')).' '.
                 html_writer::tag('a', $imgedit,
                            array('title' => get_string('editthisreport', 'report_customsql'),
                                  'href' => report_customsql_url('edit.php?id='.$report->id))).' '.
                 html_writer::tag('a', $imgdelete,
                            array('title' => get_string('deletethisreport', 'report_customsql'),
                                  'href' => report_customsql_url('delete.php?id='.$report->id)));
        }
        echo html_writer::end_tag('p');
        echo "\n";
    }
}

function report_customsql_time_note($report, $tag) {
    if ($report->lastrun) {
        $a = new stdClass;
        $a->lastrun = userdate($report->lastrun);
        $a->lastexecutiontime = $report->lastexecutiontime / 1000;
        $note = get_string('lastexecuted', 'report_customsql', $a);

    } else {
        $note = get_string('notrunyet', 'report_customsql');
    }

    return html_writer::tag($tag, $note, array('class'=> 'admin_note'));
}

function report_customsql_pretify_column_names($row) {
    $colnames = array();
    foreach (get_object_vars($row) as $colname => $ignored) {
        $colnames[] = str_replace('_', ' ', $colname);
    }
    return $colnames;
}

function report_customsql_write_csv_row($handle, $data) {
    global $CFG;
    $escapeddata = array();
    foreach ($data as $value) {
        $value = str_replace('%%WWWROOT%%', $CFG->wwwroot, $value);
        $escapeddata[] = '"'.str_replace('"', '""', $value).'"';
    }
    fwrite($handle, implode(',', $escapeddata)."\r\n");
}

function report_customsql_start_csv($handle, $firstrow, $datecol) {
    $colnames = report_customsql_pretify_column_names($firstrow);
    if ($datecol) {
        array_unshift($colnames, get_string('queryrundate', 'report_customsql'));
    }
    report_customsql_write_csv_row($handle, $colnames);
}

function report_customsql_get_week_starts($timenow) {
    $dateparts = getdate($timenow);

    $daysafterweekstart = ($dateparts['wday'] - REPORT_CUSTOMSQL_START_OF_WEEK + 7) % 7;

    return array(
        mktime(0, 0, 0, $dateparts['mon'], $dateparts['mday'] - $daysafterweekstart,
               $dateparts['year']),
        mktime(0, 0, 0, $dateparts['mon'], $dateparts['mday'] - $daysafterweekstart - 7,
               $dateparts['year']),
    );
}

function report_customsql_get_month_starts($timenow) {
    $dateparts = getdate($timenow);

    return array(
        mktime(0, 0, 0, $dateparts['mon'], 1, $dateparts['year']),
        mktime(0, 0, 0, $dateparts['mon'] - 1, 1, $dateparts['year']),
    );
}

function report_customsql_get_starts($report, $timenow) {
    switch ($report->runable) {
        case 'weekly':
            return report_customsql_get_week_starts($timenow);
        case 'monthly':
            return report_customsql_get_month_starts($timenow);
        default:
            throw new Exception('unexpected $report->runable.');
    }
}

function report_customsql_delete_old_temp_files($upto) {
    global $CFG;

    $count = 0;
    $comparison = strftime('%Y%m%d-%H%M%S', $upto).'csv';

    $files = glob($CFG->dataroot.'/admin_report_customsql/temp/*/*.csv');
    if (empty($files)) {
        return;
    }
    foreach ($files as $file) {
        if (basename($file) < $comparison) {
            unlink($file);
            $count += 1;
        }
    }

    return $count;
}
