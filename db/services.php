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
 * Web service declarations.
 *
 * @package   report_customsql
 * @copyright 2020 the Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'report_customsql_get_users' => [
        'classname' => 'report_customsql\external\get_users',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Use by form autocomplete for selecting users to receive emails.',
        'capabilities' => 'report/customsql:definequeries',
        'type' => 'read',
        'ajax' => true,
    ],
    'report_customsql_get_simple_value' => [
        'classname'   => 'report_customsql_external',
        'methodname'  => 'get_simple_value',
        'classpath'   => 'report/customsql/classes/external.php',
        'description' => 'Execute a predefined query of the customsql report to get a simple value.',
        'type'        => 'read',
        'ajax'        => true,
    ],
];

$services = [
    'Custom SQL Report API' => [
        'functions'       => [
            'report_customsql_get_simple_value',
        ],
        'restrictedusers' => 1, // If 1, the administrator must manually select which user can use this service.
        // (Administration > Plugins > Web services > Manage services > Authorised users).
        'enabled'         => 1, // If 0, then token linked to this service won't work.
    ],
];
