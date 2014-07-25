<?php

// These tests inherit from TWFY_Selenium_TestCase (in helpers.php)
// which handles all the Selenium stuff. Run them with:
//
//     vendor/bin/phpunit -v --no-configuration ./integration-tests
//
// The tests assume there's an instance of TheyWorkForYou running at
// theyworkforyou.dev, with some sample data (debates from October 2009).

require_once('helpers.php');

class DebatePageTest extends TWFY_Selenium_TestCase {

    // These tests take the path as an argument so that PHPUnit runs them
    // with the default value first, but we can override the URL later on.
    public function testDebateTitle($path='/debates/?id=2009-10-29a.479.0') {
        ensureUrl(self::$webDriver, self::$base_url . $path);
        $title = self::$webDriver->getTitle();
        $this->assertContains('Social Care Green Paper', $title);
        $this->assertContains('House of Commons', $title);
        $this->assertContains('debate', strtolower($title));
    }

    public function testSpeechesText($path='/debates/?id=2009-10-29a.479.0') {
        ensureUrl(self::$webDriver, self::$base_url . $path);
        $html = self::$webDriver->getPageSource();
        $this->assertContains('considered the matter of the Social Care Green Paper', $html);
        $this->assertContains('welcome sign of the growing debate about the future of social care', $html);
        $this->assertContains('exclude people with any pre-existing health conditions, so the number', $html);
        $this->assertContains('the careandsupport.direct.gov.uk website has had 90,000 hits', $html);
    }

    public function testMpDetails($path='/debates/?id=2009-10-29a.479.0') {
        ensureUrl(self::$webDriver, self::$base_url . $path);
        $speeches = getElementsByCss(self::$webDriver, '.debate-speech');
        $this->assertCount(128, $speeches);

        $firstSpeech = $speeches[0];

        $text = $firstSpeech->getText();
        $this->assertContains('Andy Burnham', $text);
        $this->assertContains('Secretary of State, Department of Health', $text);

        $img = getFirstElementByCss($firstSpeech, 'img');
        $this->assertContains('/images/mps/10766.jpg', $img->getAttribute('src'));
    }

    public function testHansardLink($path='/debates/?id=2009-10-29a.479.0') {
        ensureUrl(self::$webDriver, self::$base_url . $path);
        $firstSpeech = getFirstElementByCss(self::$webDriver, '.debate-speech');
        $url = 'http://www.publications.parliament.uk/pa/cm200809/cmhansrd/cm091029/debtext/91029-0010.htm#09102935001383';
        $hansardLinks = getElementsByCss($firstSpeech, "a[href='$url']");
        $this->assertCount(1, $hansardLinks);
    }

    // This one's a bit meta. We re-run all the previous tests, but on
    // a new URL. Since it's the same debate, just accessed via a different
    // URL, all the tests should still pass.
    public function testDebateByDateAndColumn() {
        $this->testDebateTitle('/debates/?d=2009-10-29&c=479');
        $this->testSpeechesText('/debates/?d=2009-10-29&c=479');
        $this->testMpDetails('/debates/?d=2009-10-29&c=479');
        $this->testHansardLink('/debates/?d=2009-10-29&c=479');
    }

    public function testDebateGlossaries() {
        // Disable glossaries using the ?ug= URL parameter
        ensureUrl(self::$webDriver, self::$base_url . '/debates/?id=2009-10-29a.479.0&ug=1');
        $links = getElementsByCss(self::$webDriver, 'a[href*="/glossary/"]');
        $this->assertCount(0, $links);

        // Glossary links are inserted by default
        ensureUrl(self::$webDriver, self::$base_url . '/debates/?id=2009-10-29a.479.0');
        $links = getElementsByCss(self::$webDriver, 'a[href*="/glossary/?gl=23"]');
        $this->assertGreaterThan(0, count($links));
        $this->assertContains('Secretary of State', $links[0].getText());
        $this->assertContains('originally the title given to the two officials', $links[0].getAttribute('title'));
    }

    public function testDebateHighlighting() {
        // No query highlighting by default
        ensureUrl(self::$webDriver, self::$base_url . '/debates/?id=2009-10-29a.479.0');
        $highlights = getElementsByCss(self::$webDriver, '.debate-speech span.hi');
        $this->assertCount(0, $highlights);

        // Query highlighting via ?s= URL parameter
        ensureUrl(self::$webDriver, self::$base_url . '/debates/?id=2009-10-29a.479.0&s=and');
        $highlights = getElementsByCss(self::$webDriver, '.debate-speech span.hi');
        $this->assertGreaterThan(0, count($highlights));
    }

}
?>
