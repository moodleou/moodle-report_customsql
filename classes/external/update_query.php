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
 * Web service to update a query.
 *
 * @package   report_customsql
 * @author    Oscar Nadjar <Oscar.nadjar@moodle.com>
 * @copyright 2024 Moodle US
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_query extends \external_api {
    /**
     * Parameter declaration.
     *
     * @return \external_function_parameters Parameters
     */
    public static function execute_parameters(): \external_function_parameters {
        return new \external_function_parameters([
            'queryid' => new \external_value(PARAM_INT, 'id of the query.', VALUE_REQUIRED),
            'displayname' => new \external_value(PARAM_ALPHANUMEXT, 'Short name of the query.', VALUE_DEFAULT, ''),
            'description' => new \external_value(PARAM_RAW, 'Description of the query.', VALUE_DEFAULT, ''),
            'querysql' => new \external_value(PARAM_RAW, 'SQL query.', VALUE_DEFAULT, ''),
            'queryparams' => new \external_value(PARAM_RAW, 'Description of the query updated', VALUE_DEFAULT, ''),
            'querylimit' => new \external_value(PARAM_INT, 'Limit of the query updated.', VALUE_DEFAULT, 5000),
            'capability' => new \external_value(PARAM_CAPABILITY, 'Capability to view the query updated.',
                                                    VALUE_DEFAULT, 'moodle/site:config'),
            'runable' => new \external_value(PARAM_ALPHAEXT, 'manual, weekly, montly.', VALUE_DEFAULT, 'manual'),
            'at' => new \external_value(PARAM_TEXT, 'Time of the execution updated.', VALUE_DEFAULT, ''),
            'emailto' => new \external_value(PARAM_EMAIL, 'Email to send the report to updated.', VALUE_DEFAULT, ''),
            'emailwhat' => new \external_value(PARAM_TEXT, 'What to send in the email updated.', VALUE_DEFAULT, ''),
            'categoryid' => new \external_value(PARAM_INT, 'Category of the query updated.', VALUE_DEFAULT, 1),
            'customdir' => new \external_value(PARAM_RAW, 'Custom directory of the query updated.', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Update a query.
     *
     * @param int    $queryid Id of the query.
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
     * @return array
     */
    public static function execute(
        int $queryid,
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
                    'queryid' => $queryid,
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
                    'customdir' => $customdir
        ];

        // We checkout the parameters.
        self::validate_parameters(self::execute_parameters(), $params);

        // We checkout the queryid.
        if (empty($DB->record_exists('report_customsql_queries', ['id' => $queryid]))) {
            throw new \moodle_exception('error:invalidqueryid', 'report_customsql');
        }

        // Validate the context.
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('report/customsql:definequeries', $context);

        // We update the query.
        $query = $DB->get_record('report_customsql_queries', ['id' => $queryid], '*');
        $query->displayname = !empty($displayname) ? $displayname : $query->displayname;
        $query->description = !empty($description) ? $description : $query->description;
        $query->querysql = !empty($querysql) ? $querysql : $query->querysql;
        $query->queryparams = !empty($queryparams) ? $queryparams : $query->queryparams;
        $query->querylimit = !empty($querylimit) ? $querylimit : $query->querylimit;
        $query->capability = !empty($capability) ? $capability : $query->capability;
        $query->runable = !empty($runable) ? $runable : $query->runable;
        $query->at = !empty($at) ? $at : $query->at;
        $query->emailto = !empty($emailto) ? $emailto : $query->emailto;
        $query->emailwhat = !empty($emailwhat) ? $emailwhat : $query->emailwhat;
        $query->categoryid = !empty($categoryid) ? $categoryid : $query->categoryid;
        $query->customdir = !empty($customdir) ? $customdir : $query->customdir;
        $query->usermodified = $USER->id;
        $query->timemodified = time();

        if (empty($DB->update_record('report_customsql_queries', $query))) {
            throw new \moodle_exception('error:updatefail', 'report_customsql', '');
        }

        return ['success' => true];
    }

    /**
     * Returns true if the query was successfully updated.
     *
     * @return \external_description Result type
     */
    public static function execute_returns(): \external_description {
        return new \external_single_structure([
            'success' => new \external_value(PARAM_BOOL, 'Succes of the update.'),
        ]);
    }
}
