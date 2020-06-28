# Ad-hoc database queries [![Build Status](https://travis-ci.com/moodleou/moodle-report_customsql.svg?branch=master)](https://travis-ci.com/moodleou/moodle-report_customsql)

This report plugin allows Administrators to set up arbitrary database queries
to act as ad-hoc reports. Reports can be of two types, either run on demand,
or scheduled to run automatically. Reports can be grouped into categories,
which helps when you have a lot of them.

Other users with the right capability can go to Administration -> Reports ->
Ad-hoc database queries and see a list of queries to which they have access.
Results can be viewed on-screen or downloaded as CSV.

Reports can contain placeholders, in which case, the user running the report is
presented with a form where they can enter the values to substitute for the
placeholders before running the report.

Scheduled reports can also be set to be send out be email whenever they are
generated.

If a column has a name ending in `date` and contains integer values, then they
will be assumed to be unix timestamps, and formatted as dates. If a query
placeholder has a name ending in `date`, then users will be give a date-time
selector to input the value of that parameter.

Pairs of columns where one is called `name`, and the other called `name_link_url`
will be displayed as a single column containing links with link text from
`name` and the target URL from `name_link_url`.

You can set a limit on the maximum number of rows returned by a query
(up to the hard limit of 5000).

See http://docs.moodle.org/en/Custom_SQL_queries_report for more information.


## Acknowledgements

Creted by the Open University (http://www.open.ac.uk/). There have been contributions
but many others who can be seen in the git log.


## Installation and set-up

This plugin should be compatible with Moodle 3.3+.

### Install from the plugins database

Install from the Moodle plugins database
* https://moodle.org/plugins/report_customsql

### Install using git

Or you can install using git. Type this commands in the root of your Moodle install

    git clone https://github.com/moodleou/moodle-report_customsql.git report/customsql
    echo '/report/customsql/' >> .git/info/exclude

Then run the moodle update process
Site administration > Notifications
