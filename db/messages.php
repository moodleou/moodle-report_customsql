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
 * Message providers for report_customsql.
 *
 * @package    report_customsql
 * @category   message
 * @copyright  2025 ISB Bayern
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$messageproviders = [
    // Legacy notification messages.
    'notification' => [
        'capability' => 'report/customsql:view',
    ],
    // Successful background execution.
    'executioncompleted' => [
        'capability' => 'report/customsql:executebackground',
    ],
    // Failed background execution.
    'executionfailed' => [
        'capability' => 'report/customsql:executebackground',
    ],
];
