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
 * Unit tests for the report_customsql implementation of the privacy API.
 *
 * @package    report_customsql
 * @copyright  2021 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \report_customsql\privacy\provider;
use core_privacy\local\request;

/**
 * Unit tests for the report_customsql implementation of the privacy API.
 *
 * @copyright  2021 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_customsql_privacy_testcase extends \core_privacy\tests\provider_testcase {

    public function setUp(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->component = 'report_customsql';
        $this->systemcontext = \context_system::instance();
        $this->user1 = $this->getDataGenerator()->create_user(['username' => 'manager1']);
        $this->create_customsql_row($this->user1->id, 'Report of user 1');
        $this->user2 = $this->getDataGenerator()->create_user(['username' => 'manager2']);
        $this->create_customsql_row($this->user2->id, 'Report of user 2');
        $this->user3 = $this->getDataGenerator()->create_user(['username' => 'manager3']);
        $this->create_customsql_row($this->user3->id, 'Report of user 3');
    }

    /**
     * Test for provider::get_contexts_for_userid().
     */
    public function test_get_contexts_for_userid() {
        $contexts = provider::get_contexts_for_userid($this->user1->id)->get_contexts();
        $this->assertEquals(1, count($contexts));
        $this->assertEquals(CONTEXT_SYSTEM, reset($contexts)->contextlevel);
    }

    /**
     * Test fetching users within a context.
     */
    public function test_get_users_in_context() {
        $userlist = new request\userlist($this->systemcontext, $this->component);
        provider::get_users_in_context($userlist);
        $actual = $userlist->get_userids();
        sort($actual);
        $expected = [$this->user1->id, $this->user2->id, $this->user3->id];
        sort($expected);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test Export all user data for the specified user.
     *
     * @return null
     */
    public function test_export_user_data() {
        $approvedcontextlist = new request\approved_contextlist($this->user1, $this->component, [$this->systemcontext->id]);
        provider::export_user_data($approvedcontextlist);
        $writer = request\writer::with_context($this->systemcontext);
        $this->assertTrue($writer->has_any_data());
        $subcontext = [
            get_string('privacy:metadata:reportcustomsqlqueries', 'report_customsql')
        ];
        $data = $writer->get_data($subcontext);
        $this->assertEquals('Report of user 1', reset($data)['displayname']);
    }

    /**
     * Test for provider::delete_data_for_all_users_in_context().
     */
    public function test_delete_data_for_all_users_in_context() {
        global $DB;

        $adminuserid = get_admin()->id;
        $count = $DB->count_records('report_customsql_queries', ['usermodified' => $adminuserid]);
        $this->assertEquals(0, $count);
        provider::delete_data_for_all_users_in_context($this->systemcontext);

        // All records should be set usermodified to adminuserid.
        $count = $DB->count_records('report_customsql_queries', ['usermodified' => $adminuserid]);
        $this->assertEquals(3, $count);
    }

    /**
     * Test for provider::delete_data_for_user().
     */
    public function test_delete_data_for_user() {
        global $DB;

        $count = $DB->count_records('report_customsql_queries', ['usermodified' => $this->user1->id]);
        $this->assertEquals(1, $count);
        $contextlist = provider::get_contexts_for_userid($this->user1->id);
        $approvedcontextlist = new request\approved_contextlist($this->user1, $this->component, $contextlist->get_contextids());
        provider::delete_data_for_user($approvedcontextlist);
        $count = $DB->count_records('report_customsql_queries', ['usermodified' => $this->user1->id]);
        $this->assertEquals(0, $count);
    }

    /**
     * Test that data for users in approved userlist is deleted.
     */
    public function test_delete_data_for_users() {
        global $DB;

        $userlistids = [$this->user1->id, $this->user2->id];

        // We just delete reports of user1 and user2.
        $approvedlist = new request\approved_userlist($this->systemcontext, $this->component, $userlistids);
        provider::delete_data_for_users($approvedlist);

        $count = $DB->count_records('report_customsql_queries', ['usermodified' => $this->user1->id]);
        $this->assertEquals(0, $count);
        $count = $DB->count_records('report_customsql_queries', ['usermodified' => $this->user2->id]);
        $this->assertEquals(0, $count);
    }

    /**
     * Create an entry in 'report_customsql_queries' table and return the id
     *
     * @param int $userid
     * @return int the new query id.
     */
    private function create_customsql_row($userid, $displayname) {
        global $DB;
        $report = new stdClass();
        $time = \report_customsql\utils::time();
        $report->displayname = $displayname;
        $report->description = 'test description';
        $report->descriptionformat = '1';
        $report->querysql = 'SELECT * FROM {report_customsql_queries} WHERE lastrun > 0';
        $report->queryparams = '';
        $report->querylimit = 10;
        $report->capability = 'report/customsql:view';
        $report->lastrun = $time;
        $report->lastexecutiontime = 1;
        $report->runable = 'manual';
        $report->at = 2;
        $report->emailto = '';
        $report->emailwhat = '';
        $report->categoryid = 1;
        $report->timemodified = $time;
        $report->timecreated = $time;
        $report->usermodified = $userid;

        return $DB->insert_record('report_customsql_queries', $report);
    }
}
