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
 * Behat steps for the custom SQL report.
 *
 * All these steps include the phrase 'custom SQL report'.
 *
 * @package report_customsql
 * @copyright 2019 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test because this file is required by Behat.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\PyStringNode as PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ExpectationException;

/**
 * Behat steps for the the custom SQL report.
 *
 * All these steps include the phrase 'custom SQL report'.
 */
class behat_report_customsql extends behat_base {

    /**
     * Convert page names to URLs for steps like 'When I am on the "[page name]" page'.
     *
     * Recognised page names are:
     * | report index | the list of all reports. |
     *
     * @param string $page name of the page, with the component name removed e.g. 'Admin notification'.
     * @return moodle_url the corresponding URL.
     * @throws Exception with a meaningful error message if the specified page cannot be found.
     */
    protected function resolve_page_url(string $page): moodle_url {
        switch (strtolower($page)) {
            case 'report index':
                return new moodle_url('/report/customsql/index.php');
            default:
                throw new Exception('Unrecognised quiz page type "' . $page . '."');
        }
    }

    /**
     * Create a new report in the database.
     *
     * For example
     * Given the following custom sql report exists:
     *   | name     | Test report            |
     *   | querysql | SELECT * FROM {config} |
     *   | category | Miscellaneous          |
     *
     * if present, category name is looked up in the database to get the id.
     * @Given /^the following custom sql report exists:$/
     * @param TableNode $data Supplied data
     */
    public function the_following_custom_sql_report_exists(TableNode $data) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/report/customsql/locallib.php');

        $report = $data->getRowsHash();

        // Report name.
        if (isset($report['name'])) {
            $report['displayname'] = $report['name'];
            unset($report['name']);
        } else {
            throw new Exception('A report name must be given.');
        }

        // Description (defaults to blank).
        if (!isset($report['description'])) {
            $report['description'] = '';
        }
        if (!isset($report['descriptionformat'])) {
            $report['descriptionformat'] = FORMAT_HTML;
        }

        // Query SQL.
        if (!isset($report['querysql'])) {
            throw new Exception('The report SQL must be given as querysql.');
        }

        // Fix test queries containing CHR for MySQL & chums.
        if ($DB->get_dbfamily() == 'mysql' && stripos($report['querysql'], 'CHR') !== false) {
            $report['querysql'] = str_ireplace('CHR', 'CHAR', $report['querysql']);
        }

        // Category.
        if (isset($report['category'])) {
            $report['categoryid'] = $this->get_category_id_by_name($report['category']);
            unset($report['category']);
        } else {
            $report['categoryid'] = $this->get_category_id_by_name(
                    get_string('defaultcategory', 'report_customsql'));
        }

        // Capability.
        if (isset($report['capability'])) {
            // If a capability was passed in, check it is valid.
            if (!isset(report_customsql_capability_options()[$report['capability']])) {
                throw new Exception('Capability ' . $report['capability'] . ' is not a valid choice.');
            }
        } else {
            // Otherwise use a default.
            $report['capability'] = 'moodle/site:config';
        }

        // Runnable.
        if (isset($report['runable']) &&
                !in_array($report['runable'], report_customsql_runable_options())) {
            throw new Exception('Invalid runable value ' . $report['capability'] . '.');
        } else {
            $report['runable'] = 'manual';
        }

        // Time modified.
        if (!isset($report['timemodified'])) {
            $report['timemodified'] = \report_customsql\utils::time();
        }

        // Time created.
        if (!isset($report['timecreated'])) {
            $report['timecreated'] = $report['timemodified'];
        }

        // User modified.
        if (!isset($report['usermodified'])) {
            $report['usermodified'] = 'admin';
        }
        $user = $DB->get_record('user', ['username' => $report['usermodified']], 'id', MUST_EXIST);
        $report['usermodified'] = $user->id;

