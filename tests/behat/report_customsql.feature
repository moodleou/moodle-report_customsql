@ou @ou_vle @report @report_customsql
Feature: Ad-hoc database queries report
  As an administrator
  In order to understand what is going on in my Moodle site
  I need to be able to run arbitrary queries against the database

  Scenario: Create an Ad-hoc database query
    When I log in as "admin"
    And I navigate to "Reports > Ad-hoc database queries" in site administration
    And I press "Add a new query"
    And I set the following fields to these values:
      | Query name  | Test query                                    |
      | Description | Display the Moodle internal version number.   |
      | Query SQL   | SELECT * FROM {config} WHERE name = 'version' |
    And I press "Verify the Query SQL text and update the form"
    And I press "Save changes"
    Then I should see "Test query"
    And I should see "Display the Moodle internal version number."
    And I should see "This report has 1 rows."
    And I should see "Download these results as"
    And the "Download these results as" select box should contain "Comma separated values (.csv)"

  Scenario: Edit an Ad-hoc database query
    Given the following custom sql report exists:
      | name        | Test query                                    |
      | description | Display the Moodle internal version number.   |
      | querysql    | SELECT * FROM {config} WHERE name = 'version' |
    When I log in as "admin"
    And I navigate to "Reports > Ad-hoc database queries" in site administration
    And I follow "Edit query 'Test query'"
    And the following fields match these values:
      | Query name  | Test query                                    |
      | Description | Display the Moodle internal version number.   |
      | Query SQL   | SELECT * FROM {config} WHERE name = 'version' |
    And I set the following fields to these values:
      | Query name  | Renamed query                                        |
      | Description | New description.                                     |
      | Query SQL   | SELECT * FROM {config} WHERE name = 'backup_version' |
    And I press "Save changes"
    Then I should see "Renamed query"
    And I should see "New description."
    And I should see "This report has 1 rows."

  Scenario: Delete an Ad-hoc database query
    Given the following custom sql report exists:
      | name        | Test query                                    |
      | description | Display the Moodle internal version number.   |
      | querysql    | SELECT * FROM {config} WHERE name = 'version' |
    When I log in as "admin"
    And I navigate to "Reports > Ad-hoc database queries" in site administration
    And I follow "Delete query 'Test query'"
    And I press "Yes"
    Then I should not see "Test query"

  Scenario: View an Ad-hoc database query that returns no data
    Given the following custom sql report exists:
      | name        | Test query                               |
      | querysql    | SELECT * FROM {config} WHERE name = '-1' |
    When I log in as "admin"
    And I view the "Test query" custom sql report
    Then I should see "This query did not return any data."

  Scenario: Create an Ad-hoc database queries category
    When I log in as "admin"
    And I navigate to "Reports > Ad-hoc database queries" in site administration
    And I press "Manage report categories"
    And I press "Add a new category"
    And I set the field "Category name" to "Category 1"
    And I press "Add a new category"
    Then I should see "Category 1"

  @javascript
  Scenario: Create an Ad-hoc database query in a custom category
    Given the custom sql report category "Special reports" exists:
    When I log in as "admin"
    And I navigate to "Reports > Ad-hoc database queries" in site administration
    And I follow "Special reports"
    And I should see "No queries available"
    And I press "Add a new query"
    And I set the following fields to these values:
      | Category    | Special reports                               |
      | Query name  | Test query                                    |
      | Query SQL   | SELECT * FROM {config} WHERE name = 'version' |
    And I press "Save changes"
    And I follow "Ad-hoc database queries"
    And I follow "Special reports"
    # Also test expand/collapse while we are here.
    Then I should see "Test query"
    And I follow "Collapse all"
    And I should not see "Test query"
    And I should not see "Collapse all"
    And I follow "Expand all"
    And I should see "Test query"
    And I should not see "Expand all"
    And I should see "Collapse all"

  Scenario: Delete an empty Ad-hoc database queries category
    Given the custom sql report category "Special reports" exists:
    When I log in as "admin"
    And I navigate to "Reports > Ad-hoc database queries" in site administration
    And I press "Manage report categories"
    And I follow "Delete category 'Special reports'"
    And I press "Yes"
    Then I should not see "Special reports"
    And "Delete category 'Miscellaneous'" "link" should not exist

  Scenario: A query that uses the various auto-formatting options
    Given the custom sql report "Formatting test" exists with SQL:
      """
      SELECT
        'Not a date'               AS String_date,
        1542888000                 AS Date_date,
        'http%%C%%//example.com/1' AS URL_to_link,
        'This is a link'           AS Link_text,
        'http%%C%%//example.com/2' AS Link_text_link_url,
        'Non-link, invalid URL'    AS Not_link,
        'Not a URL'                AS Not_link_link_url,
        'http%%C%%//example.com/3' AS Just_a_link_url,
        '<b>Raw HTML</b>'          AS HTML_should_be_escaped
      """
    When I log in as "admin"
    And I view the "Formatting test" custom sql report
    Then I should see "Formatting test"
    And "Not a date" row "String date" column of "report_customsql_results" table should contain "Not a date"
    And "Not a date" row "Date date" column of "report_customsql_results" table should contain "2018-11-22"
    And "Not a date" row "URL to link" column of "report_customsql_results" table should contain "http://example.com/1"
    And "Not a date" row "Link text" column of "report_customsql_results" table should contain "This is a link"
    And "Not a date" row "Not link" column of "report_customsql_results" table should contain "Non-link, invalid URL"
    And "Not a date" row "Just a link url" column of "report_customsql_results" table should contain "http://example.com/3"
    And "Not a date" row "HTML should be escaped" column of "report_customsql_results" table should contain "<b>Raw HTML</b>"
    And "http://example.com/1" "link" should exist in the "report_customsql_results" "table"
    And "This is a link" "link" should exist in the "report_customsql_results" "table"
    And "Non-link, invalid URL" "link" should not exist in the "report_customsql_results" "table"
    And "http://example.com/3" "link" should exist in the "report_customsql_results" "table"
    And I should not see "Link text link url" in the "report_customsql_results" "table"
    And I should see "This report has 1 rows."

  Scenario: Create and run an Ad-hoc database query that has parameters
    When I log in as "admin"
    And I navigate to "Reports > Ad-hoc database queries" in site administration
    And I press "Add a new query"
    And I set the following fields to these values:
      | Query name  | Find user                                       |
      | Query SQL   | SELECT * FROM {user} WHERE username = :username |
    And I press "Verify the Query SQL text and update the form"
    And I set the field "username" to "frog"
    And I press "Save changes"
    Then I should see "Find user"
    And I should see "Query parameters"
    And the field "username" matches value "frog"
    And I set the field "username" to "admin"
    And I press "Run query"
    And I should see "Find user"
    And I should see "username: admin"
    And I should see "moodle@example.com"
    And I should see "This report has 1 rows."

  Scenario: Link directly to an Ad-hoc database query that has parameters
    Given the following custom sql report exists:
      | name        | Find user                                       |
      | querysql    | SELECT * FROM {user} WHERE username = :username |
    When I log in as "admin"
    And I view the "Find user" custom sql report with these URL parameters:
      | username | frog |
    Then I should see "This query did not return any data."
    And I view the "Find user" custom sql report with these URL parameters:
      | username | admin |
    And I should see "moodle@example.com"
    And I should see "This report has 1 rows."

  Scenario: Link directly to an Ad-hoc database query giving some parameters
    Given the following custom sql report exists:
      | name        | Find user                                                                  |
      | querysql    | SELECT * FROM {user} WHERE firstname = :firstname AND lastname = :lastname |
    When I log in as "admin"
    And I view the "Find user" custom sql report with these URL parameters:
      | firstname | Admin |
    Then I should see "Find user"
    And I should see "Query parameters"
    And the field "firstname" matches value "Admin"
    And I set the field "lastname" to "User"
    And I press "Run query"
    And I should see "Find user"
    And I should see "firstname: Admin"
    And I should see "lastname: User"
    And I should see "moodle@example.com"
    And I should see "This report has 1 rows."

  Scenario: Test reporting when a query exceeds the limit
    Given the following config values are set as admin:
      | querylimitdefault | 1 | report_customsql |
    When I log in as "admin"
    And I navigate to "Reports > Ad-hoc database queries" in site administration
    And I press "Add a new query"
    And I set the following fields to these values:
      | Query name  | Test query                                                                                   |
      | Description | Query that tries to return 2 rows.                                                           |
      | Query SQL   | SELECT * FROM {config_plugins} WHERE name = 'version' AND plugin IN ('mod_quiz', 'mod_wiki') |
    And I press "Save changes"
    Then I should see "Test query"
    And I should see "This query reached the limit of 1 rows. Some rows may have been omitted from the end."
