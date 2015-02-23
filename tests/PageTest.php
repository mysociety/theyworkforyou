<?php

include_once 'FetchPageTestCase.php';

/**
 * Provides test methods to ensure pages contain what we want them to in various ways.
 */
class PageTest extends FetchPageTestCase
{

    /**
     * Connects to the testing database.
     */
    public function getConnection()
    {
        $dsn = 'mysql:host=' . OPTION_TWFY_DB_HOST . ' ;dbname=' . OPTION_TWFY_DB_NAME;
        $username = OPTION_TWFY_DB_USER;
        $password = OPTION_TWFY_DB_PASS;
        $pdo = new PDO($dsn, $username, $password);
        return $this->createDefaultDBConnection($pdo, OPTION_TWFY_DB_NAME);
    }

    /**
     * Loads the member testing fixture.
     */
    public function getDataSet()
    {
        return $this->createMySQLXMLDataSet(dirname(__FILE__).'/_fixtures/member.xml');
    }

    private function fetch_page($vars)
    {
        return $this->base_fetch_page('', $vars, 'www/docs/mp');
    }

    public function testQueenie()
    {
        $page = $this->fetch_page( array( 'royal' => 1, 'n' => 'elizabeth_the_second' ) );
        $this->assertContains('Elizabeth the Second', $page);
        $this->assertContains('Coronated on 2 June 1953', $page);
    }

    public function testSittingMP()
    {
        $page = $this->fetch_page( array( 'pid' => 2, 'url' => '/mp/2/test_current-mp/test_westminster_constituency' ) );
        $this->assertContains('Test Current-MP', $page);
        $this->assertContains('<span class="constituency">Test Westminster Constituency</span>', $page);
        $this->assertContains('<span class="party Lab">Labour</span>', $page);
    }

	public function testSittingMLA()
    {
        $page = $this->fetch_page( array( 'pid' => 4, 'url' => '/mp/4/test_current-mla/test_northern_ireland_constituency' ) );
        $this->assertContains('Test Current-MLA', $page);
        $this->assertContains('<span class="constituency">Test Northern Ireland Constituency</span>', $page);
        $this->assertContains('<span class="party SF">Sinn Fein</span>', $page);
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
        $this->assertContains('<span class="party SPK">Speaker</span>', $page);
    }

}