        $this->save_new_report($report);
    }

    /**
     * Create a new report in the database.
     *
     * For example
     * Given the custom sql report "Test report" exists with SQL:
     * """
     *   SELECT *
     *   FROM {config}
     * """
     *
     * Creates a report in the default category with long SQL.
     *
     * @Given /^the custom sql report "(?P<REPORT_NAME>[^"]*)" exists with SQL:$/
     * @param string $reportname the name of the report to go to.
     * @param PyStringNode $querysql The query SQL
     */
    public function the_custom_sql_report_x_exists(string $reportname, PyStringNode $querysql) {
        $report = [
            'displayname' => $reportname,
            'description' => '',
            'descriptionformat' => FORMAT_HTML,
            'querysql' => (string)$querysql,
            'categoryid' => $this->get_category_id_by_name(
                    get_string('defaultcategory', 'report_customsql')),
            'capability' => 'moodle/site:config',
            'runable' => 'manual',
        ];

        $this->save_new_report($report);
    }

    /**
     * Helper used by other methods to save a report.
     *
     * @param array $report the report to save.
     */
    protected function save_new_report(array $report) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/report/customsql/locallib.php');

        $params = report_customsql_get_query_placeholders_and_field_names($report['querysql']);
        if ($params) {
            $report['queryparams'] = serialize($params);
        }

        $DB->insert_record('report_customsql_queries', (object) $report);
    }

    /**
     * Create a new report category in the database.
     *
     * @Given /^the custom sql report category "(?P<CATEGORY_NAME>[^"]*)" exists:$/
     * @param string $name the name of the category to create.
     */
    public function the_custom_sql_report_cateogry_exists(string $name) {
        global $DB;
        $DB->insert_record('report_customsql_categories', (object) ['name' => $name]);
    }

    /**
     * Views a report.
     *
     * Goes straight to the URL $CFG->wwwroot/report/customsql/view.php?id=123
     *
     * @When /^I view the "(?P<REPORT_NAME>[^"]*)" custom sql report$/
     * @param string $reportname the name of the report to go to.
     */
    public function i_view_the_x_custom_sql_report(string $reportname) {
        $report = $this->get_report_by_name($reportname);
        $this->getSession()->visit($this->locate_path('/report/customsql/view.php?id=' . $report->id));
    }

    /**
     * Simulates going directly to a report with certain parameters in the URL.
     *
     * For example:
     * When I view the "Frog" custom sql report with these URL parameters:
     *   | frogname | freddy |
     *   | colour   | green  |
     * this goes to the URL $CFG->wwwroot/report/customsql/view.php?id=123&frogname=freddy&colour=green.
     *
     * @When /^I view the "(?P<REPORT_NAME>[^"]*)" custom sql report with these URL parameters:$/
     * @param string $reportname the name of the report to go to.
     * @param TableNode $data two columns, name and value, params to add to the URL.
     */
    public function i_view_the_x_custom_sql_report_with_these_url_parameters(string $reportname, TableNode $data) {
        $report = $this->get_report_by_name($reportname);

        $queryparams = ['id=' . $report->id];
        foreach ($data->getRows() as $rowdata) {
            if (count($rowdata) != 2) {
                throw new Exception('Table of params must have two values in each row, query parameter name and value.');
            }

            $name = clean_param($rowdata[0], PARAM_ALPHANUMEXT);
            if ($name !== $rowdata[0]) {
                throw new Exception('Parameter names must match PARAM_ALPHANUMEXT.');
            }
            $queryparams[] = $name . '=' . urlencode($rowdata[1]);
        }

        $this->getSession()->visit($this->locate_path('/report/customsql/view.php?' .
                implode('&', $queryparams)));
    }

    /**
     * Sets a fake time for the report_customsql
     *
     * @param string $time time in a format that strtotime will understand
     * @Given /^the Ad-hoc database queries thinks the time is "(?P<strtotime_string>.+)"$/
     */
    public function adhoc_database_queries_thinks_the_time_is($time) {
        $value = strtotime($time);
        if ($value === false) {
            throw new \Behat\Mink\Exception\ExpectationException('specified time is not valid', $this->getSession());
        }
        set_config('behat_fixed_time', $value, 'report_customsql');
    }

    /**
     * Simulates downloading an empty report to ensure it shows table headers.
     *
     * For example:
     * When downloading the empty custom sql report "Frog" it contains the headers "frogname,freddy"
     *
     * @Then /^downloading custom sql report "(?P<REPORT_NAME>[^"]*)" returns a file with headers "([^"]*)"$/
     * @param string $reportname the name of the report to go to.
     * @param string $headers the headers that shuold be returned.
     */
    public function downloading_custom_sql_report_x_returns_a_file_with_headers(string $reportname, string $headers) {
        $report = $this->get_report_by_name($reportname);
        $url = new \moodle_url('/pluginfile.php/1/report_customsql/download/' . $report->id, ['dataformat' => 'csv']);

        $session = $this->getSession()->getCookie('MoodleSession');
        $filecontent = trim(download_file_content($url, ['Cookie' => 'MoodleSession=' . $session]));
        $filecontent = core_text::trim_utf8_bom($filecontent);
        if ($filecontent != $headers) {
            throw new ExpectationException(
                    "File headers: $filecontent did not match expected: $headers", $this->getSession());
        }
    }

    /**
     * Find a report by name and get all the details.
     *
     * @param string $reportname the report name to find.
     * @return stdClass the report data
     */
    protected function get_report_by_name(string $reportname): stdClass {
        global $DB;
        return $DB->get_record('report_customsql_queries', ['displayname' => $reportname], '*', MUST_EXIST);
    }

    /**
     * Find a category by name and return its id.
     *
     * @param string $name the category name to find.
     * @return int the category id.
     */
    protected function get_category_id_by_name(string $name): int {
        global $DB;
        return $DB->get_field('report_customsql_categories', 'id', ['name' => $name], MUST_EXIST);
    }
}
