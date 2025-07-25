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

use core_external\external_api;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');


/**
 * Tests for the get_users web service.
 *
 * @package   report_customsql
 * @category  external
 * @copyright 2020 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \report_customsql\external\get_users
 * @runTestsInSeparateProcesses
 */
final class external_get_users_test extends \externallib_advanced_testcase {

    /**
     * Sets up test users with specific roles and permissions.
     *
     * @return array An array containing the created users: admin, manager, and course creator.
     */
    protected function setup_users(): array {
        global $DB, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $context = \context_system::instance();

        // Set up some permissions on two site-wide roles.
        $managerroleid = $DB->get_field('role', 'id', ['shortname' => 'manager']);
        $coursecreateorroleid = $DB->get_field('role', 'id', ['shortname' => 'coursecreator']);

        role_change_permission($managerroleid, $context, 'moodle/site:viewreports', CAP_ALLOW);
        role_change_permission($managerroleid, $context, 'report/customsql:view', CAP_ALLOW);
        role_change_permission($coursecreateorroleid, $context, 'report/customsql:view', CAP_ALLOW);

        // Create some users.
        $DB->update_record('user', (object)
                ['id' => $USER->id, 'firstname' => 'Admin', 'lastname' => 'User']);
        $admin = $DB->get_record('user', ['id' => $USER->id]);
        $manager = $generator->create_user(
                ['firstname' => 'The', 'lastname' => 'Manager', 'email' => 'manager@example.com']);
        $coursecreateor = $generator->create_user(
                ['firstname' => 'Coarse', 'lastname' => 'Creator', 'email' => 'cc@example.com']
        );

        $generator->role_assign($managerroleid, $manager->id);
        $generator->role_assign($coursecreateorroleid, $coursecreateor->id);

        return [$admin, $manager, $coursecreateor];
    }

    public function test_get_users_site_config(): void {
        global $OUTPUT;
        $defaultuserimage = $OUTPUT->image_url('u/f2');

        [$admin] = $this->setup_users();

        $result = get_users::execute('', 'moodle/site:config');
        $result = external_api::clean_returnvalue(get_users::execute_returns(), $result);

        $this->assertEquals([
            [
                'id' => $admin->id,
                'fullname' => fullname($admin),
                'identity' => 'admin@example.com',
                'hasidentity' => true,
                'profileimageurlsmall' => $defaultuserimage,
            ],
        ], $result);
    }

    public function test_get_users_site_viewreports(): void {
        global $OUTPUT;
        $defaultuserimage = $OUTPUT->image_url('u/f2');

        [$admin, $manager] = $this->setup_users();

        $result = get_users::execute('', 'moodle/site:viewreports');
        $result = external_api::clean_returnvalue(get_users::execute_returns(), $result);

        $this->assertEquals([
            [
                'id' => $manager->id,
                'fullname' => fullname($manager),
                'identity' => 'manager@example.com',
                'hasidentity' => true,
                'profileimageurlsmall' => $defaultuserimage,
            ],
            [
                'id' => $admin->id,
                'fullname' => fullname($admin),
                'identity' => 'admin@example.com',
                'hasidentity' => true,
                'profileimageurlsmall' => $defaultuserimage,
            ],
        ], $result);
    }

    public function test_get_users_customsql_view(): void {
        global $OUTPUT;
        $defaultuserimage = $OUTPUT->image_url('u/f2');

        [$admin, $manager, $coursecreateor] = $this->setup_users();

        $result = get_users::execute('', 'report/customsql:view');
        $result = external_api::clean_returnvalue(get_users::execute_returns(), $result);

        $this->assertEquals([
            [
                'id' => $coursecreateor->id,
                'fullname' => fullname($coursecreateor),
                'identity' => 'cc@example.com',
                'hasidentity' => true,
                'profileimageurlsmall' => $defaultuserimage,
            ],
            [
                'id' => $manager->id,
                'fullname' => fullname($manager),
                'identity' => 'manager@example.com',
                'hasidentity' => true,
                'profileimageurlsmall' => $defaultuserimage,
            ],
            [
                'id' => $admin->id,
                'fullname' => fullname($admin),
                'identity' => 'admin@example.com',
                'hasidentity' => true,
                'profileimageurlsmall' => $defaultuserimage,
            ],
        ], $result);
    }

    public function test_get_users_serch_without_admins(): void {
        global $OUTPUT;
        $defaultuserimage = $OUTPUT->image_url('u/f2');

        global $OUTPUT;

        [, $manager] = $this->setup_users();

        $result = get_users::execute('Man', 'report/customsql:view');
        $result = external_api::clean_returnvalue(get_users::execute_returns(), $result);

        $this->assertEquals([
                [
                        'id' => $manager->id,
                        'fullname' => fullname($manager),
                        'identity' => 'manager@example.com',
                        'hasidentity' => true,
                        'profileimageurlsmall' => $defaultuserimage,
                ],
        ], $result);
    }

    public function test_get_users_serch_with_admin(): void {
        global $OUTPUT;
        $defaultuserimage = $OUTPUT->image_url('u/f2');

        [$admin] = $this->setup_users();

        $result = get_users::execute('n U', 'report/customsql:view');
        $result = external_api::clean_returnvalue(get_users::execute_returns(), $result);

        $this->assertEquals([
            [
                'id' => $admin->id,
                'fullname' => fullname($admin),
                'identity' => 'admin@example.com',
                'hasidentity' => true,
                'profileimageurlsmall' => $defaultuserimage,
            ],
        ], $result);
    }
}
