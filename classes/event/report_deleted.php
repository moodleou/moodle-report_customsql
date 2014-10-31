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
 * report_deleted event class.
 *
 * @package    report_customsql
 * @copyright  2014
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_customsql\event;
defined('MOODLE_INTERNAL') || die();
require_once 'base.php';
/**
 * The report_deleted event class.
 *
 * @since     Moodle 2.7
 * @copyright 2014 Jason Peak
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
class report_deleted extends \report_customsql\event\report_base {
    protected function init() {
        parent::init();
        $this->data['crud'] = 'd';
    }
}