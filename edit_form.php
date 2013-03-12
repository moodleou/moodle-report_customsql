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
    public function definition() {
        global $CFG;

        $mform =& $this->_form;

        $mform->addElement('text', 'displayname', get_string('displayname', 'report_customsql'));
        $mform->addRule('displayname', get_string('displaynamerequired', 'report_customsql'),
                        'required', null, 'client');
        $mform->setType('displayname', PARAM_MULTILANG);

        $mform->addElement('htmleditor', 'description', get_string('description',
                                                                   'report_customsql'));
        $mform->setType('description', PARAM_RAW);

        $mform->addElement('textarea', 'querysql', get_string('querysql', 'report_customsql'),
                           'rows="25" cols="50"');
        $mform->addRule('querysql', get_string('querysqlrequried', 'report_customsql'),
                        'required', null, 'client');
        $mform->setType('querysql', PARAM_RAW);

        $mform->addElement('submit', 'verify', get_string('verifyqueryandupdate', 'report_customsql'));
        $mform->registerNoSubmitButton('verify');

        $hasparameters = 0;
        if (count($this->_customdata)) {
            $mform->addElement('static', 'params', '', get_string('queryparams', 'report_customsql'));
            foreach ($this->_customdata as $queryparam => $formparam) {
                $mform->addElement('text', $formparam, $queryparam);
                $hasparameters++;
            }
            $mform->addElement('static', 'spacer', '', '');
        }

        $mform->addElement('static', 'note', get_string('note', 'report_customsql'),
                           get_string('querynote', 'report_customsql', $CFG->wwwroot));

        $capabilityoptions = report_customsql_capability_options();
        $mform->addElement('select', 'capability', get_string('whocanaccess', 'report_customsql'),
                           $capabilityoptions);
        end($capabilityoptions);
        $mform->setDefault('capability', key($capabilityoptions));

        $runat = array();
        if ($hasparameters) {
            $runat[] = $mform->createElement('select', 'runable', null,  report_customsql_runable_options('manual'));
        } else {
            $runat[] = $mform->createElement('select', 'runable', null,  report_customsql_runable_options());
        }
        $runat[] = $mform->createElement('select', 'at', null, report_customsql_daily_at_options());
        $mform->addGroup($runat, null, get_string('runable', 'report_customsql'), ' at ', true);

        $mform->addElement('checkbox', 'singlerow', get_string('typeofresult', 'report_customsql'),
                           get_string('onerow', 'report_customsql'));

        $mform->addElement('text', 'emailto', get_string('emailto', 'report_customsql'), 'size = 70');
        $mform->addElement('select', 'emailwhat', get_string('emailwhat', 'report_customsql'),
                report_customsql_email_options());
        $mform->disabledIf('singlerow', 'runable', 'eq', 'manual');
        $mform->disabledIf('at', 'runable', 'ne', 'daily');
        $mform->disabledIf('emailto', 'runable', 'eq', 'manual');
        $mform->disabledIf('emailwhat', 'runable', 'eq', 'manual');

        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        global $CFG, $DB, $USER;

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
        } else if ($CFG->prefix != '' && preg_match('/\b'.$CFG->prefix . '\w+/i', $sql)) {
            $errors['querysql'] = get_string('noexplicitprefix', 'report_customsql', $CFG->prefix);

            // Now try running the SQL, and ensure it runs without errors.
        } else {
            $report = new stdClass;
            $report->querysql = $sql;
            $report->runable = $data['runable'];
            if ($report->runable === 'daily') {
                $report->at = $data['at'];
            }
            $sql = report_customsql_prepare_sql($report, time());

            // Check for required query parameters if there are any.
            $queryparams = array();
            foreach (report_customsql_get_query_placeholders($sql) as $queryparam) {
                $queryparam = substr($queryparam, 1);
                $formparam = 'queryparam' . $queryparam;
                if (!isset($data[$formparam])) {
                    $errors['params'] = get_string('queryparamschanged', 'report_customsql');
                    break;
                }
                $queryparams[$queryparam] = $data[$formparam];
            }

            if (!isset($errors['params'])) {
                try {
                    $rs = report_customsql_execute_query($sql, $queryparams, 2);

                    if (!empty($data['singlerow'])) {
                        // Count rows for Moodle 2 as all Moodle 1.9 useful and more performant
                        // recordset methods removed.
                        $rows = 0;
                        foreach ($rs as $value) {
                            $rows++;
                        }
                        if (!$rows) {
                            $errors['querysql'] = get_string('norowsreturned', 'report_customsql');
                        } else if ($rows >= 2) {
                            $errors['querysql'] = get_string('morethanonerowreturned',
                                                             'report_customsql');
                        }
                    }
                    // Ckeck the list of users in emailto field.
                    if ($data['runable'] !== 'manual') {
                        if ($invaliduser = report_customsql_validate_users($data['emailto'], $data['capability'])) {
                            $errors['emailto'] = $invaliduser;
                        }
                    }
                    $rs->close();
                } catch (Exception $e) {
                    $errors['querysql'] = get_string('queryfailed', 'report_customsql',
                                                     $e->getMessage());
                }
            }
        }
        return $errors;
    }
}
