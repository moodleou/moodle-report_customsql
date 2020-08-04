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
 * External Custom SQL admin report API
 *
 * @package    report_customsql
 * @copyright  2020 Pawe³ Suwiñski
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

class report_customsql_external extends external_api {

    /**
     * To validade input parameters
     * @return external_function_parameters
     */
    public static function download_parameters() {
        return new external_function_parameters(
              array(
                  'id' => new external_value(PARAM_INT, 'Report ID', VALUE_REQUIRED),
                  'timestamp' => new external_value(PARAM_INT, 'Report timestamp', VALUE_DEFAULT, time()),
                  'dataformat' => new external_value(PARAM_ALPHA, 'Report dataformat', VALUE_DEFAULT, 'csv'),
               )
        );
    }

    /**
     * Download the CSV version of a SQL report.
     * 
     * @see ../download.php
     * @param int $id 
     * @param int $timestamp 
     * @param string $datatype
     * @return null
     */
    public static function download($id, $timestamp, $dataformat) {
        global $DB;
        foreach(['timestamp', 'dataformat'] as $param) {
            if(!isset($_GET[$param])) {
                $_GET[$param] = $$param;
            }
        }
        require_once(dirname(__FILE__) . '/../download.php');
        exit;
    }

    /**
     * Validate the return value
     * @return external_value
     */
    public static function download_returns() {
        return new external_value(PARAM_RAW, 'Report content in CSV format');
    }

}
