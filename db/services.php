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
 * Download the CSV version of a SQL report.
 *
 * @package    report_customsql
 * @copyright  2020 Paweł Suwiński
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'report_customsql_download' => array(
        'classname' => 'report_customsql_external',
        'methodname' => 'download',
        'description' => 'Download the CSV version of a SQL report',
        'type' => 'read',
        'capabilities' => '',
        'services'      => '',
    )
);

