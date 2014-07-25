<?php

// These tests inherit from TWFY_Selenium_TestCase (in helpers.php)
// which handles all the Selenium stuff. Run them with:
//
//     vendor/bin/phpunit -v --no-configuration ./integration-tests
//
// The tests assume there's an instance of TheyWorkForYou running at
// theyworkforyou.dev, with some sample data (debates from October 2009).

require_once('helpers.php');

class DebateListingTest extends TWFY_Selenium_TestCase {

    public function testCalendarTitle() {
        ensureUrl(self::$webDriver, self::$base_url . '/debates/?y=2009');
        $title = self::$webDriver->getTitle();
        $this->assertEquals('2009: House of Commons debates - TheyWorkForYou', $title);
    }

    public function testCalendarDisplay() {
        ensureUrl(self::$webDriver, self::$base_url . '/debates/?y=2009');
        $html = self::$webDriver->getPageSource();
        $this->assertContains('« Previous year', $html);
        $this->assertContains('Next year »', $html);

        $calendars = getElementsByCss(self::$webDriver, '.calendar > table');
        $this->assertEquals(12, count($calendars));
    }

    public function testDayTitle() {
        ensureUrl(self::$webDriver, self::$base_url . '/debates/?d=2009-10-29');
        $title = self::$webDriver->getTitle();
        $this->assertEquals('29 Oct 2009: House of Commons debates - TheyWorkForYou', $title);
    }

    public function testDayLinks() {
        ensureUrl(self::$webDriver, self::$base_url . '/debates/?d=2009-10-29');

        // There were 35 debates in the House of Commons on 29th October 2009
        $links = getElementsByCss(self::$webDriver, 'a[href*="/debates/?id=2009-10-29a."]');
        $this->assertCount(35, $links);

        $this->assertEquals('Business Before Questions', $links[0]->getText());
        $this->assertEquals('Canterbury City Council Bill (By Order)', $links[1]->getText());
        $this->assertEquals('Natural Environment', $links[12]->getText());
        $this->assertEquals('Social Care Green Paper', $links[30]->getText());
    }

    public function testDayExcerpts() {
        ensureUrl(self::$webDriver, self::$base_url . '/debates/?d=2009-10-29');
        $html = self::$webDriver->getPageSource();
        $this->assertContains("Third Reading opposed and deferred until  Thursday 5 November ( Standing Order No. 20).", $html);
        $this->assertContains("What recent assessment he has made of the effectiveness of his Department's policies to protect the natural environment; and if he will make a statement.", $html);
        $this->assertContains("I beg to move, That this House has considered the matter of the Social Care Green Paper. Today's debate is another welcome sign of the growing debate about the future of social care", $html);
    }

    public function testRecentDebatesList() {
        ensureUrl(self::$webDriver, self::$base_url . '/debates/');

        $headers = getElementsByCss(self::$webDriver, 'h2');
        $this->assertCount(3, $headers);

        foreach($headers as $header){
            $this->assertContains($header->getText(), array(
                'Recent House of Commons debates',
                'Recent Westminster Hall debates',
                'Recent House of Lords debates'
            ));
        }

        // Should be links to at least 2 debates on this page
        $links = getElementsByCss(self::$webDriver, 'a[href*="/debates/?id=2009-10-"]');
        $this->assertGreaterThan(1, $links);
    }

}
?>
