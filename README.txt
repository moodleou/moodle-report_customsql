Ad-hoc database queries

This report plugin allows Administrators to set up arbitrary database queries
to act as ad-hoc reports. Reports can be of two types, either run on demand,
or scheduled to run automatically.

Other users with the right capability can go in and see a list of queries that
they have access to. Results can be viewed on-screen or downloaded as CSV.

Reports can contain placeholders, in which case, the user running the report is
presented with a form where they can enter the values to substitute for the
placeholders before running the report.

Scheduled reports can also be set to be send out be email whenever they are
generated.

See http://docs.moodle.org/24/en/Custom_SQL_queries_report for more information.

Written by Tim Hunt and converted to Moodle 2.0 by Derek Woolhead, both from
The Open University (http://www.open.ac.uk/). There have also been contibutions
but many others.

To install using git, type this command in the root of your Moodle install
    git clone git://github.com/timhunt/moodle-report_customsql.git report/customsql

This version of the report is compatible with Moodle 2.2 or later. (Tested on
Moodle 2.3+.)
