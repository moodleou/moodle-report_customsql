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
 * @copyright 2015 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['addcategory'] = 'Add a new category';
$string['addcategorydesc'] = 'To change a report\'s category, you must edit that report. Here you can edit category texts, delete a category or add a new category.';
$string['addingareport'] = 'Adding an ad-hoc database query';
$string['addreport'] = 'Add a new query';
$string['addreportcategory'] = 'Add a new category for reports';
$string['anyonewhocanveiwthisreport'] = 'Anyone who can view this report (report/customsql:view)';
$string['archivedversions'] = 'Results of this query at other times';
$string['at'] = 'at';
$string['automaticallydaily'] = 'Scheduled, daily';
$string['automaticallymonthly'] = 'Scheduled, on the first day of each month';
$string['automaticallyweekly'] = 'Scheduled, on the first day of each week';
$string['availablereports'] = 'On-demand queries';
$string['availableto'] = 'Available to {$a}.';
$string['backtocategory'] = 'Back to category \'{$a}\'';
$string['backtoreportlist'] = 'Back to the list of queries';
$string['category'] = 'Category';
$string['categorycontent'] = '({$a->manual} on-demand, {$a->daily} daily, {$a->weekly} weekly, {$a->monthly} monthly)';
$string['categoryexists'] = 'Category names must be unique, this name already exists';
$string['categorynamex'] = 'Category name: {$a}';
$string['changetheparameters'] = 'Change the parameters';
$string['crontask'] = 'Ad-hoc database queries: run scheduled reports task';
$string['customdir'] = 'Export csv report to path / directory';
$string['customdir_help'] = 'Files are exported in the CSV format to the file path specified. If a directory is specified the filename format will be reportid-timecreated.csv.';
$string['customdirmustexist'] = 'The directory "{$a}" does not exist.';
$string['customdirnotadirectory'] = 'The path "{$a}" is not a directory.';
$string['customdirnotwritable'] = 'The directory "{$a}" is not writable.';
$string['customsql:definequeries'] = 'Define custom queries';
$string['customsql:managecategories'] = 'Define custom categories';
$string['customsql:view'] = 'View custom queries report';
$string['dailyheader'] = 'Daily';
$string['dailyheader_help'] = 'These queries are automatically run every day at the specified time. These links let you view the results that has already been accumulated.';
$string['defaultcategory'] = 'Miscellaneous';
$string['delete'] = 'Delete';
$string['deleteareyousure'] = 'Are you sure you want to delete this query?';
$string['deletecategoryareyousure'] = '<p>Are you sure you want to delete this category? </p><p>It cannot contain any queries.</p>';
$string['deletecategoryx'] = 'Delete category \'{$a}\'';
$string['deletecategoryyesno'] = '<p>Are you really sure you want to delete this category? </p>';
$string['deletereportx'] = 'Delete query \'{$a}\'';
$string['description'] = 'Description';
$string['displayname'] = 'Query name';
$string['displaynamerequired'] = 'You must enter a query name';
$string['displaynamex'] = 'Query name: {$a}';
$string['downloadthisreportas'] = 'Download these results as';
$string['downloadthisreportascsv'] = 'Download these results as CSV';
$string['edit'] = 'Add/Edit';
$string['editcategory'] = 'Update category';
$string['editcategoryx'] = 'Edit category \'{$a}\'';
$string['editingareport'] = 'Editing an ad-hoc database query';
$string['editreportx'] = 'Edit query \'{$a}\'';
$string['emailbody'] = 'Dear {$a}';
$string['emailink'] = 'To access the report, click this link: {$a}';
$string['emailnumberofrows'] = 'Just the number of rows and the link';
$string['emailresults'] = 'Put the results in the email body';
$string['emailrow'] = 'The report returned {$a} row.';
$string['emailrows'] = 'The report returned {$a} rows.';
$string['emailsent'] = 'An email notification has been sent to {$a}';
$string['emailsentfailed'] = 'Email cannot be sent to {$a}';
$string['emailsubject'] = 'Query {$a}';
$string['emailsubject1row'] = 'Query {$a->name} [1 row] [{$a->env}]';
$string['emailsubjectnodata'] = 'Query {$a->name} [no results] [{$a->env}]';
$string['emailsubjectxrows'] = 'Query {$a->name} [{$a->rows} rows] [{$a->env}]';
$string['emailto'] = 'Automatically email to';
$string['emailwhat'] = 'What to email';
$string['enterparameters'] = 'Enter parameters for ad-hoc database query';
$string['error:cannotdeletequery'] = 'Error: cannot delete query';
$string['error:invalidqueryid'] = 'Invalid query id';
$string['error:updatefail'] = 'Update failed';
$string['errordeletingcategory'] = '<p>Error deleting a query category.</p><p>It must be empty to delete it.</p>';
$string['errordeletingreport'] = 'Error deleting a query.';
$string['errorinsertingreport'] = 'Error inserting a query.';
$string['errorupdatingreport'] = 'Error updating a query.';
$string['invalidreportid'] = 'Invalid query id {$a}.';
$string['lastexecuted'] = 'This query was last run on {$a->lastrun}. It took {$a->lastexecutiontime}s to run.';
$string['limittestrows'] = 'Maximum allowed limit on rows on the ws Query validations';
$string['limittestrows_desc'] = 'This is the limit on the Query validation WS(Up to 99).';
$string['managecategories'] = 'Manage report categories';
$string['manual'] = 'On-demand';
$string['manualheader'] = 'On-demand';
$string['manualheader_help'] = 'These queries are run on-demand, when you click the link to view the results.';
$string['messageprovider:notification'] = 'Ad-hoc database query notifications';
$string['monthlyheader'] = 'Monthly';
$string['monthlyheader_help'] = 'These queries are automatically run on the first day of each month, to report on the previous month. These links let you view the results that has already been accumulated.';
$string['monthlynote_help'] = 'These queries are automatically run on the first day of each month, to report on the previous month. These links let you view the results that has already been accumulated.';
$string['morethanonerowreturned'] = 'More than one row was returned. This query should return one row.';
$string['nodatareturned'] = 'This query did not return any data.';
$string['noexplicitprefix'] = 'Please do to include the table name prefix <code>{$a}</code> in the SQL. Instead, put the un-prefixed table name inside <code>{}</code> characters.';
$string['noreportsavailable'] = 'No queries available';
$string['norowsreturned'] = 'No rows were returned. This query should return one row.';
$string['noscheduleifplaceholders'] = 'Queries containing placeholders can only be run on-demand.';
$string['nosemicolon'] = 'You are not allowed a ; character in the SQL.';
$string['notallowedwords'] = 'You are not allowed to use the words <code>{$a}</code> in the SQL.';
$string['notanyresults'] = 'This query did not return any results on {$a}.';
$string['note'] = 'Notes';
$string['notrunyet'] = 'This query has not yet been run.';
$string['onerow'] = 'The query returns one row, accumulate the results one row at a time';
$string['parametervalue'] = '{$a->name}: {$a->value}';
$string['pluginname'] = 'Ad-hoc database queries';
$string['privacy:metadata'] = 'The Ad-hoc database queries plugin does not store any personal data.';
$string['privacy:metadata:reportcustomsqlqueries'] = 'Ad-hoc database queries';
$string['privacy:metadata:reportcustomsqlqueries:at'] = 'The time for the daily report';
$string['privacy:metadata:reportcustomsqlqueries:capability'] = 'The capability that a user needs to have to run this report';
$string['privacy:metadata:reportcustomsqlqueries:categoryid'] = 'The category ID from report_customsql_categories table';
$string['privacy:metadata:reportcustomsqlqueries:customdir'] = 'Export csv report to path / directory';
$string['privacy:metadata:reportcustomsqlqueries:description'] = 'A human-readable description of the query.';
$string['privacy:metadata:reportcustomsqlqueries:descriptionformat'] = 'Query description text format';
$string['privacy:metadata:reportcustomsqlqueries:displayname'] = 'The name of the report as displayed in the UI';
$string['privacy:metadata:reportcustomsqlqueries:emailto'] = 'A comma-separated list of user ids';
$string['privacy:metadata:reportcustomsqlqueries:emailwhat'] = 'A list of email options in a select menu';
$string['privacy:metadata:reportcustomsqlqueries:lastexecutiontime'] = 'Time this report took to run last time it was executed, in milliseconds';
$string['privacy:metadata:reportcustomsqlqueries:lastrun'] = 'When this report was last run';
$string['privacy:metadata:reportcustomsqlqueries:querylimit'] = 'Limit the number of results returned';
$string['privacy:metadata:reportcustomsqlqueries:queryparams'] = 'The SQL parameters to generate this report';
$string['privacy:metadata:reportcustomsqlqueries:querysql'] = 'The SQL to run to generate this report';
$string['privacy:metadata:reportcustomsqlqueries:runable'] = 'Runable \'manual\', \'weekly\' or \'monthly\'';
$string['privacy:metadata:reportcustomsqlqueries:singlerow'] = 'Only meaningful to set this scheduled reports. Means the report can only return one row of data, and the report builds up a row at a time';
$string['privacy:metadata:reportcustomsqlqueries:timecreated'] = 'Time created';
$string['privacy:metadata:reportcustomsqlqueries:timemodified'] = 'Time modified';
$string['privacy:metadata:reportcustomsqlqueries:usermodified'] = 'User modified';
$string['privacy_somebodyelse'] = 'Somebody else';
$string['privacy_you'] = 'You';
$string['query_deleted'] = 'Query deleted';
$string['query_edited'] = 'Query edited';
$string['query_viewed'] = 'Query viewed';
$string['queryfailed'] = 'Error when executing the query: {$a}';
$string['querylimit'] = 'Limit on rows returned';
$string['querylimitdefault'] = 'Default limit on rows returned';
$string['querylimitdefault_desc'] = 'To avoid accidents where a query return a huge number of rows which might overload the server, each query has a limit to the number of rows it can return. This is the default value for that limit for new queries.';
$string['querylimitmaximum'] = 'Maximum allowed limit on rows returned';
$string['querylimitmaximum_desc'] = 'This is the absolute maximum limit on rows returned which a query author is allowed to set.';
$string['querylimitrange'] = 'Number must be between 1 and {$a}';
$string['querynote'] = '<ul>
<li>The token <code>%%WWWROOT%%</code> in the results will be replaced with <code>{$a}</code>.</li>
<li>Any value in the output that looks like a URL will automatically be made into a link.</li>
<li>If your query results have two columns <code><i>column_name</i></code> and <code><i>column_name</i>_link_url</code> then the resulting report output will have a single column containing a link with first column as link text and second as URL.</li>
<li>If a column name in the results ends with the characters <code>date</code>, and the column contains integer values, then they will be treated as Unix time-stamps, and automatically converted to human-readable dates.</li>
<li>The token <code>%%USERID%%</code> in the query will be replaced with the user id of the user viewing the report, before the report is executed.</li>
<li>For scheduled reports, the tokens <code>%%STARTTIME%%</code> and <code>%%ENDTIME%%</code> are replaced by the Unix timestamp at the start and end of the reporting week/month in the query before it is executed.</li>
<li>You can put parameters into the SQL using named placeholders, for example <code>:parameter_name</code>. Then, when the report is run, the user can enter values for the parameters to use when running the query.</li>
<li>If the <code>:parameter_name</code> starts or ends with the characters <code>date</code> then a date-time selector will be used to input that value, otherwise a plain text-box will be used.</li>
<li>You cannot use the characters <code>:</code>, <code>;</code> or <code>?</code> in strings in your query.<ul>
    <li>If you need them in output data (such as when outputting URLs), you can use the tokens <code>%%C%%</code>, <code>%%S%%</code> and <code>%%Q%%</code> respectively.</li>
    <li>If you need them in input data (such as in a regular expression or when querying for the characters), you will need to use a database function to get the characters and concatenate them yourself. In Postgres, respectively these are CHR(58), CHR(59) and CHR(63); in MySQL CHAR(58), CHAR(59) and CHAR(63).</li>
