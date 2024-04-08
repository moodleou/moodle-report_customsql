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
use external_multiple_structure;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/report/customsql/locallib.php');

/**
 * Web service to validate a query.
 *
 * @package   report_customsql
 * @author    Oscar Nadjar <Oscar.nadjar@moodle.com>
 * @copyright 2024 Moodle US
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class query_validation extends \external_api {
    /**
     * Parameter declaration.
     *
     * @return \external_function_parameters Parameters
     */
    public static function execute_parameters(): \external_function_parameters {
        return new \external_function_parameters([
            'queryid' => new \external_value(PARAM_INT, 'The id of the query', VALUE_REQUIRED),
            'rowlimit' => new \external_value(PARAM_INT, 'The limit of rows', VALUE_DEFAULT, 10)
        ]);
    }

    /**
     * Delete a query.
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
        $rowlimit = $params['rowlimit'];
        $limittestrows = get_config('report_customsql', 'limittestrows');
        $limittestrows = $limittestrows < 100 ? $limittestrows : 100;

        // Validate the context.
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('report/customsql:definequeries', $context);

        // We checkout the queryid.
        $query = $DB->get_record('report_customsql_queries', ['id' => $queryid], '*', MUST_EXIST);
        $sql = report_customsql_prepare_sql($query, time());
        $limit = !empty($limittestrows) ? $limittestrows : $rowlimit;
        $queryparams = !empty($query->queryparams) ? unserialize($query->queryparams) : null;
        $rs = report_customsql_execute_query($sql, $queryparams, $limit);
        $result = [];
        foreach ($rs as $row) {
            $result[] = $row;
        }
        $rs->close();
        return ['result' => json_encode($result)];
    }

    /**
     * Returns results if the query is valid to be executed.
     *
     * @return \external_description Result type
     */
    public static function execute_returns(): \external_description {
        return new \external_single_structure([
                'result' => new \external_value(PARAM_RAW, 'The result of the query json formated', VALUE_DEFAULT, null)
            ]
        );
    }
}
