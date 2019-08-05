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
 * Admin settings tree setup for the Custom SQL admin report.
 *
 * @package block_externaldashboard
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ( $hassiteconfig ){
    $settings = new admin_settingpage( 'report_customsql_settings', 
            get_string('settings', 'report_customsql') );

    $ADMIN->add( 'reports', $settings );

    $setting = new admin_setting_configtext('report_customsql/max_records', get_string('max_records_title',
            'report_customsql'), get_string('max_records_desc', 'report_customsql'), 5000, PARAM_INT);
    $settings->add($setting);
}

$ADMIN->add('reports', new admin_externalpage('report_customsql',
        get_string('pluginname', 'report_customsql'),
        new moodle_url('/report/customsql/index.php'),
        'report/customsql:view'));
