<?php

/**
 * Provides test methods to ensure pages contain what we want them to in various ways.
 */
class AlertsPageTest extends FetchPageTestCase {
    /**
     * Loads the member testing fixture.
     */
    public function getDataSet() {
        return $this->createMySQLXMLDataSet(dirname(__FILE__) . '/_fixtures/alertspage.xml');
    }

    private function fetch_page($vars) {
        return $this->base_fetch_page($vars, 'alert');
    }

    private function get_page($vars = []) {
        return $this->base_fetch_page_user($vars, '1.fbb689a0c092f5534b929d302db2c8a9', 'alert');
    }

    public function testFetchPage() {
        $page = $this->fetch_page([]);
        $this->assertStringContainsString('TheyWorkForYou Email Alerts', $page);
    }

    public function testKeywordOnly() {
        $page = $this->fetch_page([ 'alertsearch' => 'elephant']);
        $this->assertStringContainsString('What word or phrase would you like to recieve alerts about', $page);
        $this->assertStringContainsString('<input type="text" id="words0" name="words[]" aria-required="true" value="elephant"', $page);
    }

    public function testSpeakerId() {
        $page = $this->fetch_page([ 'alertsearch' => 'speaker:2']);
        $this->assertStringContainsString('Mrs Test Current-MP', $page);
    }

    public function testPostCodeOnly() {
        $page = $this->fetch_page([ 'alertsearch' => 'SE17 3HE']);
        $this->assertStringContainsString('Mrs Test Current-MP', $page);
    }

    public function testPostCodeWithKeyWord() {
        $page = $this->fetch_page([ 'alertsearch' => 'SE17 3HE elephant']);
        $this->assertStringContainsString('You have used a postcode and something else', $page);
        $this->assertStringContainsString('Mentions of [elephant] by your MP, Mrs Test Current-MP', $page);
        $this->assertStringNotContainsString('by your MSP', $page);
    }

    public function testScottishPostcodeWithKeyword() {
        $page = $this->fetch_page([ 'alertsearch' => 'PH6 2DB elephant']);
        $this->assertStringContainsString('You have used a postcode and something else', $page);
        $this->assertStringContainsString('Mentions of [elephant] by your MP, Mr Test2 Current-MP', $page);
        $this->assertStringContainsString('Mentions of [elephant] by your MSP, Mrs Test Current-MSP', $page);
    }

    public function testPostcodeAndKeywordWithNoSittingMP() {
        $page = $this->fetch_page([ 'alertsearch' => 'OX1 4LF elephant']);
        $this->assertStringContainsString('You have used a postcode and something else', $page);
        $this->assertStringNotContainsString('Did you mean to get alerts for when your MP', $page);
    }

    public function testBasicKeyWordAlertsCreation() {
        $page = $this->fetch_page([ 'step' => 'define']);
        $this->assertStringContainsString('What word or phrase would you like to recieve alerts about', $page);
        $this->assertStringContainsString('<input type="text" id="words0" name="words[]" aria-required="true" value=""', $page);

        $page = $this->fetch_page([ 'step' => 'review', 'email' => 'test@example.org', 'words[]' => 'fish']);
        $this->assertStringContainsString('Review Your Alert', $page);
        $this->assertStringContainsString('<input type="hidden" name="words[]" value="fish"', $page);

        $page = $this->fetch_page([ 'step' => 'confirm', 'email' => 'test@example.org', 'words[]' => 'fish']);
        $this->assertStringContainsString('We’re nearly done', $page);
        $this->assertStringContainsString('You should receive an email shortly', $page);
    }

    public function testMultipleKeyWordAlertsCreation() {
        $page = $this->fetch_page([ 'step' => 'define']);
        $this->assertStringContainsString('What word or phrase would you like to recieve alerts about', $page);
        $this->assertStringContainsString('<input type="text" id="words0" name="words[]" aria-required="true" value=""', $page);

        $page = $this->fetch_page([ 'step' => 'review', 'email' => 'test@example.org', 'words[]' => ['fish', 'salmon']]);
        $this->assertStringContainsString('Review Your Alert', $page);
        $this->assertStringContainsString('<input type="hidden" name="words[]" value="fish"', $page);
        $this->assertStringContainsString('<input type="hidden" name="words[]" value="salmon"', $page);

        $page = $this->fetch_page([ 'step' => 'confirm', 'email' => 'test@example.org', 'words[]' => ['fish', 'salmon']]);
        $this->assertStringContainsString('You should receive an email shortly', $page);
    }

    public function testMultipleKeyWordAlertsCreationLoggedIn() {
        $page = $this->get_page(['step' => 'define']);
        $this->assertStringContainsString('What word or phrase would you like to recieve alerts about', $page);
        $this->assertStringContainsString('<input type="text" id="words0" name="words[]" aria-required="true" value=""', $page);

        $page = $this->get_page([ 'step' => 'review', 'words[]' => ['fish', 'salmon']]);
        $this->assertStringContainsString('Review Your Alert', $page);
        $this->assertStringContainsString('<input type="hidden" name="words[]" value="fish"', $page);
        $this->assertStringContainsString('<input type="hidden" name="words[]" value="salmon"', $page);

        $page = $this->get_page([ 'step' => 'confirm', 'words[]' => ['fish', 'salmon']]);
        $this->assertStringContainsString('You will now receive email alerts on any day when [fish salmon] is mentioned in parliament', $page);
    }

    public function testKeyWordAndSectionAlertsCreationLoggedIn() {
        $page = $this->get_page(['step' => 'define']);
        $this->assertStringContainsString('What word or phrase would you like to recieve alerts about', $page);
        $this->assertStringContainsString('<input type="text" id="words0" name="words[]" aria-required="true" value=""', $page);

        $page = $this->get_page(['step' => 'review', 'words[]' => 'fish', 'search_section' => 'debates']);
        $this->assertStringContainsString('Review Your Alert', $page);
        $this->assertStringContainsString('<input type="hidden" name="words[]" value="fish"', $page);

        $page = $this->get_page(['step' => 'confirm', 'words[]' => 'fish', 'search_section' => 'debates']);
        $this->assertStringContainsString('You will now receive email alerts on any day when [fish] is mentioned in House of Commons debates', $page);
    }

    public function testKeyWordAndSpeakerAlertsCreationLoggedIn() {
        $page = $this->get_page(['step' => 'define']);
        $this->assertStringContainsString('What word or phrase would you like to recieve alerts about', $page);
        $this->assertStringContainsString('<input type="text" id="words0" name="words[]" aria-required="true" value=""', $page);

        $page = $this->get_page(['step' => 'review', 'words[]' => 'fish', 'representative' => 'Mrs Test Current-MP']);
        $this->assertStringContainsString('Review Your Alert', $page);
        $this->assertStringContainsString('<input type="hidden" name="words[]" value="fish"', $page);
        $this->assertStringContainsString('<input type="hidden" name="representative" value="Mrs Test Current-MP"', $page);

        $page = $this->get_page([ 'step' => 'confirm', 'words[]' => 'fish', 'representative' => 'Mrs Test Current-MP']);
        $this->assertStringContainsString('You will now receive email alerts on any day when Mrs Test Current-MP mentions [fish] in parliament', $page);
    }
}
