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
 * Web service to delete a query.
 *
 * @package   report_customsql
 * @author    Oscar Nadjar <Oscar.nadjar@moodle.com>
 * @copyright 2024 Moodle US
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_query extends \external_api {
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

        // Validate the context.
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('report/customsql:definequeries', $context);

        // We checkout the queryid.
        if (empty($DB->record_exists('report_customsql_queries', ['id' => $queryid]))) {
            throw new \moodle_exception('error:invalidqueryid', 'report_customsql');
        }

        // We delete the query.
        if (empty($DB->delete_records('report_customsql_queries', ['id' => $queryid]))) {
            throw new \moodle_exception('error:cannotdeletequery', 'report_customsql');
        }

        return ['success' => true];
    }

    /**
     * Returns true if the query was deleted.
     *
     * @return \external_description Result type
     */
    public static function execute_returns(): \external_description {
        return new \external_single_structure([
            'success' => new \external_value(PARAM_BOOL, 'True if the query was deleted.'),
        ]);
    }
}
