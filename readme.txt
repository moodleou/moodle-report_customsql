Ad-hoc database queries

This is a dump of the old MOODLE_19_CODE into git. This branch is no longer
maintained.

This report plugin allows Administrators to set up arbitrary database queries
to act as ad-hoc reports. Reports can be of two types, either run on demand,
or scheduled to run automatically. Other users with the right capability can
go in and see a list of queries that they have access to. Results can be viewed
on-screen or downloaded as CSV.

See http://docs.moodle.org/19/en/Custom_SQL_queries_report for more information.

Written by Tim Hunt from The Open University (http://www.open.ac.uk/).

To install using git, type this command in the root of your Moodle install
    git clone -b MOODLE_19_STABLE git://github.com/timhunt/moodle-report_customsql.git admin/report/customsql

This version of the report is compatible with Moodle 1.9.x.
