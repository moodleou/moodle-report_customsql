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

namespace report_customsql\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/report/customsql/edit_form.php');

/**
 * Web service to create new queries.
 *
 * @package   report_customsql
 * @author    Oscar Nadjar <Oscar.nadjar@moodle.com>
 * @copyright 2024 Moodle US
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_query extends \external_api {
    /**
     * Parameter declaration.
     *
     * @return \external_function_parameters Parameters
     */
    public static function execute_parameters(): \external_function_parameters {
        return new \external_function_parameters([
            'displayname' => new \external_value(PARAM_ALPHANUMEXT, 'Short name of the query.', VALUE_REQUIRED),
            'description' => new \external_value(PARAM_RAW, 'Description of the query.', VALUE_DEFAULT, ''),
            'querysql' => new \external_value(PARAM_RAW, 'SQL query.', VALUE_REQUIRED),
            'queryparams' => new \external_value(PARAM_RAW, 'Description of the query.', VALUE_DEFAULT, ''),
            'querylimit' => new \external_value(PARAM_INT, 'Limit of the query.', VALUE_DEFAULT, 5000),
            'capability' => new \external_value(PARAM_CAPABILITY, 'Capability to view the query.',
                                                    VALUE_DEFAULT, 'moodle/site:config'),
            'runable' => new \external_value(PARAM_ALPHAEXT, 'manual, weekly, montly.', VALUE_DEFAULT, 'manual'),
            'at' => new \external_value(PARAM_TEXT, 'Time of the execution.', VALUE_DEFAULT, ''),
            'emailto' => new \external_value(PARAM_EMAIL, 'Email to send the report to.', VALUE_DEFAULT, ''),
            'emailwhat' => new \external_value(PARAM_TEXT, 'What to send in the email.', VALUE_DEFAULT, ''),
            'categoryid' => new \external_value(PARAM_INT, 'Category of the query.', VALUE_DEFAULT, 1),
            'customdir' => new \external_value(PARAM_RAW, 'Custom directory of the query.', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Create a new query.
     *
     * @param string $displayname Short name of the query.
     * @param string $description Description of the query.
     * @param string $querysql SQL query.
     * @param string $queryparams Description of the query.
     * @param int    $querylimit Limit of the query.
     * @param string $capability Capability to view the query.
     * @param string $runable manual, weekly, montly.
     * @param string $at Time of the execution.
     * @param string $emailto Email to send the report to.
     * @param string $emailwhat What to send in the email.
     * @param int    $categoryid Category of the query.
     * @param string $customdir Custom directory of the query.
     *
     * @return int id of the created query.
     */
    public static function execute(
        string $displayname,
        string $description,
        string $querysql,
        string $queryparams,
        int $querylimit,
        string $capability,
        string $runable,
        string $at,
        string $emailto,
        string $emailwhat,
        int $categoryid,
        string $customdir
    ): array {
        global $CFG, $DB, $USER;

        // We need an associative array in order to use the validation functions.
        $params = [
                    'displayname' => $displayname,
                    'description' => $description,
                    'querysql' => $querysql,
                    'queryparams' => $queryparams,
                    'querylimit' => $querylimit,
                    'capability' => $capability,
                    'runable' => $runable,
                    'at' => $at,
                    'emailto' => $emailto,
                    'emailwhat' => $emailwhat,
                    'categoryid' => $categoryid,
                    'customdir' => $customdir,
        ];

        // This will assign the validated values to the variables.
        $formdata = self::validate_parameters(self::execute_parameters(), $params);

        // Validate the context.
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('report/customsql:definequeries', $context);

        // Validate the data using the form class.
        $form = new \report_customsql_edit_form();
        $errors = $form->validation($formdata, []);

        if (!empty($errors)) {
            throw new \moodle_exception('error', 'report_customsql', '', $errors);
        }

        // We are ready to insert the query in the database.
        $query = (object)$formdata;
        $query->usermodified = $USER->id;
        $query->timecreated = time();
        $query->timemodified = time();
        $query->id = $DB->insert_record('report_customsql_queries', $query);

        if (empty($query->id)) {
            throw new \moodle_exception('error', 'report_customsql', '', $errors);
        }

        return ['queryid' => $query->id];
    }

    /**
     * Returns the id of the created query.
     *
     * @return \external_description Result type
     */
    public static function execute_returns(): \external_description {
        return new \external_single_structure([
            'queryid' => new \external_value(PARAM_INT, 'id of the created query.'),
        ]);
    }
}
