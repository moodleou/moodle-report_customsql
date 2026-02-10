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
 * Unit tests for execute_query_adhoc task.
 *
 * @package    report_customsql
 * @copyright  2025 Moodle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_customsql;

use report_customsql\task\execute_query_adhoc;

/**
 * Unit tests for execute_query_adhoc task.
 *
 * @package    report_customsql
 * @copyright  2025 Moodle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \report_customsql\task\execute_query_adhoc
 */
final class adhoc_task_test extends \advanced_testcase {
    /**
     * Setup for each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Test successful query execution.
     */
    public function test_successful_execution(): void {
        global $DB, $USER;

        ob_start();

        // Create test query.
        $queryid = $this->create_test_query('SELECT id, username FROM {user} LIMIT 5');

        // Create execution record.
        $executionid = $DB->insert_record('report_customsql_executions', [
            'queryid' => $queryid,
            'userid' => $USER->id,
            'executionmode' => 'background',
            'status' => 'queued',
            'timecreated' => time(),
            'cancelled' => 0,
        ]);

        // Execute task.
        $task = new execute_query_adhoc();
        $task->set_custom_data([
            'executionid' => $executionid,
        ]);
        $task->execute();

        ob_end_clean();

        // Verify execution completed.
        $execution = $DB->get_record('report_customsql_executions', ['id' => $executionid]);
        $this->assertEquals('completed', $execution->status);
        $this->assertNotEmpty($execution->filename);
        $this->assertGreaterThan(0, $execution->filesize);
        $this->assertGreaterThan(0, $execution->rowsreturned);
        $this->assertGreaterThanOrEqual(0, $execution->executiontime); // Can be 0 for fast queries.
        $this->assertNotEmpty($execution->timecompleted);

        // Verify file was created.
        $context = \context_system::instance();
        $fs = get_file_storage();
        $file = $fs->get_file(
            $context->id,
            'report_customsql',
            'execution',
            $executionid,
            '/',
            $execution->filename
        );
        $this->assertNotFalse($file);
        $this->assertEquals($execution->filesize, $file->get_filesize());

        // Verify CSV content (note: CSV headers might be quoted).
        $content = $file->get_content();
        $this->assertStringContainsString('id', $content);
        $this->assertStringContainsString('username', $content);
    }

    /**
     * Test execution with SQL error.
     */
    public function test_execution_with_sql_error(): void {
        global $DB, $USER;

        ob_start();

        // Create query with invalid SQL.
        $queryid = $this->create_test_query('SELECT invalid_column FROM {nonexistent_table}');

        // Create execution record.
        $executionid = $DB->insert_record('report_customsql_executions', [
            'queryid' => $queryid,
            'userid' => $USER->id,
            'executionmode' => 'background',
            'status' => 'queued',
            'timecreated' => time(),
            'cancelled' => 0,
        ]);

        // Execute task - this will throw exception but handle it internally.
        $task = new execute_query_adhoc();
        $task->set_custom_data([
            'executionid' => $executionid,
        ]);
        try {
            $task->execute();
        } catch (\Exception $e) {
            // Expected to throw - no action needed.
            $e = $e; // Suppress empty catch warning.
        }

        ob_end_clean();

        // Verify execution failed.
        $execution = $DB->get_record('report_customsql_executions', ['id' => $executionid]);
        $this->assertEquals('failed', $execution->status);
        $this->assertNotEmpty($execution->errormessage);

        // Verify error file was created.
        $context = \context_system::instance();
        $fs = get_file_storage();
        $file = $fs->get_file(
            $context->id,
            'report_customsql',
            'execution',
            $executionid,
            '/',
            $execution->filename
        );
        $this->assertNotFalse($file);

        // Verify error CSV content (check for error marker).
        $content = $file->get_content();
        $this->assertStringContainsString('error', strtolower($content));
        // Error message is truncated/escaped in CSV, so just check for key parts.
        $this->assertStringContainsString('Error reading from database', $content);
    }

