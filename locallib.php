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

function report_customsql_execute_query($sql, $params = null,
        $limitnum = REPORT_CUSTOMSQL_MAX_RECORDS) {
    global $CFG, $DB;

    $sql = preg_replace('/\bprefix_(?=\w+)/i', $CFG->prefix, $sql);

    // Note: throws Exception if there is an error.
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

/**
 * Extract all the placeholder names from the SQL.
 * @param string $sql The sql.
 * @return array placeholder names
 */
function report_customsql_get_query_placeholders($sql) {
    preg_match_all('/(?<!:):[a-z][a-z0-9_]*/', $sql, $matches);
    return $matches[0];
}

/**
 * Return the type of form field to use for a placeholder, based on its name.
 * @param string $name the placeholder name.
 * @return string a formslib element type, for example 'text' or 'date_time_selector'.
 */
function report_customsql_get_element_type($name) {
    $regex = '/^date|date$/';
    if (preg_match($regex, $name)) {
        return 'date_time_selector';
    }
    return 'text';
}

function report_customsql_generate_csv($report, $timenow) {
    global $DB;
    $starttime = microtime(true);

    $sql = report_customsql_prepare_sql($report, $timenow);

    $queryparams = !empty($report->queryparams) ? unserialize($report->queryparams) : array();
    $querylimit  = !empty($report->querylimit) ? $report->querylimit : REPORT_CUSTOMSQL_MAX_RECORDS;
    $rs = report_customsql_execute_query($sql, $queryparams, $querylimit);

    $csvfilenames = array();
    $csvtimestamp = null;
    foreach ($rs as $row) {
        if (!$csvtimestamp) {
            list($csvfilename, $csvtimestamp) = report_customsql_csv_filename($report, $timenow);
            $csvfilenames[] = $csvfilename;

            if (!file_exists($csvfilename)) {
                $handle = fopen($csvfilename, 'w');
                report_customsql_start_csv($handle, $row, $report->singlerow);
            } else {
                $handle = fopen($csvfilename, 'a');
            }
        }

        $data = get_object_vars($row);
        foreach ($data as $name => $value) {
            if (report_customsql_get_element_type($name) == 'date_time_selector' &&
                    report_customsql_is_integer($value)) {
                $data[$name] = userdate($value, '%F %T');
            }
        }
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

    // Report is runable daily, weekly or monthly.
    if (($report->runable != 'manual') && !empty($report->emailto)) {
        if ($csvfilenames) {
            foreach ($csvfilenames as $csvfilename) {
                report_customsql_email_report($report, $csvfilename);
            }
        } else { // If there is no data.
            report_customsql_email_report($report);
        }
    }
    return $csvtimestamp;
}

/**
 * @param mixed $value some value
 * @return whether $value is an integer, or a string that looks like an integer.
 */
function report_customsql_is_integer($value) {
    return (string) (int) $value === (string) $value;
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
    return $CFG->wwwroot.'/report/customsql/'.$relativeurl;
}

function report_customsql_capability_options() {
    return array(
        'report/customsql:view' => get_string('anyonewhocanveiwthisreport', 'report_customsql'),
        'moodle/site:viewreports' => get_string('userswhocanviewsitereports', 'report_customsql'),
        'moodle/site:config' => get_string('userswhocanconfig', 'report_customsql')
    );
}

function report_customsql_runable_options($type = null) {
    if ($type === 'manual') {
        return array('manual' => get_string('manually', 'report_customsql'));
    }
    return array('manual' => get_string('manually', 'report_customsql'),
                 'daily' => get_string('daily', 'report_customsql'),
                 'weekly' => get_string('automaticallyweekly', 'report_customsql'),
                 'monthly' => get_string('automaticallymonthly', 'report_customsql')
    );
}

function report_customsql_daily_at_options() {
    $time = array();
    for ($h = 0; $h < 24; $h++) {
        $hour = ($h < 10) ? "0$h" : $h;
        $time[$h] = "$hour:00";
    }
    return $time;
}

function report_customsql_email_options() {
    return array('emailnumberofrows' => get_string('emailnumberofrows', 'report_customsql'),
            'emailresults' => get_string('emailresults', 'report_customsql'),
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
               '../report/customsql/'.$relativeurl, $id);
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

    $context = context_system::instance();
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

/**
 * @param int $timenow a timestamp.
 * @param int $at an hour, 0 to 23.
 * @return array with two elements: the timestamp for hour $at today (where today
 *      is defined by $timenow) and the timestamp for hour $at yesterday.
 */
function report_customsql_get_daily_time_starts($timenow, $at) {
    $hours =  $at;
    $minutes = 0;
    $dateparts = getdate($timenow);
    return array(
        mktime((int)$hours, (int)$minutes, 0,
                $dateparts['mon'], $dateparts['mday'], $dateparts['year']),
        mktime((int)$hours, (int)$minutes, 0,
                $dateparts['mon'], $dateparts['mday'] - 1, $dateparts['year']),
        );
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
        case 'daily':
            return report_customsql_get_daily_time_starts($timenow, $report->at);
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

function report_customsql_validate_users($userstring, $capability) {
    global $DB;
    if (empty($userstring)) {
        return null;
    }

    $a = new stdClass();
    $a->capability = $capability;
    $a->whocanaccess = get_string('whocanaccess', 'report_customsql');

    $usernames = preg_split("/[\s,;]+/", $userstring);
    if ($usernames) {
        foreach ($usernames as $username) {
            // Cannot find the user in the database.
            if (!$user = $DB->get_record('user', array('username' => $username))) {
                return get_string('usernotfound', 'report_customsql', $username);
            }
            // User does not have the chosen access level.
            $context = context_user::instance($USER->id);
            $a->username = $username;
            if (!has_capability($capability, $context, $user)) {
                return get_string('userhasnothiscapability', 'report_customsql', $a);
            }
        }
    }
    return null;
}

function report_customsql_get_message_no_data($report) {
    // Construct subject.
    $subject = get_string('emailsubject', 'report_customsql', $report->displayname);
    $url = new moodle_url('/report/customsql/view.php', array('id' => $report->id));
    $link = get_string('emailink', 'report_customsql', html_writer::tag('a', $url, array('href' => $url)));
    $fullmessage = html_writer::tag('p', get_string('nodatareturned', 'report_customsql') . ' ' . $link);
    $fullmessagehtml = $fullmessage;

    // Create the message object.
    $message = new stdClass();
    $message->subject           = $subject;
    $message->fullmessage       = $fullmessage;
    $message->fullmessageformat = FORMAT_HTML;
    $message->fullmessagehtml   = $fullmessagehtml;
    $message->smallmessage      = null;
    return $message;
}

function report_customsql_get_message($report, $csvfilename) {
    $handle = fopen($csvfilename, 'r');
    $table = new html_table();
    $table->head = fgetcsv($handle);
    $countrows = 0;
    while ($row = fgetcsv($handle)) {
        $rowdata = array();
        foreach ($row as $value) {
            $rowdata[] = $value;
        }
        $table->data[] = $rowdata;
        $countrows++;
    }
    fclose($handle);

    // Construct subject.
    $subject = get_string('emailsubject', 'report_customsql', $report->displayname);

    // Construct message without the table.
    $fullmessage = '';
    if (!html_is_blank($report->description)) {
        $fullmessage .=  html_writer::tag('p', format_text($report->description, FORMAT_HTML));
    }

    if ($countrows === 1) {
        $returnrows =  html_writer::tag('span', get_string('emailrow', 'report_customsql', $countrows));
    } else {
        $returnrows =  html_writer::tag('span', get_string('emailrows', 'report_customsql', $countrows));
    }
    $url = new moodle_url('/report/customsql/view.php', array('id' => $report->id));
    $link = get_string('emailink', 'report_customsql', html_writer::tag('a', $url, array('href' => $url)));
    $fullmessage .= html_writer::tag('p', $returnrows . ' ' . $link);

    // Construct message in html.
    $fullmessagehtml = null;
    if ($report->emailwhat === 'emailresults') {
        $fullmessagehtml = html_writer::table($table);
    }
    $fullmessagehtml .= $fullmessage;

    // Create the message object.
    $message = new stdClass();
    $message->subject           = $subject;
    $message->fullmessage       = $fullmessage;
    $message->fullmessageformat = FORMAT_HTML;
    $message->fullmessagehtml   = $fullmessagehtml;
    $message->smallmessage      = null;

    return $message;
}

function report_customsql_email_report($report, $csvfilename = null) {
    global $CFG, $DB, $OUTPUT;

    // If there are no recipients return.
    if (!$report->emailto) {
        return;
    }
    // Get the message.
    if ($csvfilename) {
        $message = report_customsql_get_message($report, $csvfilename);
    } else {
        $message = report_customsql_get_message_no_data($report);
    }

    // Email all recipients.
    $usernames = preg_split("/[\s,;]+/", $report->emailto);
    foreach ($usernames as $username) {
        $recipient = $DB->get_record('user', array('username' => $username), '*', MUST_EXIST);
        $messageid = report_customsql_send_email_notification($recipient, $message);
        if (!$messageid) {
            mtrace(get_string('emailsentfailed', 'report_customsql', fullname($recipient)));
        }
    }
}

function report_customsql_get_list_of_users($str, $inputfield = 'username', $outputfield = 'id') {
    global $DB;
    if (!$userarray = preg_split("/[\s,;]+/", $str)) {
        return null;
    }
    $users = array();
    foreach ($userarray as $user) {
        $users[$user] = $DB->get_field('user', $outputfield, array($inputfield => $user));
    }
    if (!$users) {
        return null;
    }
    return implode(',', $users);
}

function report_customsql_get_ready_to_run_daily_reports($timenow) {
    global $DB;
    $reports = $DB->get_records_select('report_customsql_queries', "runable = ?", array('daily'), 'id');

    $reportstorun = array();
    foreach ($reports as $id => $r) {
        // Check whether the report is ready to run.
        if (!report_customsql_is_daily_report_ready($r, $timenow)) {
            continue;
        }
        $reportstorun[$id] = $r;
    }
    return $reportstorun;
}

/**
 * Sends a notification message to the reciepients.
 *
 * @param object $recepient, the message recipient.
 * @param object $message, the message objectr.
 */
function report_customsql_send_email_notification($recipient, $message) {

    // Prepare the message.
    $eventdata = new stdClass();
    $eventdata->component         = 'report_customsql';
    $eventdata->name              = 'notification';
    $eventdata->notification      = 1;

    $eventdata->userfrom          = get_admin();
    $eventdata->userto            = $recipient;
    $eventdata->subject           = $message->subject;
    $eventdata->fullmessage       = $message->fullmessage;
    $eventdata->fullmessageformat = $message->fullmessageformat;
    $eventdata->fullmessagehtml   = $message->fullmessagehtml;
    $eventdata->smallmessage      = $message->smallmessage;

    return message_send($eventdata);
}

/**
 * Check if the report is ready to run.
 * @param object $report
 * @return boolean
 */
function report_customsql_is_daily_report_ready($report, $timenow) {
    // Time when the report should run today.
    list($runtimetoday) = report_customsql_get_daily_time_starts($timenow, $report->at);

    // Values used to check whether the report has already run today.
    list($today) = report_customsql_get_daily_time_starts($timenow, 0);
    list($lastrunday) = report_customsql_get_daily_time_starts($report->lastrun, 0);

    if (($runtimetoday <= $timenow) && ($today > $lastrunday)) {
        return true;
    }
    return false;
}
