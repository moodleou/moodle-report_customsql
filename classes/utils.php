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

namespace report_customsql;

/**
 * Static utility methods to support the report_customsql module.
 *
 * @package report_customsql
 * @copyright 2021 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class utils {
    /**
     * Return the current timestamp, or a fixed timestamp specified by an automated test.
     *
     * @return int The timestamp
     */
    public static function time(): int {
        if ((defined('BEHAT_SITE_RUNNING') || PHPUNIT_TEST) &&
                $time = get_config('report_customsql', 'behat_fixed_time')) {
            return $time;
        } else {
            return time();
        }
    }
}
