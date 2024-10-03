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
class get_query_results extends \external_api {
    /**
     * Parameter declaration.
     *
     * @return \external_function_parameters Parameters
     */
    public static function execute_parameters(): \external_function_parameters {
        return new \external_function_parameters([
            'queryid' => new \external_value(PARAM_INT, 'The id of the query', VALUE_REQUIRED),
            'format' => new \external_value(PARAM_TEXT, 'The format of the file to download', VALUE_DEFAULT, 'csv'),
        ]);
    }

    /**
     * Returns the query file results.
     *
     * @param int $queryid The id of the query.
     * @param string $format The format of the file to download.
     * @return array
     */
    public static function execute(int $queryid, $format): array {
        global $CFG, $DB, $USER;

        // This will assign the validated values to the variables.
        $params = self::validate_parameters(self::execute_parameters(), ['queryid' => $queryid]);
        $queryid = $params['queryid'];

        // Validate the context.
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('report/customsql:definequeries', $context);

        if (!$query = $DB->get_record('report_customsql_queries', ['id' => $queryid]) ) {
            throw new \moodle_exception('error:querynotfound', 'report_customsql');
        }
        // Get the files from the directory.
        $resultsdir = $CFG->dataroot . "/admin_report_customsql/";
        $dataformat = class_exists('\core\dataformat_' . $format);
        if (!empty($format) && !empty($dataformat)) {
            throw new \moodle_exception('error:invalidformat', 'report_customsql');
        }

        $contextid = \context_system::instance()->id;
        $url = new \moodle_url('/webservice/pluginfile.php/' . $contextid . '/report_customsql/download/' . $queryid . '/');
        if ($query->runable == 'manual') {
            $files = glob($resultsdir . "temp/$queryid/*");
        } else {
            if (!empty($query->customdir)) {
                $files = glob($query->customdir . "/$queryid-*");
            } else {
                $files = glob($resultsdir . "$queryid/*");
            }
        }

        $response = [];
        foreach ($files as $file) {
            $fileinfo = pathinfo($file);
            $filename = $fileinfo['filename'];
            $date = !empty($query->customdir) ? str_replace($queryid . '-', '', $filename) : $filename;
            $humandate = date('Y-m-d H:i:s', $date);
            if (empty($humandate)) {
                throw new \moodle_exception('error:invaliddate', 'report_customsql');
            }
            $donwloadurl = new \moodle_url($url, ['dataformat' => $format, 'timestamp' => $date]);
            $response[] = ['date' => $humandate, 'downloadurl' => $donwloadurl->out(false)];
        }

        return ['results' => $response];
    }

    /**
     * Returns the query details if exists.
     *
     * @return \external_description Result type
     */
    public static function execute_returns(): \external_description {
        return new \external_single_structure([
            'results' => new \external_multiple_structure(new \external_single_structure([
                'date' => new \external_value(PARAM_TEXT, 'The date of the report.'),
                'downloadurl' => new \external_value(PARAM_URL, 'The download URL of the file.'),
            ])),
        ]);
    }
}
