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
 * Database upgrade script for the custom SQL report.
 *
 * @package report_customsql
 * @copyright 2009 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


function xmldb_report_customsql_upgrade($oldversion=0) {
    global $CFG, $db;

    $result = true;

    if ($result && $oldversion < 2009102802) {

    /// Define field runable to be added to report_customsql_queries
        $table = new XMLDBTable('report_customsql_queries');
        $field = new XMLDBField('runable');
        $field->setAttributes(XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, null, null, 'manual', 'lastexecutiontime');

    /// Launch add field runable
        $result = $result && add_field($table, $field);
    }

    if ($result && $oldversion < 2009102900) {

    /// Define field singlerow to be added to report_customsql_queries
        $table = new XMLDBTable('report_customsql_queries');
        $field = new XMLDBField('singlerow');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'runable');

    /// Launch add field singlerow
        $result = $result && add_field($table, $field);
    }

    if ($result && $oldversion < 2009103000) {

    /// Changing the default of field lastrun on table report_customsql_queries to 0
        $table = new XMLDBTable('report_customsql_queries');
        $field = new XMLDBField('lastrun');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0', 'capability');

    /// Launch change of default for field lastrun
        $result = $result && change_field_default($table, $field);
    }

    return $result;
}
?>
