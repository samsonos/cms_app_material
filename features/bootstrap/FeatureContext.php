<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Definition\Call\Given;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\MinkContext;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends MinkContext implements SnippetAcceptingContext
{
    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
    }

    /**
     * @Given /^I log out$/
     */
    public function iLogOut() {
        $this->getSession()->reset();
    }

    /**
     * @Given /^I wait for ajax response$/
     */
    public function iWaitForAjaxResponse()
    {
        $this->getSession()->wait(1000);
    }

    /**
     * @Given I am logged in as :arg1 with :arg2
     */
    public function iAmLoggedInAsWith($arg1, $arg2)
    {
        $this->visit('/signin');
        $this->fillField('email', $arg1);
        $this->fillField('password', $arg2);
        $this->pressButton('btn-signin');
        $this->getSession()->wait(2000);
    }

    /**
     * Click on the element with the provided xpath query
     *
     * @When I click on the element :arg1
     */
    public function iClickOnTheElement2($selector)
    {
        $session = $this->getSession();
        $element = $session->getPage()->find('css', $selector);

        // If element with current selector is not found then print error
        if (null === $element) {
            throw new \InvalidArgumentException(sprintf('Could not evaluate CSS selector: "%s"', $selector));
        }

        // Click on the founded element
        $element->click();
    }

    /**
     * @Then Element :arg1 should be visible
     */
    public function shouldBeVisible($selector) {

        $element = $this->getSession()->getPage()->find('css', $selector);

        if (!empty($element)){
            $style = preg_replace('/\s/', '', $element->getAttribute('style'));
        } else {
            throw new \InvalidArgumentException(sprintf('Could not evaluate CSS selector: "%s"', $selector));
        }

        if (false !== strstr($style, 'display: none') && false !== strstr($style, 'display:none')) {
            throw new Exception(sprintf('Element with selector: "%s" is not visible', $selector));
        }
    }

    /**
     * @Then I should see an menu collapser element
     */
    public function iShouldSeeAnMenuCollapserElement()
    {
        $this->assertElementOnPage('.collapser.icon2');
    }

    /**
     * @Given We have filled material table
     */
    public function weHaveFilledMaterialTable() {
        //mysqli_query
        //("INSERT INTO `material`(`Name`, `Url`, `Published`, `Active`) VALUES ('behatTest','behatTest', 1, 1)")
        //or die("Invalid query: " . mysql_error());
    }
}
