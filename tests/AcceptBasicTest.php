<?php

include_once 'FetchPageTestCase.php';

/**
 * Provides acceptance(ish) tests to ensure key pages are working.
 */
class AcceptBasicTest extends FetchPageTestCase
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
     * Loads the acceptance testing fixture.
     */
    public function getDataSet()
    {
        return $this->createMySQLXMLDataSet(dirname(__FILE__).'/_fixtures/acceptance.xml');
    }

    private function fetch_page($path, $file = 'index.php', $vars = array())
    {
        return $this->base_fetch_page('', $vars, "www/docs/$path", $file);
    }

    /**
     * Does the homepage still work?
     */
    public function testHome()
    {
        $page = $this->fetch_page('');
        $this->assertContains('Your representative', $page);
        $this->assertContains('Search, create an email alert or RSS feed', $page);
        $this->assertContains('What&rsquo;s up next:', $page);
    }

    /**
     * Does the list of MPs still work?
     */
    public function testMPList()
    {
        $page = $this->fetch_page('mps');
        $this->assertContains('All MPs', $page);
        $this->assertContains('Test Current-MP', $page);
    }

    /**
     * Does the list of Lords still work?
     */
    public function testLordsList()
    {
        $page = $this->fetch_page('mps', 'index.php', array('peer' => '1'));
        $this->assertContains('All Members of the House of Lords', $page);
        $this->assertContains('Mr Current-Lord', $page);
    }

    /**
     * Does the list of MSPs still work?
     */
    public function testMSPList()
    {
        $page = $this->fetch_page('mps', 'index.php', array('msp' => '1'));
        $this->assertContains('Scottish Parliament', $page);
        $this->assertContains('All MSPs', $page);
        $this->assertContains('Test Current-MSP', $page);
    }

    /**
     * Does the list of MSPs still work?
     */
    public function testMLAList()
    {
        $page = $this->fetch_page('mps', 'index.php', array('mla' => '1'));
        $this->assertContains('Northern Ireland Assembly', $page);
        $this->assertContains('All MLAs', $page);
        $this->assertContains('Test Current-MLA', $page);
    }

    /**
     * Does the debates top page still work?
     */
    public function testDebatesList()
    {
        $page = $this->fetch_page('', 'section.php', array('type' => 'debates'));
        $this->assertContains('UK Parliament Hansard Debates', $page);
        $this->assertContains('Recent House of Commons debates', $page);
        $this->assertContains('Test Hansard Section', $page);
        $this->assertContains('Test Hansard Subsection', $page);
        $this->assertContains('(6 speeches)', $page);
        $this->assertContains('Wednesday, 1 January 2014', $page);
    }

}
