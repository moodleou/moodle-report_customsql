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
 * Database upgrades.
 *
 * @package report
 * @subpackage customsql
 * @copyright 2012 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * @param string $oldversion the version we are upgrading from.
 */
function xmldb_report_customsql_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2012092400) {

        // Add fields to report_customsql_queries.
        $table = new xmldb_table('report_customsql_queries');
        if ($dbman->table_exists($table)) {

            // Define and add the field 'at'.
            $field = new xmldb_field('at', XMLDB_TYPE_CHAR, '16', null, XMLDB_NOTNULL, null, null, 'singlerow');
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
            // Define and add the field 'emailto'.
            $field = new xmldb_field('emailto', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'at');
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
            // Define and add the field 'emailwhat'.
            $field = new xmldb_field('emailwhat', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null, 'emailto');
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_plugin_savepoint(true, 2012092400, 'report', 'customsql');
    }

    if ($oldversion < 2013062300) {
        require_once($CFG->dirroot . '/report/customsql/lib.php');
        $table = new xmldb_table('report_customsql_queries');
        $field = new xmldb_field('querylimit', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, REPORT_CUSTOMSQL_MAX_RECORDS, 'queryparams');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2013062300, 'report', 'customsql');
    }

    return true;
}
