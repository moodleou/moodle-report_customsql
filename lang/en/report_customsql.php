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
 * Lang strings for report/customsql
 *
 * @package report_customsql
 * @copyright 2009 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['addreport'] = 'Add a new query';
$string['anyonewhocanveiwthisreport'] = 'Anyone who can view this report (report/customsql:view)';
$string['archivedversions'] = 'Archived versions of this query';
$string['at'] = 'At';
$string['automaticallymonthly'] = 'Scheduled, on the first day of each month';
$string['automaticallyweekly'] = 'Scheduled, on the first day of each week';
$string['availablereports'] = 'On-demand queries';
$string['availableto'] = 'Available to {$a}.';
$string['backtoreportlist'] = 'Back to the list of queries';
$string['changetheparameters'] = 'Change the parameters';
$string['customsql:definequeries'] = 'Define custom queries';
$string['customsql:view'] = 'View custom queries report';
$string['daily'] = 'Daily';
$string['dailyqueries'] = 'Daily queries';
$string['dailynote'] = 'These queries are run daily, when you click the link to view the results.';
$string['deleteareyousure'] = 'Are you sure you want to delete this query?';
$string['deletethisreport'] = 'Delete this query';
$string['description'] = 'Description';
$string['displayname'] = 'Query name';
$string['displaynamex'] = 'Query name: {$a}';
$string['displaynamerequired'] = 'You must enter a query name';
$string['downloadthisreportascsv'] = 'Download these results as CSV';
$string['editingareport'] = 'Editing an ad-hoc database query';
$string['editthisreport'] = 'Edit this query';
$string['emailnumberofrows'] = 'Just the number of rows and the link';
$string['emailresults'] = 'Put the results in the email body';
$string['emailink'] = 'To access the report, click this link: {$a}';
$string['emailrow'] = 'The report returned {$a} row.';
$string['emailrows'] = 'The report returned {$a} rows.';
$string['emailsent'] = 'An email notification has been sent to {$a}';
$string['emailsentfailed'] = 'Email cannot be sent to {$a}';
$string['emailsubject'] = 'Query {$a}';
$string['emailbody'] = 'Dear {$a}';
$string['emailto'] = 'Automaticallly email to';
$string['emailwhat'] = 'What to email';
$string['enterparameters'] = 'Enter parameters for ad-hoc database query';
$string['errordeletingreport'] = 'Error deleting a query.';
$string['errorinsertingreport'] = 'Error inserting a query.';
$string['errorupdatingreport'] = 'Error updating a query.';
$string['invalidreportid'] = 'Invalid query id {$a}.';
$string['lastexecuted'] = 'This query was last run on {$a->lastrun}. It took {$a->lastexecutiontime}s to run.';
$string['messageprovider:notification'] = 'Custom SQL report notifications and alerts';
$string['manually'] = 'On-demand';
$string['manualnote'] = 'These queries are run on-demand, when you click the link to view the results.';
$string['morethanonerowreturned'] = 'More than one row was returned. This query should return one row.';
$string['nodatareturned'] = 'This query did not return any data.';
$string['noexplicitprefix'] = 'Please use prefix_ in the SQL, not {$a}.';
$string['noreportsavailable'] = 'No queries available';
$string['norowsreturned'] = 'No rows were returned. This query should return one row.';
$string['nosemicolon'] = 'You are not allowed a ; character in the SQL.';
$string['notallowedwords'] = 'You are not allowed to use the words {$a} in the SQL.';
$string['note'] = 'Notes';
$string['notrunyet'] = 'This query has not yet been run.';
$string['onerow'] = 'The query returns one row, accumulate the results one row at a time';
$string['parametervalue'] = '{$a->name}: {$a->value}';
$string['pluginname'] = 'Ad-hoc database queries';
$string['queryfailed'] = 'Error when executing the query: {$a}';
$string['querynote'] = '<ul>
<li>The token <tt>%%%%WWWROOT%%%%</tt> in the results will be replaced with <tt>{$a}</tt>.</li>
<li>Any field in the output that looks like a URL will automatically be made into a link.</li>
<li>The token <tt>%%%%USERID%%%%</tt> in the query will be replaced with the user id of the user viewing the report, before the report is executed.</li>
<li>For scheduled reports, the tokens <tt>%%%%STARTTIME%%%%</tt> and <tt>%%%%ENDTIME%%%%</tt> are replaced by the Unix timestamp at the start and end of the reporting week/month in the query before it is executed.</li>
<li>You can put parameters into the SQL using named placeholders, for example <tt>:parameter_name</tt>. Then, when the report is run, the user can enter values for the parameters to use when running the query.</li>
</ul>';
$string['queryparameters'] = 'Query parameters';
$string['queryparams'] = 'Please enter default values for the query parameters.';
$string['queryparamschanged'] = 'The placeholders in the query have changed.';
$string['queryrundate'] = 'query run date';
$string['querysql'] = 'Query SQL';
$string['querysqlrequried'] = 'You must enter some SQL.';
$string['recordlimitreached'] = 'This query reached the limit of {$a} rows. Some rows may have been omitted from the end.';
$string['reportfor'] = 'Query run on {$a}';
$string['runable'] = 'Run';
$string['runablex'] = 'Run: {$a}';
$string['schedulednote'] = 'These queries are automatically run on the first day of each week or month, to report on the previous week or month. These links let you view the results that has already been accumulated.';
$string['scheduledqueries'] = 'Scheduled queries';
$string['typeofresult'] = 'Type of result';
$string['unknowndownloadfile'] = 'Unknown download file.';
$string['usernotfound'] = 'User with the username \'{$a}\' does not exist';
$string['userhasnothiscapability'] = 'User \'{$a->username}\' has no \'{$a->capability}\' capability. Please delete this user from the list or change the choice in \'{$a->whocanaccess}\'.';
$string['userinvalidinput'] = 'Invalid input, a  comma-separated list of user names is required';
$string['userswhocanviewsitereports'] = 'Users who can see system reports (moodle/site:viewreports)';
$string['userswhocanconfig'] = 'Only administrators (moodle/site:config)';
$string['verifyqueryandupdate'] = 'Verify the Query SQL text and update the form';
$string['whocanaccess'] = 'Who can access this query';
