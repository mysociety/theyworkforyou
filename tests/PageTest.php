<?php

/**
 * Provides test methods to ensure pages contain what we want them to in various ways.
 */
class PageTest extends FetchPageTestCase
{

    /**
     * Loads the member testing fixture.
     */
    public function getDataSet()
    {
        return $this->createMySQLXMLDataSet(dirname(__FILE__).'/_fixtures/member.xml');
    }

    private function fetch_page($vars)
    {
        return $this->base_fetch_page($vars, 'mp');
    }

    public function testQueenie()
    {
        $page = $this->fetch_page( array( 'representative_type' => 'royal', 'n' => 'elizabeth_the_second' ) );
        $this->assertContains('Elizabeth the Second', $page);
        $this->assertContains('Coronated on 2 June 1953', $page);
    }

    public function testSittingMP()
    {
        $page = $this->fetch_page( array( 'pid' => 2, 'url' => '/mp/2/test_current-mp/test_westminster_constituency' ) );
        $this->assertContains('Test Current-MP', $page);
        $this->assertRegexp('#<span class="person-header__about__position__constituency">\s*Test Westminster Constituency\s*</span>#', $page);
        $this->assertRegexp('#<span class="person-header__about__position__role">\s*Labour\s*MP\s*</span>#', $page);
    }

    public function testSittingMLA()
    {
        $page = $this->fetch_page( array( 'pid' => 4, 'representative_type' => 'mla', 'url' => '/mp/4/test_current-mla' ) );
        $this->assertContains('Test Current-MLA', $page);
        $this->assertRegexp('#<span class="person-header__about__position__constituency">\s*Test Northern Ireland Constituency\s*</span>#', $page);
        $this->assertRegexp('#<span class="person-header__about__position__role">\s*Sinn Féin\s*MLA\s*</span>#', $page);
    }

    /**
     * Ensure that the Sinn Fein message is displayed for SF MPs.
     */
    public function testSittingSinnFeinMP()
    {
        $page = $this->fetch_page( array( 'pid' => 15, 'url' => '/mp/15/test_current-sf-mp/test_westminster_constituency' ) );
        $this->assertContains('Sinn F&eacute;in MPs do not take their seats in Parliament.', $page);
    }

    /**
     * Ensure that the Sinn Fein message is not displayed for non-SF MPs.
     */
    public function testSittingNonSinnFeinMP()
    {
        $page = $this->fetch_page( array( 'pid' => 2, 'url' => '/mp/2/test_current-mp/test_westminster_constituency' ) );
        $this->assertNotContains('Sinn F&eacute;in MPs do not take their seats in Parliament.', $page);
    }

    /**
     * Ensure that the Speaker is given the correct constituency.
     */
    public function testSpeaker()
    {
        $page = $this->fetch_page( array( 'pid' => 13, 'url' => '/mp/13/test_speaker/buckingham' ) );
        $this->assertRegexp('#<span class="person-header__about__position__role">\s*Speaker\s*MP\s*</span>#', $page);
    }

    public function testBanner() {
        $banner = new MySociety\TheyWorkForYou\Model\AnnouncementManagement;

        # makes sure it is empty in case there's something hanging
        # about in memcached
        $banner->set_text('', "banner");
        $page = $this->fetch_page( array( 'url' => '/' ) );
        $this->assertNotContains('<div class="banner">', $page);
        $this->assertNotContains('This is a banner', $page);

        $banner_config = '
        [
            {
               "id":"basic-donate",
               "content":"This is a banner",
               "button_text":"Donate",
               "button_link":"https://www.mysociety.org/donate/",
               "button_class": "button--negative",
               "weight":1,
               "lang": "en",
               "published":true
            }
        ]
        ';
        
        $banner->set_text($banner_config, "banner");
        $page = $this->fetch_page( array( 'url' => '/' ) );
        $this->assertContains('This is a banner', $page);

        $banner->set_text('', "banner");
        $page = $this->fetch_page( array( 'url' => '/' ) );
        $this->assertNotContains('<div class="banner">', $page);
        $this->assertNotContains('This is a banner', $page);
    }

    public function testNewMPMessage() {
        $page = $this->fetch_page( array( 'pid' => 17, 'url' => '/mp/17/recent_mp/test_westminster_constituency' ) );
        $this->assertNotContains('is a recently elected MP', $page);
        $this->db->query('UPDATE member SET entered_house = NOW() WHERE person_id = 17');
        $page = $this->fetch_page( array( 'pid' => 17, 'url' => '/mp/17/recent_mp/test_westminster_constituency' ) );
        $this->assertContains('is a recently elected MP', $page);
    }

}
