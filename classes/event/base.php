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
 * report_viewed event class.
 *
 * @package    report_customsql
 * @copyright  2014
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_customsql\event;
defined('MOODLE_INTERNAL') || die();
/**
 * The report_viewed event class.
 *
 * @since     Moodle 2.7
 * @copyright 2014 Jason Peak
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
class report_base extends \core\event\base {

    /**
     * init function required by logging API.
     *
     * This base function encapsulates the
     * assignments common to the descendants
     * of this class.
     *
     * NB each inheriting class must assign its
     * own CRUD value, otherwise, API exception.
     */
    protected function init() {
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'report_customsql_queries';
    }

    /**
     * General function to return name string
     * for each inheriting class.
     *
     * Uses get_called_class() and a regular naming scheme
     * to determine the get_string param.
     *
     * @return string the name of the event
     */
    public static function get_name() {
        $class = explode('_', get_called_class());
        $action = $class[2];
        return get_string('eventreportname', 'report_customsql', $action);
    }

    /**
     * General function returning a
     * string parameterized with $this->action.
     *
     * @return string description string
     */
    public function get_description() {
        $a = new \stdClass();
        $a->userid   = $this->userid;
        $a->class    = $this->action;
        $a->objectid = $this->objectid;
        return get_string('eventreportdescription', 'report_customsql', $a);
    }

    /**
     * Always returns the view.php
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/report/customsql/view.php', array('id' => $this->objectid));
    }

    /**
     *
     *
     * @global stdClass $DB
     * @return stdClass[] {log} table rows
     */
    public function get_legacy_logdata() {
        global $DB;
        $class = explode('_', get_called_class());
        $action = '';
        switch($this->action){
            case 'viewed':
                $action = 'view';
                break;
            case 'deleted':
                $action = 'delete';
                break;
            case 'updated':
                $action = 'edit';
                break;
            default:
                return array();
        }
        $params = array(
            'course' => 0,
            'module' => 'admin',
            'action' => $action.' query',
            'cmid'   => 0
        );
        return $DB->get_records('log', $params);

    }
}