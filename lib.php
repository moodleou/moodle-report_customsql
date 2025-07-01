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
 * Report customsql functions.
 *
 * @package    report_customsql
 * @author     Jwalit Shah <jwalitshah@catalyst-au.net>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Called by pluginfile, to download user generated reports via selected dataformat.
 * Generated reports can also be downloaded via webservice/pluginfile.
 *
 * Example url for download:
 * /pluginfile/<contextid>/report_customsql/download/<reportid>/?dataformat=csv&parameter1=value1&parameter2=value2
 * Example url for download via WS:
 * /webservice/pluginfile/<contextid>/report_customsql/download/<reportid>/?token=<wstoken>&dataformat=csv&parameter1=value1&parameter2=value2
 *
 * Exits if the required permissions are not satisfied.
 *
 * @param stdClass $course course object
 * @param stdClass $cm
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function report_customsql_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $DB;

    require_once(dirname(__FILE__) . '/locallib.php');

    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }

    if ($filearea != 'download') {
        return false;
    }

    $id = (int)array_shift($args);
    $dataformat = required_param('dataformat', PARAM_ALPHA);

    $report = $DB->get_record('report_customsql_queries', ['id' => $id]);
    if (!$report) {
        throw new moodle_exception('invalidreportid', 'report_customsql',
                report_customsql_url('index.php'), $id);
    }

    require_login();
    $context = context_system::instance();
    if (!empty($report->capability)) {
        require_capability($report->capability, $context);
    }

    $queryparams = report_customsql_get_query_placeholders_and_field_names($report->querysql);
    // Get any query param values that are given in the URL.
    $paramvalues = [];
    foreach ($queryparams as $queryparam => $notused) {
        $value = optional_param($queryparam, null, PARAM_RAW);
        if ($value !== null && $value !== '') {
            $paramvalues[$queryparam] = $value;
        }
        $report->queryparams = serialize($paramvalues);
    }

    // Check timestamp param.
    $csvtimestamp = optional_param('timestamp', null, PARAM_INT);
    if ($csvtimestamp === null) {
        $runtime = time();
        if ($report->runable !== 'manual') {
            $runtime = $report->lastrun;
        }
        $csvtimestamp = report_customsql_generate_csv($report, $runtime, true);
    }
    list($csvfilename) = report_customsql_csv_filename($report, $csvtimestamp);

    $handle = fopen($csvfilename, 'r');
    if ($handle === false) {
        throw new moodle_exception('unknowndownloadfile', 'report_customsql',
                report_customsql_url('view.php?id=' . $id));
    }

    $fields = report_customsql_read_csv_row($handle);

    $rows = new ArrayObject([]);
    while ($row = report_customsql_read_csv_row($handle)) {
        $rows->append($row);
    }

    fclose($handle);

    $filename = clean_filename($report->displayname);
    // Also strip commas. clean_filename does not remove ,s, but they
    // can stop downloads from working in some browsers.
    $filename = str_replace(',', '', $filename);

    \core\dataformat::download_data($filename, $dataformat, $fields, $rows->getIterator(), function(array $row) use ($dataformat) {
        // HTML export content will need escaping.
        if (strcasecmp($dataformat, 'html') === 0) {
            $row = array_map(function($cell) {
                return s($cell);
            }, $row);
        }

        return $row;
    });
    die;
}
