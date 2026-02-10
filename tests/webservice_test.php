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
 * Unit tests for the webservice of the custom SQL report.
 *
 * @package report_customsql
 * @copyright 2018 Andre Scherl, ISB Bayern
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_customsql;

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../locallib.php');

/**
 * Unit tests for the webservice of the custom SQL report.
 *
 * @package report_customsql
 * @copyright 2018 Andre Scherl, ISB Bayern
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \report_customsql_external
 */
final class webservice_test extends \advanced_testcase {
    /**
     * Test getting a simple value via webservice.
     *
     * @runInSeparateProcess
     */
    public function test_get_simple_value(): void {
        $this->resetAfterTest(true);

        $displayname = 'test_query_counting_itself';
        $description = 'Count queries with exactly this name. Should be one.';

        $this->create_a_database_row($displayname, $description);
        $result = \report_customsql_external::get_simple_value($displayname);

        $this->assertEquals("1", $result);
    }

    /**
     * Create an entry in 'report_customsql_queries' table and return the id.
     *
     * @param string $displayname
     * @param string $description
     *
     * @return int The new query id.
     */
    private function create_a_database_row(
        $displayname = 'number_of_custom_sql_queries',
        $description = 'Count the custom sql queries.'
    ) {
        global $DB;
        $report = new \stdClass();
        $report->displayname = $displayname;
        $report->description = $description;
        $report->querysql = "SELECT count(DISTINCT id) as simplevalue FROM {report_customsql_queries}
            WHERE displayname = '$displayname'";
        $report->capability = 'report/customsql:view';
        $report->lastexecutiontime = 1;
        $report->runable = 'manual';
        $report->categoryid = 1;

        return $DB->insert_record('report_customsql_queries', $report);
    }
}