</ul></li>
</ul>';
$string['queryparameters'] = 'Query parameters';
$string['queryparams'] = 'Please enter default values for the query parameters.';
$string['queryparamschanged'] = 'The placeholders in the query have changed.';
$string['queryrundate'] = 'query run date';
$string['querysql'] = 'Query SQL';
$string['querysqlrequried'] = 'You must enter some SQL.';
$string['recordcount'] = 'This report has {$a} rows.';
$string['recordlimitreached'] = 'This query reached the limit of {$a} rows. Some rows may have been omitted from the end.';
$string['reportfor'] = 'Query run on {$a}';
$string['requireint'] = 'Integer required';
$string['runable'] = 'Run';
$string['runablex'] = 'Run: {$a}';
$string['runquery'] = 'Run query';
$string['schedulednote'] = 'These queries are automatically run on the first day of each week or month, to report on the previous week or month. These links let you view the results that has already been accumulated.';
$string['scheduledqueries'] = 'Scheduled queries';
$string['showonlythiscategory'] = 'Show only {$a}';
$string['startofweek'] = 'Day to run weekly reports';
$string['startofweek_default'] = 'Use site calendar start of week ({$a})';
$string['startofweek_desc'] = 'This is the day which should be considered the first day of the week, for weekly scheduled reports.';
$string['timecreated'] = '<span class="font-weight-bold">Time created:</span> {$a}';
$string['timemodified'] = '<span class="font-weight-bold">Last modified:</span> {$a}';
$string['typeofresult'] = 'Type of result';
$string['unknowndownloadfile'] = 'Unknown download file.';
$string['userhasnothiscapability'] = 'User \'{$a->name}\' ({$a->userid}) has not got capability \'{$a->capability}\'. Please delete this user from the list or change the choice in \'{$a->whocanaccess}\'.';
$string['userinvalidinput'] = 'Invalid input, a  comma-separated list of user names is required';
$string['usermodified'] = '<span class="font-weight-bold">Modified by:</span> {$a}';
$string['usernotfound'] = 'User with id \'{$a}\' does not exist';
$string['userswhocanconfig'] = 'Only administrators (moodle/site:config)';
$string['userswhocanviewsitereports'] = 'Users who can see system reports (moodle/site:viewreports)';
$string['verifyqueryandupdate'] = 'Verify the Query SQL text and update the form';
$string['weeklyheader'] = 'Weekly';
$string['weeklyheader_help'] = 'These queries are automatically run on the first day of each week, to report on the previous week. These links let you view the results that has already been accumulated.';
$string['whocanaccess'] = 'Who can access this query';
