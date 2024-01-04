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
 * Tests for the create_query web service.
 *
 * @package   report_customsql
 * @category  external
 * @author    Oscar Nadjar <oscar.nadjar@moodle.com>
 * @copyright 2023 Moodle Us
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \report_customsql\external\list_queries
 * @runTestsInSeparateProcesses
 */
final class external_list_queries_test extends \externallib_advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    public function test_list_queries(): void {

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

        $displayname = 'test2';

        $result = create_query::execute(
            $displayname, $description, $querysql, $queryparams, $querylimit,
                $capability, $runable, $at, $emailto, $emailwhat, $categoryid, $customdir);
        $result = \external_api::clean_returnvalue(create_query::execute_returns(), $result);

        $querylistpage1 = list_queries::execute(1, 1);
        $querylistpage1 = \external_api::clean_returnvalue(list_queries::execute_returns(), $querylistpage1);

        $queries = $querylistpage1['queries'];
        $this->assertEquals(1, count($queries));
        $this->assertEquals('test', $queries[0]['displayname']);

        $querylistpage2 = list_queries::execute(2, 1);
        $querylistpage2 = \external_api::clean_returnvalue(list_queries::execute_returns(), $querylistpage2);

        $queries = $querylistpage2['queries'];
        $this->assertEquals(1, count($queries));
        $this->assertEquals('test2', $queries[0]['displayname']);
    }
}
