Feature: SamsonCMS Material listing
  In order use SamsonCMS material application
  As a visitor
  I need to be able to see list of material

  Scenario: Open material list page
    Given I am logged in as "admin@admin.com" with "admin@admin.com"
    And I am on "/material"
    And print last response
    Then I should see 1 ".table2.default" elements

