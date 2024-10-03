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
 * Web service to list the queries.
 *
 * @package   report_customsql
 * @author    Oscar Nadjar <Oscar.nadjar@moodle.com>
 * @copyright 2024 Moodle US
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class list_queries extends \external_api {
    /**
     * Parameter declaration.
     *
     * @return \external_function_parameters Parameters
     */
    public static function execute_parameters(): \external_function_parameters {
        return new \external_function_parameters([
            'page' => new \external_value(PARAM_INT, 'Page number', VALUE_DEFAULT, 1),
            'pagesize' => new \external_value(PARAM_INT, 'The pagesize', VALUE_DEFAULT, 20),
        ]);
    }

    /**
     * Delete a query.
     *
     * @param int $page The page number.
     * @param int $pagesize The pagesize.
     *
     * @return array
     */
    public static function execute(int $page, int $pagesize): array {
        global $CFG, $DB, $USER;

        // This will assign the validated values to the variables.
        $params = self::validate_parameters(self::execute_parameters(), ['page' => $page, 'pagesize' => $pagesize]);
        $page = !empty($params['page']) ? $params['page'] : 1;
        $pagesize = !empty($params['pagesize']) ? $params['pagesize'] : 20;

        // Validate the context.
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('report/customsql:definequeries', $context);
        $fields = 'id,displayname';
        $page = $page < 1 ? 0 : $page - 1;
        $queries = $DB->get_records('report_customsql_queries', [], '', $fields, $page * $pagesize, $pagesize);
        $totalqueries = $DB->count_records('report_customsql_queries');
        $totalpages = ceil($totalqueries / $pagesize);
        if ($page > $totalpages) {
            throw new \moodle_exception('invalidpagenumber', 'report_customsql', '',
                ['page' => $page, 'totalpages' => $totalpages]);
        }

        return ['page' => $page + 1, 'totalpages' => $totalpages, 'pagesize' => $pagesize, 'queries' => $queries];
    }

    /**
     * Returns the queries paginated.
     *
     * @return \external_description Result type
     */
    public static function execute_returns(): \external_description {
        return new \external_single_structure([
            'page' => new \external_value(PARAM_INT, 'True if the query was deleted.'),
            'totalpages' => new \external_value(PARAM_INT, 'True if the query was deleted.'),
            'queries' => new \external_multiple_structure(
                new \external_single_structure([
                    'id' => new \external_value(PARAM_INT, 'The id of the query.'),
                    'displayname' => new \external_value(PARAM_TEXT, 'The display name of the query.'),
                ]),
                'The list of queries'
            ),
        ]);
    }
}
