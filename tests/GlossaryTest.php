<?php

class GlossaryTest extends PHPUnit_Extensions_Database_TestCase
{

    public function getConnection()
    {
        $dsn = 'mysql:host=' . OPTION_TWFY_DB_HOST . ' ;dbname=' . OPTION_TWFY_DB_NAME;
        $username = OPTION_TWFY_DB_USER;
        $password = OPTION_TWFY_DB_PASS;
        $pdo = new PDO($dsn, $username, $password);
        return $this->createDefaultDBConnection($pdo, OPTION_TWFY_DB_NAME);
    }

    public function getDataSet()
    {
        return $this->createFlatXMLDataSet(dirname(__FILE__).'/_fixtures/glossary.xml');
    }

	public function setUp() {
		include_once('www/includes/easyparliament/glossary.php');

		$args['sort'] = "regexp_replace";
		$this->glossary = new GLOSSARY($args);
	 }

	public function testGlossariseNormal()
    {
        $this->assertEquals('<a href="/glossary/?gl=169" title="In a general election, each Constituency chooses an MP to represent them...." class="glossary">constituency</a>', $this->glossary->glossarise('constituency'));
    }

    public function testGlossariseInLink()
    {
        $this->assertEquals('<a href="#">constituency</a>', $this->glossary->glossarise('<a href="#">constituency</a>'));
    }

    public function testGlossariseInString()
    {
        $this->assertEquals('fooconstituencybar', $this->glossary->glossarise('fooconstituencybar'));
    }

    public function testGlossariseInSpacedString()
    {
        $this->assertEquals('foo <a href="/glossary/?gl=169" title="In a general election, each Constituency chooses an MP to represent them...." class="glossary">constituency</a> bar', $this->glossary->glossarise('foo constituency bar'));
    }

    public function testWikipediaLinkNormal()
    {
        $this->assertEquals('<a href="http://en.wikipedia.org/wiki/MP">MP</a>', $this->glossary->glossarise('MP'));
    }

    public function testWikipediaLinkInLink()
    {
        $this->assertEquals('<a href="#">MP</a>', $this->glossary->glossarise('<a href="#">MP</a>'));
    }

    public function testWikipediaLinkInString()
    {
        $this->assertEquals('fooMPbar', $this->glossary->glossarise('fooMPbar'));
    }

    public function testWikipediaLinkInSpacedString()
    {
        $this->assertEquals('foo <a href="http://en.wikipedia.org/wiki/MP">MP</a> bar', $this->glossary->glossarise('foo MP bar'));
    }
}