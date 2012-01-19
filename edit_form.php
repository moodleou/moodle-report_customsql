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
 * Form for editing a custom SQL report.
 *
 * @package report_customsql
 * @copyright 2009 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once($CFG->libdir . '/formslib.php');
require_once(dirname(__FILE__) . '/locallib.php');


/**
 * Form for editing a custom SQL report.
 *
 * @copyright © 2009 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_customsql_edit_form extends moodleform {
    function definition() {
        global $CFG;

        $mform =& $this->_form;

        $mform->addElement('text', 'displayname', get_string('displayname', 'report_customsql'));
        $mform->addRule('displayname', get_string('displaynamerequired', 'report_customsql'),
                'required', null, 'client');
        $mform->setType('displayname', PARAM_MULTILANG);

        $mform->addElement('htmleditor', 'description', get_string('description', 'report_customsql'));
        $mform->setType('description', PARAM_RAW);

        $mform->addElement('textarea', 'querysql', get_string('querysql', 'report_customsql'),
                'rows="25" cols="50"');
        $mform->addRule('querysql', get_string('querysqlrequried', 'report_customsql'),
                'required', null, 'client');
        $mform->setType('querysql', PARAM_RAW);

        $mform->addElement('static', 'note', get_string('note', 'report_customsql'),
                get_string('querynote', 'report_customsql', $CFG->wwwroot));

        $mform->addElement('select', 'capability', get_string('whocanaccess', 'report_customsql'), 
                report_customsql_capability_options());

        $mform->addElement('select', 'runable', get_string('runable', 'report_customsql'),
                report_customsql_runable_options());

        $mform->addElement('checkbox', 'singlerow', get_string('typeofresult', 'report_customsql'),
                get_string('onerow', 'report_customsql'));
        $mform->disabledIf('singlerow', 'runable', 'eq', 'manual');

        $this->add_action_buttons();
    }

    function validation($data, $files) {
        global $CFG, $db, $USER;

        $errors = parent::validation($data, $files);

        $sql = stripslashes($data['querysql']);

        // Simple test to avoid evil stuff in the SQL.
        if (report_customsql_contains_bad_word($sql)) {
            $errors['querysql'] = get_string('notallowedwords', 'report_customsql',
                    implode(', ', report_customsql_bad_words_list()));

        // Do not allow any semicolons.
        } else if (strpos($sql, ';') !== false) {
            $errors['querysql'] = get_string('nosemicolon', 'report_customsql');

        // Make sure prefix is prefix_, not explicit.
        } else if ($CFG->prefix != '' && preg_match('/\b' . $CFG->prefix . '\w+/i', $sql)) {
            $errors['querysql'] = get_string('noexplicitprefix', 'report_customsql', $CFG->prefix);

        // Now try running the SQL, and ensure it runs without errors.
        } else {
            $report = new stdClass;
            $report->querysql = $sql;
            $report->runable = $data['runable'];
            $sql = report_customsql_prepare_sql($report, time());

            $rs = report_customsql_execute_query($sql, 2);
            if (!$rs) {
                $errors['querysql'] = get_string('queryfailed', 'report_customsql', $db->ErrorMsg());
            } else if (!empty($data['singlerow'])) {
                if (rs_EOF($rs)) {
                    $errors['querysql'] = get_string('norowsreturned', 'report_customsql');
                } else {
                    rs_fetch_next_record($rs);
                    if (!rs_EOF($rs)) {
                        $errors['querysql'] = get_string('morethanonerowreturned', 'report_customsql');
                    }
                }
            }

            if ($rs) {
                rs_close($rs);
            }
        }

        return $errors;
    }
}
