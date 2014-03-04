<?php

/**
 * Provides test methods to ensure pages contain what we want them to in various ways.
 */
class PageTest extends PHPUnit_Extensions_Database_TestCase
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
        foreach ($vars as $k => $v) {
            $vars[$k] =  $k . '=' . urlencode($v);
        }
        $vars = join('&', $vars);
        $command = 'parse_str($argv[1], $_GET); include_once("tests/Bootstrap.php"); chdir("www/docs/mp"); include_once("index.php");';
        $page = `REMOTE_ADDR=127.0.0.1 php -e -r '$command' -- '$vars'`;
        return $page;
    }

	public function testSittingSinnFeinMP()
    {
        $page = $this->fetch_page( array( 'pid' => 2, 'url' => '/mp/2/test_current-mp/test_westminster_constituency' ) );
        $this->assertContains('Test Current-MP MP', $page);
        $this->assertContains('Sinn Fein <abbr title="Member of Parliament">MP</abbr> for Test Westminster Constituency', $page);
        #$this->assertContains('Sinn F&eacute;in MPs do not take their seats in Parliament</li>', $page);
    }

	public function testSittingMLA()
    {
        $page = $this->fetch_page( array( 'pid' => 4, 'url' => '/mp/4/test_current-mla/test_northern_ireland_constituency' ) );
        $this->assertContains('Test Current-MLA MP', $page);
        $this->assertContains('<span class="constituency">Test Northern Ireland Constituency</span> <span class="party SF">Sinn Fein</span>', $page);
        #$this->assertContains('Former Sinn F&eacute;in <abbr title="Member of the Legislative Assembly">MLA</abbr> for Fermanagh and South Tyrone', $page);
        #$this->assertContains('Sinn F&eacute;in MPs do not take their seats in Parliament</li>', $page);
    }

	public function testQueenie()
    {
        $page = $this->fetch_page( array( 'royal' => 1, 'n' => 'elizabeth_the_second' ) );
        $this->assertContains('Elizabeth the Second', $page);
        $this->assertContains('Coronated on 2 June 1953', $page);
    }

	public function testSpeaker()
    {
        $page = $this->fetch_page( array( 'pid' => 12, 'url' => '/mp/12/test_speaker/buckingham' ) );
        $this->assertContains('<span class="constituency">Buckingham</span> <span class="party SPK">Speaker</span>', $page);
    }

}