    /**
     * Test execution cancellation.
     */
    public function test_execution_cancellation(): void {
        global $DB, $USER;

        // Create test query with many rows to allow cancellation.
        $queryid = $this->create_test_query('SELECT id FROM {user}');

        // Create execution record.
        $executionid = $DB->insert_record('report_customsql_executions', [
            'queryid' => $queryid,
            'userid' => $USER->id,
            'executionmode' => 'background',
            'status' => 'queued',
            'timecreated' => time(),
            'cancelled' => 1, // Pre-cancel it.
        ]);

        // Execute task.
        $task = new execute_query_adhoc();
        $task->set_custom_data([
            'executionid' => $executionid,
        ]);
        $task->execute();

        // Verify execution detected cancellation and stopped early.
        $execution = $DB->get_record('report_customsql_executions', ['id' => $executionid]);
        // Since it was cancelled before running, it remains in queued state.
        $this->assertEquals('queued', $execution->status);
        $this->assertEquals(1, $execution->cancelled);

        // Verify no file was created.
        $this->assertEmpty($execution->filename);
    }

    /**
     * Test execution with query parameters.
     */
    public function test_execution_with_parameters(): void {
        global $DB, $USER;

        ob_start();

        // Create test query with parameter placeholders.
        $queryid = $this->create_test_query('SELECT id FROM {user} WHERE id = :userid LIMIT 1');

        // Create execution record with parameters.
        $queryparams = json_encode(['userid' => $USER->id]);
        $executionid = $DB->insert_record('report_customsql_executions', [
            'queryid' => $queryid,
            'userid' => $USER->id,
            'executionmode' => 'background',
            'status' => 'queued',
            'queryparams' => $queryparams,
            'timecreated' => time(),
            'cancelled' => 0,
        ]);

        // Execute task.
        $task = new execute_query_adhoc();
        $task->set_custom_data([
            'executionid' => $executionid,
        ]);
        $task->execute();

        ob_end_clean();

        // Verify execution completed.
        $execution = $DB->get_record('report_customsql_executions', ['id' => $executionid]);
        $this->assertEquals('completed', $execution->status);
        $this->assertEquals(1, $execution->rowsreturned);
    }

    /**
     * Test CSV format with special characters.
     */
    public function test_csv_with_special_characters(): void {
        global $DB, $USER;

        ob_start();

        // Create a user with special characters in name.
        $testuser = $this->getDataGenerator()->create_user([
            'firstname' => 'Test "Quote"',
            'lastname' => 'User, Comma',
            'email' => 'test@example.com',
        ]);

        // Create test query.
        $queryid = $this->create_test_query(
            'SELECT firstname, lastname FROM {user} WHERE id = :userid'
        );

        // Create execution record.
        $queryparams = json_encode(['userid' => $testuser->id]);
        $executionid = $DB->insert_record('report_customsql_executions', [
            'queryid' => $queryid,
            'userid' => $USER->id,
            'executionmode' => 'background',
            'status' => 'queued',
            'queryparams' => $queryparams,
            'timecreated' => time(),
            'cancelled' => 0,
        ]);

        // Execute task.
        $task = new execute_query_adhoc();
        $task->set_custom_data([
            'executionid' => $executionid,
        ]);
        $task->execute();

        ob_end_clean();

        // Verify execution completed.
        $execution = $DB->get_record('report_customsql_executions', ['id' => $executionid]);
        $this->assertEquals('completed', $execution->status);

        // Verify CSV escaping.
        $context = \context_system::instance();
        $fs = get_file_storage();
        $file = $fs->get_file(
            $context->id,
            'report_customsql',
            'execution',
            $executionid,
            '/',
            $execution->filename
        );

        $content = $file->get_content();
        // CSV should escape quotes by doubling them.
        $this->assertStringContainsString('"Test ""Quote"""', $content);
        // Comma should be inside quotes.
        $this->assertStringContainsString('"User, Comma"', $content);
    }

