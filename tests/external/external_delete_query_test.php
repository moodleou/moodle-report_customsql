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
 * Tests for the delete_query web service.
 *
 * @package   report_customsql
 * @category  external
 * @author    Oscar Nadjar <oscar.nadjar@moodle.com>
 * @copyright 2023 Moodle Us
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \report_customsql\external\delete_query
 * @runTestsInSeparateProcesses
 */
class external_delete_query_test extends \externallib_advanced_testcase {

    protected function setUp(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    public function test_delete_query(): void {

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

        $query = $DB->get_record('report_customsql_queries', []);

        $result = delete_query::execute($query->id);
        $result = \external_api::clean_returnvalue(delete_query::execute_returns(), $result);

        $query = $DB->get_record('report_customsql_queries', ['id' => $query->id]);
        $this->assertFalse($query);
    }
}
