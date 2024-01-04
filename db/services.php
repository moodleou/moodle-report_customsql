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
    'report_customsql_create_query' => [
        'classname' => 'report_customsql\external\create_query',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Use to create a new query.',
        'capabilities' => 'report/customsql:definequeries',
        'type' => 'read',
        'ajax' => true,
    ],
    'report_customsql_update_query' => [
        'classname' => 'report_customsql\external\update_query',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Use to update a query.',
        'capabilities' => 'report/customsql:definequeries',
        'type' => 'read',
        'ajax' => true,
    ],
    'report_customsql_delete_query' => [
        'classname' => 'report_customsql\external\delete_query',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Use to delete a query.',
        'capabilities' => 'report/customsql:definequeries',
        'type' => 'read',
        'ajax' => true,
    ],
    'report_customsql_list_queries' => [
        'classname' => 'report_customsql\external\list_queries',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Use to list the sql queries.',
        'capabilities' => 'report/customsql:definequeries',
        'type' => 'read',
        'ajax' => true,
    ],
    'report_customsql_query_details' => [
        'classname' => 'report_customsql\external\query_details',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Use to get the details of a query.',
        'capabilities' => 'report/customsql:definequeries',
        'type' => 'read',
        'ajax' => true,
    ],
    'report_customsql_get_query_results' => [
        'classname' => 'report_customsql\external\get_query_results',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Use to get the results of a query.',
        'capabilities' => 'report/customsql:definequeries',
        'type' => 'read',
        'ajax' => true,
    ],
    'report_customsql_query_validation' => [
        'classname' => 'report_customsql\external\query_validation',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Use to validate a query.',
        'capabilities' => 'report/customsql:definequeries',
        'type' => 'read',
        'ajax' => true,
    ],
];

$services = [
    'report_customsql_service' => [
        'functions' => [
            'report_customsql_get_users',
            'report_customsql_create_query',
            'report_customsql_update_query',
            'report_customsql_delete_query',
            'report_customsql_list_queries',
            'report_customsql_query_details',
            'report_customsql_get_query_results',
            'report_customsql_query_validation',
        ],
        'requiredcapability' => '',
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'customsqlws',
        'downloadfiles' => 1,
        'uploadfiles'  => 0,
    ],
];
