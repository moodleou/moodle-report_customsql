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
 * Cron script for the custom SQL report.
 *
 * @package report_customsql
 * @copyright 2009 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/locallib.php');

/**
 * Cron that runs any automatically scheduled reports weekly or monthly.
 */
function report_customsql_cron() {
    global $CFG, $DB;

    $timenow = time();
    if (!empty($CFG->reportcustomsqlmaxruntime)) {
        $timestop = $timenow + $CFG->reportcustomsqlmaxruntime;
    } else {
        $timestop = $timenow + 180; // Three minutes.
    }

    list($startofthisweek, $startoflastweek) = report_customsql_get_week_starts($timenow);
    list($startofthismonth) = report_customsql_get_month_starts($timenow);

    mtrace("... Looking for old temp CSV files to delete.");
    $numdeleted = report_customsql_delete_old_temp_files($startoflastweek);
    if ($numdeleted) {
        mtrace("... $numdeleted old temporary files deleted.");
    }

    // Get daily scheduled reports.
    $dailyreportstorun = report_customsql_get_ready_to_run_daily_reports($timenow);

    // Get weekly and monthly scheduled reports.
    $scheduledreportstorun = $DB->get_records_select('report_customsql_queries',
                                        "(runable = 'weekly' AND lastrun < :startofthisweek) OR
                                         (runable = 'monthly' AND lastrun < :startofthismonth)",
                                        array('startofthisweek' => $startofthisweek,
                                              'startofthismonth' => $startofthismonth), 'lastrun');
    // All reports ready to run.
    $reportstorun = array_merge($dailyreportstorun, $scheduledreportstorun);

    if (empty($reportstorun)) {
        return;
    }

    while (!empty($reportstorun) && time() < $timestop) {
        $report = array_shift($reportstorun);
        mtrace("... Running report " . strip_tags($report->displayname));
        try {
            report_customsql_generate_csv($report, $timenow);
        } catch (Exception $e) {
            mtrace("... REPORT FAILED " . $e->getMessage());
        }
    }
}
