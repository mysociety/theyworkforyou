<?php

/**
 * Provides acceptance(ish) tests to ensure key pages are working.
 */
class AcceptBasicTest extends FetchPageTestCase
{

    /**
     * Loads the acceptance testing fixture.
     */
    public function getDataSet()
    {
        return $this->createMySQLXMLDataSet(dirname(__FILE__).'/_fixtures/acceptance.xml');
    }

    private function fetch_page($path, $file = 'index.php', $vars = array())
    {
        return $this->base_fetch_page($vars, $path, $file);
    }

    /**
     * Does the homepage still work?
     */
    public function testHome()
    {
        $page = $this->fetch_page('');
        $this->assertStringContainsString('Find out more', $page);
        $this->assertStringContainsString('Create an alert', $page);
        $this->assertStringContainsString('Upcoming', $page);
    }

    /**
     * Does the list of MPs still work?
     */
    public function testMPList()
    {
        $page = $this->fetch_page('mps');
        $this->assertStringContainsString('All MPs', $page);
        $this->assertStringContainsString('Test Current-MP', $page);
    }

    /**
     * Does the list of Lords still work?
     */
    public function testLordsList()
    {
        $page = $this->fetch_page('mps', 'index.php', array('representative_type' => 'peer'));
        $this->assertStringContainsString('All Members of the House of Lords', $page);
        $this->assertStringContainsString('Mr Current-Lord', $page);
    }

    /**
     * Does the list of MSPs still work?
     */
    public function testMSPList()
    {
        $page = $this->fetch_page('mps', 'index.php', array('representative_type' => 'msp'));
        $this->assertStringContainsString('Scottish Parliament', $page);
        $this->assertStringContainsString('All MSPs', $page);
        $this->assertStringContainsString('Test Current-MSP', $page);
    }

    /**
     * Does the list of MSPs still work?
     */
    public function testMLAList()
    {
        $page = $this->fetch_page('mps', 'index.php', array('representative_type' => 'mla'));
        $this->assertStringContainsString('Northern Ireland Assembly', $page);
        $this->assertStringContainsString('All MLAs', $page);
        $this->assertStringContainsString('Test Current-MLA', $page);
    }

    /**
     * Does the debates top page still work?
     */
    public function testDebatesList()
    {
        $page = $this->fetch_page('', 'section.php', array('type' => 'debates'));
        $this->assertStringContainsString('UK Parliament Hansard Debates', $page);
        $this->assertStringContainsString('Recent House of Commons debates', $page);
        $this->assertStringContainsString('Test Hansard Section', $page);
        $this->assertStringContainsString('Test Hansard Subsection', $page);
        $this->assertStringContainsString('6 speeches', $page);
        $this->assertStringContainsString('Wednesday,  1 January 2014', $page);
    }

}
