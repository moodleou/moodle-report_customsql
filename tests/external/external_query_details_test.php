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
 * Tests for the query_details web service.
 *
 * @package   report_customsql
 * @category  external
 * @author    Oscar Nadjar <oscar.nadjar@moodle.com>
 * @copyright 2023 Moodle Us
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \report_customsql\external\query_details
 * @runTestsInSeparateProcesses
 */
final class external_query_details_test extends \externallib_advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    public function test_query_details(): void {

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

        $querydetails = query_details::execute($result['queryid']);
        $querydetails = \external_api::clean_returnvalue(query_details::execute_returns(), $querydetails);
        $querydetails = $querydetails['query'];

        $this->assertEquals($querydetails['displayname'], $displayname);
        $this->assertEquals($querydetails['description'], $description);
        $this->assertEquals($querydetails['querysql'], $querysql);
        $this->assertEquals($querydetails['queryparams'], $queryparams);
        $this->assertEquals($querydetails['querylimit'], $querylimit);
        $this->assertEquals($querydetails['capability'], $capability);
        $this->assertEquals($querydetails['runable'], $runable);
        $this->assertEquals($querydetails['at'], $at);
        $this->assertEquals($querydetails['emailto'], $emailto);
        $this->assertEquals($querydetails['emailwhat'], $emailwhat);
        $this->assertEquals($querydetails['categoryid'], $categoryid);
        $this->assertEquals($querydetails['customdir'], $customdir);
    }
}
