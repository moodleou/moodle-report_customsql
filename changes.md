# Change log for the Ad-hoc database queries report


## Changes in 3.6

* New feature for columns that are links. If the SQL query returns two
  columns `name` and `name_link_url` (for any value of `name`) then
  the that will be shown as a single column of links, where the visible
  link text comes from the `name` column, and the URL comes from the
  `name_link_url` column.
* The exact case of the column titles is extracted from the query. So,
  if your query is `SELECT 'x' AS My_HTML`, previously the table would
  display the column heading as 'my html'. Now it will show 'My HTML'.
* A summary of the number of rows output under the table.
* Moodle 3.3 compatibility re-established thanks to Paul Holden.
* Behat tests fixed for Moodle 3.6. 


## Changes in 3.5

* Privacy API implementation.
* Fix escaping of values in reports that contain HTML special characters.
* Fix bug where the report name was missing from scheduled task emails.
* Fix some coding style issues.
* Due to privacy API support, this version now only works in Moodle 3.4+
  For older Moodles, you will need to use a previous version of this plugin.


## 3.4 and before

Changes were not documented here.
