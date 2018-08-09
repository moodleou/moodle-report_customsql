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
 * Install code for report_customsql.
 *
 * @package report_customsql
 * @copyright 2013 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Install code for report_customsql.
 *
 * @return bool true on success.
 */
function xmldb_report_customsql_install() {
    global $CFG, $DB;

    // Create the default 'Miscellaneous' category.
    $category = new stdClass();
    $category->name = get_string('defaultcategory', 'report_customsql');
    if (!$DB->record_exists('report_customsql_categories', array('name' => $category->name))) {
        $DB->insert_record('report_customsql_categories', $category);
    }

    return true;
}
