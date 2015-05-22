Feature: SamsonCMS material application

  Background:
    Given I am on homepage
    And I log out
    And I am logged in as "admin@admin.com" with "admin@admin.com"
    Given I am on "/material"

  Scenario: Material list rendering
    #Given We have filled material table
    Then I should see 1 ".table2.default" element
    And I log out

  Scenario: Table style switcher
    When I click on the element ".icon2.icon2-th"
    Then I should see 1 ".table2.tiles" element
    And I should not see an ".table2.default" element
    And I log out

  Scenario: Input changing
    Then Element ".icon2.icon2-th" should be visible
    And I log out

  Scenario: List pager
    Then I should see an ".table-pager" element
    When I click on the element ".__samson_pager_li_next"
    And I wait for ajax response
    Then I should see "2" in the ".__samson_pager_li.active" element
    And I log out

  Scenario: Pager size block
    Then I should see an ".pager-size-block" element
    And I select "20" from "sizeSelect"
    And I wait for ajax response
    # 21 because table header also has this class
    Then I should see 21 ".table2-row" elements
    And I log out

  Scenario: Async links
    When I click on the element ".icon2.icon_16x16.icon-delete.delete"
    Then I should be on "/material"
    When I click on the element ".__samson_pager_li"
    Then I should be on "/material"
    When I click on the element ".collection-sort-link"
    Then I should be on "/material"
    # If we stay on current page it means that js events are working
    And I log out

  Scenario: Sub menu collapsing
    Then I should see an menu collapser element
    And I should not see an ".template-sub-menu.collapsed" element
    When I click on the element ".collapser.icon2"
    Then I should see an ".template-sub-menu.collapsed" element
    When I click on the element ".collapser.icon2"
    Then I should not see an ".template-sub-menu.collapsed" element
    And I log out

  Scenario: Searching not found
    When I fill in "search" with "asdfzxvsdewgwgrsfsfsddsvsgegwgeds"
    And I wait for ajax response
    # Need more time
    And I wait for ajax response
    Then I should see an ".table2-row.table2-row-notfound" element
    And I log out