    /**
     * Test memory efficiency with large dataset.
     *
     * This test verifies that the streaming approach doesn't load
     * all records into memory at once.
     */
    public function test_memory_efficiency(): void {
        global $DB, $USER;

        ob_start();

        // Get memory usage before.
        $memorybefore = memory_get_usage();

        // Create query that returns many records.
        $queryid = $this->create_test_query('SELECT id, username FROM {user}');

        // Create some test users to have more data.
        for ($i = 0; $i < 100; $i++) {
            $this->getDataGenerator()->create_user();
        }

        // Create execution record.
        $executionid = $DB->insert_record('report_customsql_executions', [
            'queryid' => $queryid,
            'userid' => $USER->id,
            'executionmode' => 'background',
            'status' => 'queued',
            'timecreated' => time(),
            'cancelled' => 0,
        ]);

        // Execute task.
        $task = new execute_query_adhoc();
        $task->set_custom_data([
            'executionid' => $executionid,
        ]);
        $task->execute();

        ob_end_clean();

        // Get memory usage after.
        $memoryafter = memory_get_usage();
        $memoryused = $memoryafter - $memorybefore;

        // Verify execution completed.
        $execution = $DB->get_record('report_customsql_executions', ['id' => $executionid]);
        $this->assertEquals('completed', $execution->status);

        // Memory usage should be reasonable (less than 10MB).
        // Streaming should prevent loading all data at once.
        $this->assertLessThan(
            10 * 1024 * 1024,
            $memoryused,
            'Memory usage too high - streaming may not be working'
        );
    }

    /**
     * Test message sending on completion.
     */
    public function test_completion_message_sent(): void {
        global $DB, $USER;

        ob_start();

        // Enable messaging.
        set_config('messaging', 1);

        // Prevent actual message sending in tests.
        $messagesink = $this->redirectMessages();

        // Create test query.
        $queryid = $this->create_test_query('SELECT id FROM {user} LIMIT 1');

        // Create execution record.
        $executionid = $DB->insert_record('report_customsql_executions', [
            'queryid' => $queryid,
            'userid' => $USER->id,
            'executionmode' => 'background',
            'status' => 'queued',
            'timecreated' => time(),
            'cancelled' => 0,
        ]);

        // Execute task.
        $task = new execute_query_adhoc();
        $task->set_custom_data([
            'executionid' => $executionid,
        ]);
        $task->execute();

        ob_end_clean();

        // Verify message was sent.
        $messages = $messagesink->get_messages();
        $this->assertCount(1, $messages);
        $this->assertEquals('executioncompleted', $messages[0]->eventtype);
        $this->assertEquals($USER->id, $messages[0]->useridto);
    }

    /**
     * Test message sending on failure.
     */
    public function test_failure_message_sent(): void {
        global $DB, $USER;

        ob_start();

        // Enable messaging.
        set_config('messaging', 1);

        // Prevent actual message sending in tests.
        $messagesink = $this->redirectMessages();

        // Create query with invalid SQL.
        $queryid = $this->create_test_query('SELECT invalid FROM {nowhere}');

        // Create execution record.
        $executionid = $DB->insert_record('report_customsql_executions', [
            'queryid' => $queryid,
            'userid' => $USER->id,
            'executionmode' => 'background',
            'status' => 'queued',
            'timecreated' => time(),
            'cancelled' => 0,
        ]);

        // Execute task.
        $task = new execute_query_adhoc();
        $task->set_custom_data([
            'executionid' => $executionid,
        ]);
        try {
            $task->execute();
        } catch (\Exception $e) {
            // Expected to throw - no action needed.
            $e = $e; // Suppress empty catch warning.
        }

        ob_end_clean();

        // Verify message was sent.
        $messages = $messagesink->get_messages();
        $this->assertCount(1, $messages);
        $this->assertEquals('executionfailed', $messages[0]->eventtype);
        $this->assertEquals($USER->id, $messages[0]->useridto);
    }

