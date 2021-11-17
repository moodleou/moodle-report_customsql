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
 * Script to download the repot via the webservice token.
 *
 * @package    report_customaql
 * @copyright  2021 Catalyst IT
 * @author     Jason den Dulk <jasondendulk@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_MOODLE_COOKIES', true);
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');

$id = required_param('id', PARAM_INT);
$token = required_param('token', PARAM_ALPHANUM);
$queryparams = optional_param('queryparams', '', PARAM_RAW);
require_user_key_login('report_customsql', $id, $token);

$report = $DB->get_record('report_customsql_queries', ['id' => $id]);
if (!$report) {
    throw new \moodle_exception('invalidreportid', 'report_customsql', report_customsql_url('index.php'), $id);
}

$context = context_system::instance();
if ($report->capability != '') {
    require_capability($report->capability, $context);
}

if (!empty($queryparams)) {
    $queryparams = json_decode($queryparams, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \moodle_exception('invalidqueryparams', 'report_customsql');
    }
    $report->queryparams = report_customsql_merge_query_params($report->queryparams, $queryparams);
}

$csvtimestamp = \report_customsql_generate_csv($report, time());

require(__DIR__. '/download.php');
