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
    // This plugin has data.
    \core_privacy\local\metadata\provider,
    // This plugin currently implements the original plugin\provider interface.
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

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
                'timemodified' => 'privacy:metadata:reportcustomsqlqueries:timemodified'
            ],
            'privacy:metadata:reportcustomsqlqueries'
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
            // If we are checking system context, we need to get all distinct usermodified from the table.
            $sql = 'SELECT DISTINCT usermodified
                      FROM {report_customsql_queries}';

            $userlist->add_from_sql('usermodified', $sql, []);
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
                    get_string('privacy:metadata:reportcustomsqlqueries', 'report_customsql')
                ];
                request\writer::with_context($context)->export_data($subcontext, (object)$exportdata);
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

                $DB->set_field('report_customsql_queries', 'usermodified',
                    $adminuserid, ['usermodified' => $userid]);
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
            list($sqlcondition, $params) = $DB->get_in_or_equal($userids);
            $adminuserid = get_admin()->id;
            $DB->set_field_select('report_customsql_queries', 'usermodified', $adminuserid,
                 'usermodified ' . $sqlcondition, $params);
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
}
