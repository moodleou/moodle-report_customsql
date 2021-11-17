<?php
// This file is part of Moodle - http://moodle.org/  <--change
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
 * External function to deliver the results of a custom query
 *
 * @package    report_customaql
 * @copyright  2021 Catalyst IT
 * @author     Jason den Dulk <jasondendulk@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_customsql\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once(__DIR__ . '/../../locallib.php');


class get_query_result extends \external_api {

    /**
     * Executes the function
     *
     * @param string $id The ID of the query.
     * @param string $queryparams Parameters to be passed to the query, in JSON format.
     * @param string $dataformat The data format to download the data in.
     * @return array The URL to retrieve the data.
     *
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     * @throws \required_capability_exception
     * @throws \restricted_context_exception
     */
    public static function execute(int $id, string $queryparams = '', string $dataformat, int $tokenexpiry = 3600): array {
        global $CFG, $DB, $USER;

        $params = self::validate_parameters(
            self::execute_parameters(),
            compact('id', 'queryparams', 'dataformat', 'tokenexpiry')
        );
        $context = \context_system::instance();
        self::validate_context($context);

        $report = $DB->get_record('report_customsql_queries', ['id' => $params['id']]);
        if (!$report) {
            throw new \moodle_exception('invalidreportid', 'report_customsql', report_customsql_url('index.php'), $id);
        }

        if ($report->capability != '') {
            require_capability($report->capability, $context);
        }

        $usertokenexpiry = time();
        if ($tokenexpiry === null) {
            $usertokenexpiry += REPORT_CUSTOMSQL_TOKEN_VALID_DURATION;
        } else {
            $usertokenexpiry += $tokenexpiry;
        }

        $token = get_user_key('report_customsql', $USER->id, $id, null, $usertokenexpiry);
        $urlparams = [
            'id' => $id,
            'token' => $token,
            'dataformat' => $dataformat,
        ];
        if (!empty($queryparams)) {
            $urlparams['queryparams'] = $queryparams;
        }
        $returnurl = new \moodle_url('/report/customsql/tokendownload.php', $urlparams);
        return ['url' => $returnurl->out(false)];
    }

    /**
     * Description of the function parameters.
     *
     * @return \external_function_parameters
     */
    public static function execute_parameters(): \external_function_parameters {
        return new \external_function_parameters([
                'id' => new \external_value(PARAM_INT, 'ID of query'),
                'queryparams' => new \external_value(PARAM_RAW, 'Query params in JSON format', VALUE_DEFAULT, '', NULL_ALLOWED),
                'dataformat' => new \external_value(PARAM_RAW, 'The data format', VALUE_DEFAULT,
                                                    REPORT_CUSTOMSQL_DEFAULT_DATAFORMAT),
                'tokenexpiry' => new \external_value(PARAM_INT, 'Expiry of the token in seconds', VALUE_DEFAULT,
                                                     3600, NULL_ALLOWED),
        ]);
    }

    /**
     * Description of the function return value.
     *
     * @return \external_description
     */
    public static function execute_returns(): \external_description {
        return new \external_single_structure([
            'url' => new \external_value(PARAM_RAW, 'The data retrieval URL.')
        ]);
    }
}

