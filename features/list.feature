Feature: SamsonCMS material application

  Background:
    Given I am on homepage
    And I log out
    And I am logged in as "test@test.com" with "123"
    And I am on "/material"
    And print last response

  Scenario: Material list rendering
    #Given We have filled material table
    Then I should see 1 ".table2.default" element
    

  Scenario: Table style switcher
    When I click on the element ".icon2.icon2-th"
    Then I should see 1 ".table2.tiles" element
    And I should not see an ".table2.default" element
      

  Scenario: Input changing
    Then Element ".icon2.icon2-th" should be visible
      

  Scenario: List pager
    Then I should see an ".table-pager" element
    When I click on the element ".__samson_pager_li_next"
    And I wait for ajax response
    Then I should see "2" in the ".__samson_pager_li.active" element
      

  Scenario: Pager size block
    Then I should see an ".pager-size-block" element
    And I select "20" from "sizeSelect"
    And I wait for ajax response
    # 21 because table header also has this class
    Then I should see 21 ".table2-row" elements
      

  Scenario: Async links
    When I click on the element ".icon2.icon_16x16.icon-delete.delete"
    Then I should be on "/material"
    When I click on the element ".__samson_pager_li"
    Then I should be on "/material"
    When I click on the element ".collection-sort-link"
    Then I should be on "/material"
    # If we stay on current page it means that js events are working
      

  Scenario: Sub menu collapsing
    Then I should see an menu collapser element
    And I should not see an ".template-sub-menu.collapsed" element
    When I click on the element ".collapser.icon2"
    Then I should see an ".template-sub-menu.collapsed" element
    When I click on the element ".collapser.icon2"
    Then I should not see an ".template-sub-menu.collapsed" element
      

  Scenario: Searching not found
    When I fill in "search" with "asdfzxvsdewgwgrsfsfsddsvsgegwgeds"
    And I wait for ajax response
    # Need more time
    And I wait for ajax response
    Then I should see an ".table2-row.table2-row-notfound" element
      