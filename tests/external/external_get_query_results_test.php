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


namespace report_customsql\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');


/**
 * Tests for the get_query_results web service.
 *
 * @package   report_customsql
 * @category  external
 * @author    Oscar Nadjar <oscar.nadjar@moodle.com>
 * @copyright 2023 Moodle Us
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \report_customsql\external\get_query_results
 * @runTestsInSeparateProcesses
 */
class external_get_query_results_test extends \externallib_advanced_testcase {

    protected function setUp(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    public function test_get_query_results(): void {

        global $DB;

        $displayname = 'test';
        $description = 'test';
        $querysql = 'SELECT * FROM {user}';
        $queryparams = '';
        $querylimit = 5000;
        $capability = 'moodle/site:config';
        $runable = 'manual';
        $at = '';
        $emailto = 'test@mail.com';
        $emailwhat = 'Test email';
        $categoryid = 1;
        $customdir = '';

        $result = create_query::execute(
            $displayname, $description, $querysql, $queryparams, $querylimit,
                $capability, $runable, $at, $emailto, $emailwhat, $categoryid, $customdir);
        $result = \external_api::clean_returnvalue(create_query::execute_returns(), $result);

        $report = $DB->get_record('report_customsql_queries', ['id' => $result['queryid']]);
        $csvtimestamp = report_customsql_generate_csv($report, time());
        $result = get_query_results::execute($report->id, 'csv');
        $result = \external_api::clean_returnvalue(get_query_results::execute_returns(), $result);
        $result = reset($result['results']);

        $date = date('Y-m-d H:i:s', $csvtimestamp);
        $this->assertEquals($date, $result['date']);

        $url = new \moodle_url('/webservice/pluginfile.php/' .
            \context_system::instance()->id . '/report_customsql/download/' . $report->id . '/');
        $donwloadurl = new \moodle_url($url, ['dataformat' => 'csv', 'timestamp' => $csvtimestamp]);
        $this->assertEquals($donwloadurl->out(false), $result['downloadurl']);
    }
}