    /**
     * Helper method to create a test query.
     *
     * @param string $sql SQL query
     * @return int Query ID
     */
    protected function create_test_query(string $sql): int {
        global $DB, $USER;

        $category = $DB->insert_record('report_customsql_categories', [
            'name' => 'Test Category',
        ]);

        return $DB->insert_record('report_customsql_queries', [
            'displayname' => 'Test Query',
            'description' => 'Test Description',
            'querysql' => $sql,
            'queryparams' => '',
            'querylimit' => 5000,
            'capability' => '',
            'lastrun' => 0,
            'lastexecutiontime' => 0,
            'runable' => 'manual',
            'singlerow' => 0,
            'at' => '',
            'emailto' => '',
            'emailwhat' => '',
            'categoryid' => $category,
            'customdir' => '',
            'usermodified' => $USER->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
    }

    /**
     * Test scheduled report execution with customdir.
     *
     * @covers \report_customsql\task\execute_query_adhoc::execute_with_customdir
     */
    public function test_scheduled_execution_with_customdir(): void {
        global $DB, $USER, $CFG;

        ob_start();

        // Create custom directory for testing.
        $customdir = make_temp_directory('report_customsql_test');

        // Create test query with customdir.
        $category = $DB->insert_record('report_customsql_categories', ['name' => 'Test Category']);
        $queryid = $DB->insert_record('report_customsql_queries', [
            'displayname' => 'Scheduled Test Query',
            'description' => 'Test scheduled query',
            'querysql' => 'SELECT id, username FROM {user} LIMIT 2',
            'queryparams' => '',
            'querylimit' => 5000,
            'capability' => '',
            'lastrun' => 0,
            'lastexecutiontime' => 0,
            'runable' => 'daily',
            'singlerow' => 0,
            'at' => '0',
            'emailto' => '',
            'emailwhat' => '',
            'categoryid' => $category,
            'customdir' => $customdir,
            'usermodified' => $USER->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        // Create execution record.
        $executionid = $DB->insert_record('report_customsql_executions', [
            'queryid' => $queryid,
            'userid' => $USER->id,
            'executionmode' => 'background',
            'status' => 'queued',
            'timecreated' => time(),
            'cancelled' => 0,
        ]);

        // Execute task.
        $task = new execute_query_adhoc();
        $task->set_custom_data(['executionid' => $executionid]);
        $task->execute();

        ob_end_clean();

        // Verify execution completed.
        $execution = $DB->get_record('report_customsql_executions', ['id' => $executionid]);
        $this->assertEquals('completed', $execution->status);
        $this->assertNotEmpty($execution->filename);

        // Verify file was written to customdir.
        $report = $DB->get_record('report_customsql_queries', ['id' => $queryid]);
        $files = glob($customdir . '/*.csv');
        $this->assertNotEmpty($files, 'CSV file should be created in customdir');

        // Verify lastrun was updated.
        $this->assertGreaterThan(0, $report->lastrun, 'lastrun should be updated after scheduled execution');

        // Cleanup.
        foreach ($files as $file) {
            @unlink($file);
        }
        @rmdir($customdir);
    }

    /**
     * Test scheduled report execution with file storage (no customdir).
     *
     * @covers \report_customsql\task\execute_query_adhoc::execute_with_filestorage
     */
    public function test_scheduled_execution_with_filestorage(): void {
        global $DB, $USER;

        ob_start();

        // Create test query without customdir (scheduled but no customdir).
        $category = $DB->insert_record('report_customsql_categories', ['name' => 'Test Category']);
        $queryid = $DB->insert_record('report_customsql_queries', [
            'displayname' => 'Scheduled Test Query',
            'description' => 'Test scheduled query',
            'querysql' => 'SELECT id, username FROM {user} LIMIT 2',
            'queryparams' => '',
            'querylimit' => 5000,
            'capability' => '',
            'lastrun' => 0,
            'lastexecutiontime' => 0,
            'runable' => 'weekly',
            'singlerow' => 0,
            'at' => '0',
            'emailto' => '',
            'emailwhat' => '',
            'categoryid' => $category,
            'customdir' => '', // No customdir - should use file storage.
            'usermodified' => $USER->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        // Create execution record.
        $executionid = $DB->insert_record('report_customsql_executions', [
            'queryid' => $queryid,
            'userid' => $USER->id,
            'executionmode' => 'background',
            'status' => 'queued',
            'timecreated' => time(),
            'cancelled' => 0,
        ]);

        // Execute task.
        $task = new execute_query_adhoc();
        $task->set_custom_data(['executionid' => $executionid]);
        $task->execute();

        ob_end_clean();

        // Verify execution completed.
        $execution = $DB->get_record('report_customsql_executions', ['id' => $executionid]);
        $this->assertEquals('completed', $execution->status);
        $this->assertNotEmpty($execution->filename);

        // Verify file was created in file storage.
        $context = \context_system::instance();
        $fs = get_file_storage();
        $file = $fs->get_file(
            $context->id,
            'report_customsql',
            'execution',
            $executionid,
            '/',
            $execution->filename
        );
        $this->assertNotFalse($file, 'CSV file should be created in file storage');

        // Verify lastrun was updated.
        $report = $DB->get_record('report_customsql_queries', ['id' => $queryid]);
        $this->assertGreaterThan(0, $report->lastrun, 'lastrun should be updated after scheduled execution');
    }
}
