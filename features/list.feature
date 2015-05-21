Feature: SamsonCMS Material listing
  In order use SamsonCMS material application
  I need to be able to see list of material

  Background:
    Given I am on homepage
    And print last response
    And I am logged in as "admin@admin.com" with "admin@admin.com"

  Scenario: Open empty material list page
    Given I am on homepage
    And print last response
    And I am on "/material"
    And print last response
    Then I should see 1 ".table2-row-empty" elements

