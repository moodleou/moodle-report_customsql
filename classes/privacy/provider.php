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
 * Privacy Subsystem implementation for report_customsql.
 *
 * @package    report_customsql
 * @copyright  2018 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_customsql\privacy;

use context;
use core_privacy\local\metadata\collection;
use core_privacy\local\request;

/**
 * Privacy Subsystem for report_customsql implementing null_provider.
 *
 * @copyright  2018 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Returns meta data about this system.
     *
     * @param collection $items The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $items): collection {
        $items->add_database_table(
            'report_customsql_queries',
            [
                'displayname' => 'privacy:metadata:reportcustomsqlqueries:displayname',
                'description' => 'privacy:metadata:reportcustomsqlqueries:description',
                'descriptionformat' => 'privacy:metadata:reportcustomsqlqueries:descriptionformat',
                'querysql' => 'privacy:metadata:reportcustomsqlqueries:querysql',
                'queryparams' => 'privacy:metadata:reportcustomsqlqueries:queryparams',
                'querylimit' => 'privacy:metadata:reportcustomsqlqueries:querylimit',
                'capability' => 'privacy:metadata:reportcustomsqlqueries:capability',
                'lastrun' => 'privacy:metadata:reportcustomsqlqueries:lastrun',
                'lastexecutiontime' => 'privacy:metadata:reportcustomsqlqueries:lastexecutiontime',
                'runable' => 'privacy:metadata:reportcustomsqlqueries:runable',
                'singlerow' => 'privacy:metadata:reportcustomsqlqueries:singlerow',
                'at' => 'privacy:metadata:reportcustomsqlqueries:at',
                'emailto' => 'privacy:metadata:reportcustomsqlqueries:emailto',
                'emailwhat' => 'privacy:metadata:reportcustomsqlqueries:emailwhat',
                'categoryid' => 'privacy:metadata:reportcustomsqlqueries:categoryid',
                'customdir' => 'privacy:metadata:reportcustomsqlqueries:customdir',
                'usermodified' => 'privacy:metadata:reportcustomsqlqueries:usermodified',
                'timecreated' => 'privacy:metadata:reportcustomsqlqueries:timecreated',
                'timemodified' => 'privacy:metadata:reportcustomsqlqueries:timemodified',
            ],
            'privacy:metadata:reportcustomsqlqueries'
        );

        $items->add_database_table(
            'report_customsql_executions',
            [
                'queryid' => 'privacy:metadata:reportcustomsqlexecutions:queryid',
                'userid' => 'privacy:metadata:reportcustomsqlexecutions:userid',
                'executionmode' => 'privacy:metadata:reportcustomsqlexecutions:executionmode',
                'status' => 'privacy:metadata:reportcustomsqlexecutions:status',
                'filename' => 'privacy:metadata:reportcustomsqlexecutions:filename',
                'filesize' => 'privacy:metadata:reportcustomsqlexecutions:filesize',
                'rows' => 'privacy:metadata:reportcustomsqlexecutions:rows',
                'cancelled' => 'privacy:metadata:reportcustomsqlexecutions:cancelled',
                'executiontime' => 'privacy:metadata:reportcustomsqlexecutions:executiontime',
                'errormessage' => 'privacy:metadata:reportcustomsqlexecutions:errormessage',
                'queryparams' => 'privacy:metadata:reportcustomsqlexecutions:queryparams',
                'timecreated' => 'privacy:metadata:reportcustomsqlexecutions:timecreated',
                'timestarted' => 'privacy:metadata:reportcustomsqlexecutions:timestarted',
                'timecompleted' => 'privacy:metadata:reportcustomsqlexecutions:timecompleted',
            ],
            'privacy:metadata:reportcustomsqlexecutions'
        );

        return $items;
    }

    /**
     * This function gets the contexts containing data for a userid.
     *
     * @param int $userid The userid to get contexts for.
     * @return request\contextlist the context list for the user.
     */
    public static function get_contexts_for_userid(int $userid): request\contextlist {
        $contextlist = new request\contextlist();

        // The report is in context system.
        $contextlist->add_system_context();
        return $contextlist;
    }

    /**
     * This gets the list of users inside of the provided context. In this case, its only system context
     * which contains users.
     *
     * @param request\userlist $userlist
     * @return void
     */
    public static function get_users_in_context(request\userlist $userlist) {
        $context = $userlist->get_context();

        if ($context->contextlevel === CONTEXT_SYSTEM) {
            // Get all distinct usermodified from queries table.
            $sql = 'SELECT DISTINCT usermodified
                      FROM {report_customsql_queries}';
            $userlist->add_from_sql('usermodified', $sql, []);

            // Get all distinct users from executions table.
            $sql = 'SELECT DISTINCT userid
                      FROM {report_customsql_executions}';
            $userlist->add_from_sql('userid', $sql, []);
        }
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param request\approved_contextlist $contextlist The approved contexts to export information for.
     * @throws coding_exception
     * @throws dml_exception
     * @throws \moodle_exception
     */
    public static function export_user_data(request\approved_contextlist $contextlist) {
        global $DB;

        $user = $contextlist->get_user();
        foreach ($contextlist as $context) {
            // We only export from system context.
            if ($context->contextlevel === CONTEXT_SYSTEM) {
                $records = $DB->get_records(
                    'report_customsql_queries',
                    ['usermodified' => $user->id],
                    'displayname'
                );

                $exportdata = [];
                foreach ($records as $record) {
                    $data = [];
                    $data['displayname'] = $record->displayname;
                    $data['description'] = $record->description;
                    $data['descriptionformat'] = $record->descriptionformat;
                    $data['querysql'] = $record->querysql;
                    $data['queryparams'] = $record->queryparams;
                    $data['querylimit'] = $record->querylimit;
                    $data['capability'] = $record->capability;
                    $data['lastrun'] = userdate($record->lastrun);
                    $data['lastexecutiontime'] = $record->lastexecutiontime;
                    $data['runable'] = $record->runable;
                    $data['singlerow'] = $record->singlerow;
                    $data['at'] = $record->at;
                    $data['emailto'] = $record->emailto;
                    $data['emailwhat'] = $record->emailwhat;
                    $data['categoryid'] = $record->categoryid;
                    $data['customdir'] = $record->customdir;
                    $data['usermodified'] = self::you_or_somebody_else($record->usermodified, $user);
                    $data['timecreated'] = userdate($record->timecreated);
                    $data['timemodified'] = userdate($record->timemodified);
                    $exportdata[] = $data;
                }

                $subcontext = [
                    get_string('privacy:metadata:reportcustomsqlqueries', 'report_customsql'),
                ];
                request\writer::with_context($context)->export_data($subcontext, (object)$exportdata);

                // Export background execution data.
                $executions = $DB->get_records(
                    'report_customsql_executions',
                    ['userid' => $user->id],
                    'timecreated DESC'
                );

                $executiondata = [];
                foreach ($executions as $execution) {
                    $query = $DB->get_record('report_customsql_queries', ['id' => $execution->queryid]);
                    $data = [];
                    $data['queryname'] = $query ? $query->displayname : 'N/A';
                    $data['executionmode'] = $execution->executionmode;
                    $data['status'] = $execution->status;
                    $data['rows'] = $execution->rowsreturned;
                    $data['executiontime'] = $execution->executiontime;
                    $data['filesize'] = $execution->filesize;
                    $data['errormessage'] = $execution->errormessage;
                    $data['timecreated'] = userdate($execution->timecreated);
                    $data['timestarted'] = $execution->timestarted ? userdate($execution->timestarted) : '';
                    $data['timecompleted'] = $execution->timecompleted ? userdate($execution->timecompleted) : '';
                    $executiondata[] = $data;

                    // Export the result file if it exists.
                    if ($execution->filename) {
                        $fs = get_file_storage();
                        $file = $fs->get_file(
                            $context->id,
                            'report_customsql',
                            'execution',
                            $execution->id,
                            '/',
                            $execution->filename
                        );
                        if ($file) {
                            $subcontext = [
                                get_string('privacy:metadata:reportcustomsqlexecutions', 'report_customsql'),
                                $execution->id,
                            ];
                            request\writer::with_context($context)->export_file($subcontext, $file);
                        }
                    }
                }

                if (!empty($executiondata)) {
                    $subcontext = [
                        get_string('privacy:metadata:reportcustomsqlexecutions', 'report_customsql'),
                    ];
                    request\writer::with_context($context)->export_data($subcontext, (object)$executiondata);
                }
            }
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param context $context The specific context to delete data for.
     * @throws \dml_exception
     */
    public static function delete_data_for_all_users_in_context(context $context) {
        global $DB;

        if ($context->contextlevel === CONTEXT_SYSTEM) {
            $adminuserid = get_admin()->id;
            $DB->set_field('report_customsql_queries', 'usermodified', $adminuserid);

            // Delete all execution records and files.
            $executions = $DB->get_records('report_customsql_executions');
            foreach ($executions as $execution) {
                self::delete_execution_data($execution);
            }
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param request\approved_contextlist $contextlist The approved contexts and user information to delete information for.
     * @throws \dml_exception
     */
    public static function delete_data_for_user(request\approved_contextlist $contextlist) {
        global $DB;

        foreach ($contextlist as $context) {
            // We only delete data from system context.
            if ($context->contextlevel === CONTEXT_SYSTEM) {
                $userid = $contextlist->get_user()->id;
                $adminuserid = get_admin()->id;

                $DB->set_field(
                    'report_customsql_queries',
                    'usermodified',
                    $adminuserid,
                    ['usermodified' => $userid]
                );

                // Delete user's execution records and files.
                $executions = $DB->get_records('report_customsql_executions', ['userid' => $userid]);
                foreach ($executions as $execution) {
                    self::delete_execution_data($execution);
                }
            }
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param request\approved_userlist $userlist The approved context and user information to delete information for.
     *
     * @throws \dml_exception|\coding_exception
     */
    public static function delete_data_for_users(request\approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel === CONTEXT_SYSTEM) {
            $userids = $userlist->get_userids();
            [$sqlcondition, $params] = $DB->get_in_or_equal($userids);
            $adminuserid = get_admin()->id;
            $DB->set_field_select(
                'report_customsql_queries',
                'usermodified',
                $adminuserid,
                'usermodified ' . $sqlcondition,
                $params
            );

            // Delete executions for these users.
            $executions = $DB->get_records_select(
                'report_customsql_executions',
                'userid ' . $sqlcondition,
                $params
            );
            foreach ($executions as $execution) {
                self::delete_execution_data($execution);
            }
        }
    }

    /**
     * Removes personally-identifiable data from a user id for export.
     *
     * @param int $userid User id of a person
     * @param \stdClass $user Object representing current user being considered
     * @return string 'You' if the two users match, 'Somebody else' otherwise
     * @throws \coding_exception
     */
    protected static function you_or_somebody_else($userid, $user) {
        if ($userid == $user->id) {
            return get_string('privacy_you', 'report_customsql');
        } else {
            return get_string('privacy_somebodyelse', 'report_customsql');
        }
    }

    /**
     * Delete execution data including files.
     *
     * @param \stdClass $execution Execution record
     * @throws \dml_exception
     */
    protected static function delete_execution_data($execution) {
        global $DB;

        // Delete the stored file if exists.
        if ($execution->filename) {
            $fs = get_file_storage();
            $context = \context_system::instance();

            $file = $fs->get_file(
                $context->id,
                'report_customsql',
                'execution',
                $execution->id,
                '/',
                $execution->filename
            );

            if ($file) {
                $file->delete();
            }
        }

        // Delete the database record.
        $DB->delete_records('report_customsql_executions', ['id' => $execution->id]);
    }
}
