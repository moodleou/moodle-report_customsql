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
 * Upgrade code for report_customsql.
 *
 * @package report_customsql
 * @copyright 2015 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Upgrade code for report_customsql.
 *
 * @param string $oldversion the version we are upgrading from.
 * @return bool true on success.
 */
function xmldb_report_customsql_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2012011900) {

        // Add field to report_customsql_queries.
        $table = new xmldb_table('report_customsql_queries');
        if ($dbman->table_exists($table)) {
            // Define and add the field 'queryparams'.
            $field = new xmldb_field('queryparams', XMLDB_TYPE_TEXT, 'small', null, null, null, null, 'querysql');
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_plugin_savepoint(true, 2012011900, 'report', 'customsql');
    }

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
        require_once($CFG->dirroot . '/report/customsql/locallib.php');
        $table = new xmldb_table('report_customsql_queries');
        $field = new xmldb_field('querylimit', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED,
                XMLDB_NOTNULL, null, 5000, 'queryparams');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2013062300, 'report', 'customsql');
    }

    if ($oldversion < 2013102400) {

        // Define table report_customsql_categories to be created.
        $table = new xmldb_table('report_customsql_categories');

        // Adding fields to table report_customsql_categories.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null);

        // Adding key to table report_customsql_categories.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for report_customsql_categories.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define field categoryid to be added to report_customsql_queries.
        $table = new xmldb_table('report_customsql_queries');
        $field = new xmldb_field('categoryid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, 'emailwhat');

        // Conditionally launch add field categoryid.
        if (! $dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add key (for the new field just added).
        $key = new xmldb_key('categoryid', XMLDB_KEY_FOREIGN, array('categoryid'), 'report_customsql_categories', array('id'));
        $dbman->add_key($table, $key);

        // Create the default 'Miscellaneous' category.
        $category = new stdClass();
        $category->name = get_string('defaultcategory', 'report_customsql');
        if (!$DB->record_exists('report_customsql_categories', array('name' => $category->name))) {
            $category->id = $DB->insert_record('report_customsql_categories', $category);
        }
        // Update the existing query category ids, to move them into this category.
        $sql = 'UPDATE {report_customsql_queries} SET categoryid =' . $category->id;
        $DB->execute($sql);

        // Report savepoint reached.
        upgrade_plugin_savepoint(true, 2013102400, 'report', 'customsql');
    }

    // Repeat upgrade step that might have got missed on some branches.
    if ($oldversion < 2014020300) {
        require_once($CFG->dirroot . '/report/customsql/locallib.php');
        $table = new xmldb_table('report_customsql_queries');
        $field = new xmldb_field('querylimit', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED,
                XMLDB_NOTNULL, null, 5000, 'queryparams');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2014020300, 'report', 'customsql');
    }

    if ($oldversion < 2015062900) {

        // Define field descriptionformat to be added to report_customsql_queries.
        $table = new xmldb_table('report_customsql_queries');
        $field = new xmldb_field('descriptionformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '1', 'description');

        // Conditionally launch add field descriptionformat.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Customsql savepoint reached.
        upgrade_plugin_savepoint(true, 2015062900, 'report', 'customsql');
    }

    if ($oldversion < 2016011800) {

        // Define field customdir to be added to report_customsql_queries.
        $table = new xmldb_table('report_customsql_queries');
        $field = new xmldb_field('customdir', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'categoryid');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2016011800, 'report', 'customsql');
    }

    if ($oldversion < 2019111101) {
        // For upgraded sites, set this setting to be backwards compatible.
        set_config('startwday', '6', 'report_customsql');
        upgrade_plugin_savepoint(true, 2019111101, 'report', 'customsql');
    }

    if ($oldversion < 2020102800) {
        // Convert the contents of the emailto column from a list of usernames to a list of user ids.

        // Transfer data from old columns into details.
        // (There seem to be just a few thousand of these, so not too bad.)
        $queries = $DB->get_records_select('report_customsql_queries', 'emailto <> ?', [''], 'id', 'id, emailto');
        $total = count($queries);

        if ($total > 0) {
            // First get all the different usernames that appear.
            $usernames = [];
            foreach ($queries as $query) {
                foreach (preg_split("/[\s,;]+/", $query->emailto) as $username) {
                    $usernames[$username] = 1;
                }
            }

            // Then get the corresponding user ids.
            $userids = $DB->get_records_list('user', 'username', array_keys($usernames), '', 'username, id');

            // Now  do the update.
            $progressbar = new progress_bar('report_customsql_emailto_upgrade', 500, true);
            $done = 0;
            foreach ($queries as $query) {
                $progressbar->update($done, $total,
                        "Updating ad-hoc DB query email recipients - {$done}/{$total} (id = {$query->id}).");

                $queryuserids = [];
                foreach (preg_split("/[\s,;]+/", $query->emailto) as $username) {
                    if (isset($userids[$username])) {
                        $queryuserids[] = $userids[$username]->id;
                    }
                }
                sort($queryuserids);

                $DB->set_field('report_customsql_queries', 'emailto', implode(',', $queryuserids), ['id' => $query->id]);
                $done += 1;
            }
            $progressbar->update($done, $total, "Updating ad-hoc DB query email recipients - {$done}/{$total}.");
        }

        // Customsql savepoint reached.
        upgrade_plugin_savepoint(true, 2020102800, 'report', 'customsql');
    }

    if ($oldversion < 2021051000) {
        // Define field usermodified to be added to report_customsql_queries.
        $table = new xmldb_table('report_customsql_queries');
        $field = new xmldb_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'customdir');

        // Conditionally launch add field usermodified.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field timecreated to be added to report_customsql_queries.
        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null,
                XMLDB_NOTNULL, null, '0', 'usermodified');

        // Conditionally launch add field timecreated.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field timemodified to be added to report_customsql_queries.
        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null,
                XMLDB_NOTNULL, null, '0', 'timecreated');

        // Conditionally launch add field timemodified.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Customsql savepoint reached.
        upgrade_plugin_savepoint(true, 2021051000, 'report', 'customsql');
    }

    return true;
}
