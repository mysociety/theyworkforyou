<?php

/**
 * Provides test methods to ensure pages contain what we want them to in various ways.
 */
class AlertsPageTest extends FetchPageTestCase
{

    /**
     * Loads the member testing fixture.
     */
    public function getDataSet()
    {
        return $this->createMySQLXMLDataSet(dirname(__FILE__).'/_fixtures/alertspage.xml');
    }

    private function fetch_page($vars)
    {
        return $this->base_fetch_page($vars, 'alert');
    }

    public function testFetchPage()
    {
        $page = $this->fetch_page( array() );
        $this->assertStringContainsString('TheyWorkForYou Email Alerts', $page);
    }

    public function testKeywordOnly() {
        $page = $this->fetch_page( array( 'alertsearch' => 'elephant') );
        $this->assertStringContainsString('Receive alerts when [elephant] is mentioned', $page);
    }

    public function testPostCodeOnly() {
        $page = $this->fetch_page( array( 'alertsearch' => 'SE17 3HE') );
        $this->assertStringContainsString('when Mrs Test Current-MP', $page);
    }

    public function testPostCodeWithKeyWord()
    {
        $page = $this->fetch_page( array( 'alertsearch' => 'SE17 3HE elephant') );
        $this->assertStringContainsString('You have used a postcode and something else', $page);
        $this->assertStringContainsString('Mentions of [elephant] by your MP, Mrs Test Current-MP', $page);
        $this->assertStringNotContainsString('by your MSP', $page);
    }

    public function testScottishPostcodeWithKeyword() {
        $page = $this->fetch_page( array( 'alertsearch' => 'PH6 2DB elephant') );
        $this->assertStringContainsString('You have used a postcode and something else', $page);
        $this->assertStringContainsString('Mentions of [elephant] by your MP, Mr Test2 Current-MP', $page);
        $this->assertStringContainsString('Mentions of [elephant] by your MSP, Mrs Test Current-MSP', $page);
    }

    public function testPostcodeAndKeywordWithNoSittingMP() {
        $page = $this->fetch_page( array( 'alertsearch' => 'OX1 4LF elephant') );
        $this->assertStringContainsString('You have used a postcode and something else', $page);
        $this->assertStringNotContainsString('Did you mean to get alerts for when your MP', $page);
    }
}
