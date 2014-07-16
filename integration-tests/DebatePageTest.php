<?php

// I couldn't work out how to add these integration tests to our PHPUnit tests
// in the /tests directory. So they're here. Run them with:
//
//     vendor/bin/phpunit -v --no-configuration ./integration-tests
//
// The tests assume there's an instance of TheyWorkForYou running at
// theyworkforyou.dev, with some sample data (debates from October 2009).

require 'helpers.php';

class DebatePageTest extends PHPUnit_Framework_TestCase {

    protected static $webDriver;
    protected static $base_url = 'http://theyworkforyou.dev';

    public static function setUpBeforeClass() {
        // Instance methods at: http://facebook.github.io/php-webdriver/classes/RemoteWebDriver.html
        self::$webDriver = RemoteWebDriver::create('http://localhost:4444/wd/hub', DesiredCapabilities::chrome());
        self::$webDriver->get(self::$base_url . '/debate/?id=2009-10-29a.479.0');
    }

    public static function tearDownAfterClass() {
        if(isset(self::$webDriver)) {
            self::$webDriver->close();
        }
    }

    public function testDebateTitle() {
        $title = self::$webDriver->getTitle();
        $this->assertContains('Social Care Green Paper', $title);
        $this->assertContains('House of Commons', $title);
        $this->assertContains('debate', strtolower($title));
    }

    public function testSpeechesText() {
        $html = self::$webDriver->getPageSource();
        $this->assertContains('considered the matter of the Social Care Green Paper', $html);
        $this->assertContains('welcome sign of the growing debate about the future of social care', $html);
        $this->assertContains('exclude people with any pre-existing health conditions, so the number', $html);
        $this->assertContains('the careandsupport.direct.gov.uk website has had 90,000 hits', $html);
    }

    public function testMpDetails() {
        $speeches = getElementsByCss(self::$webDriver, '.speech');
        $this->assertEquals(128, count($speeches));

        $firstSpeech = $speeches[0];

        $text = $firstSpeech->getText();
        $this->assertContains('Andy Burnham', $text);
        $this->assertContains('Secretary of State, Department of Health', $text);

        $img = getFirstElementByCss($firstSpeech, 'img');
        $this->assertContains('/images/mps/10766.jpg', $img->getAttribute('src'));
    }

    public function testHansardLink() {
        $firstSpeech = getFirstElementByCss(self::$webDriver, '.speech');
        $url = 'http://www.publications.parliament.uk/pa/cm200809/cmhansrd/cm091029/debtext/91029-0010.htm#09102935001383';
        $hansardLinks = getElementsByCss($firstSpeech, "a[href='$url']");
        $this->assertEquals(1, count($hansardLinks));
    }

}
?>
