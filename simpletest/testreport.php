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
 * Unit tests for (parts of) the custom SQL report.
 *
 * @package report_customsql
 * @copyright 2009 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(__FILE__) . '/../locallib.php');


class report_customsql_test extends UnitTestCase {
    function test_get_week_starts_test() {
        $this->assertEqual(array(
                strtotime('00:00 7 November 2009'), strtotime('00:00 31 October 2009')),
                report_customsql_get_week_starts(strtotime('12:36 10 November 2009')));

        $this->assertEqual(array(
                strtotime('00:00 7 November 2009'), strtotime('00:00 31 October 2009')),
                report_customsql_get_week_starts(strtotime('00:00 7 November 2009')));

        $this->assertEqual(array(
                strtotime('00:00 7 November 2009'), strtotime('00:00 31 October 2009')),
                report_customsql_get_week_starts(strtotime('23:59 13 November 2009')));
    }

    function test_get_month_starts_test() {
        $this->assertEqual(array(
                strtotime('00:00 1 November 2009'), strtotime('00:00 1 October 2009')),
                report_customsql_get_month_starts(strtotime('12:36 10 November 2009')));

        $this->assertEqual(array(
                strtotime('00:00 1 November 2009'), strtotime('00:00 1 October 2009')),
                report_customsql_get_month_starts(strtotime('00:00 1 November 2009')));

        $this->assertEqual(array(
                strtotime('00:00 1 November 2009'), strtotime('00:00 1 October 2009')),
                report_customsql_get_month_starts(strtotime('23:59 29 November 2009')));
    }

    function test_report_customsql_substitute_user_token() {
        $this->assertEqual('SELECT COUNT(*) FROM oh_quiz_attempts WHERE user = 123',
                report_customsql_substitute_user_token('SELECT COUNT(*) FROM oh_quiz_attempts WHERE user = %%USERID%%', 123));
    }
}

?>
