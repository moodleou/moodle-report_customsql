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

/**
 * Web service to return the query details.
 *
 * @package   report_customsql
 * @author    Oscar Nadjar <Oscar.nadjar@moodle.com>
 * @copyright 2024 Moodle US
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class query_details extends \external_api {
    /**
     * Parameter declaration.
     *
     * @return \external_function_parameters Parameters
     */
    public static function execute_parameters(): \external_function_parameters {
        return new \external_function_parameters([
            'queryid' => new \external_value(PARAM_INT, 'The id of the query', VALUE_REQUIRED),
        ]);
    }

    /**
     * Returns the query details.
     *
     * @param int $queryid The id of the query.
     *
     * @return array
     */
    public static function execute(int $queryid): array {
        global $CFG, $DB, $USER;

        // This will assign the validated values to the variables.
        $params = self::validate_parameters(self::execute_parameters(), ['queryid' => $queryid]);
        $queryid = $params['queryid'];

        // Validate the context.
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('report/customsql:definequeries', $context);

        $query = $DB->get_record('report_customsql_queries', ['id' => $queryid], '*', MUST_EXIST);

        return ['query' => $query];
    }

    /**
     * Returns the query details if exists.
     *
     * @return \external_description Result type
     */
    public static function execute_returns(): \external_description {
        return new \external_single_structure([
            'query' => new \external_single_structure([
                'id' => new \external_value(PARAM_INT, 'The id of the query.'),
                'displayname' => new \external_value(PARAM_TEXT, 'The display name of the query.'),
                'description' => new \external_value(PARAM_RAW, 'The description of the query.'),
                'querysql' => new \external_value(PARAM_RAW, 'The SQL query.'),
                'queryparams' => new \external_value(PARAM_TEXT, 'The description of the query.'),
                'querylimit' => new \external_value(PARAM_INT, 'The limit of the query.'),
                'capability' => new \external_value(PARAM_CAPABILITY, 'The capability to view the query.'),
                'runable' => new \external_value(PARAM_ALPHAEXT, 'The runable of the query.'),
                'at' => new \external_value(PARAM_TEXT, 'The time of the execution.'),
                'emailto' => new \external_value(PARAM_EMAIL, 'The email to send the report to.'),
                'emailwhat' => new \external_value(PARAM_TEXT, 'The what to send in the email.'),
                'categoryid' => new \external_value(PARAM_INT, 'The category of the query.'),
                'customdir' => new \external_value(PARAM_TEXT, 'The custom directory of the query.'),
            ])
        ]);
    }
}
