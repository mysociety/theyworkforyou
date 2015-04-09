<?php

/**
 * Testing for functions in postcode.inc
 */

class PostcodeTest extends TWFY_Database_TestCase
{

    /**
     * Loads the member testing fixture.
     */
    public function getDataSet()
    {
        return $this->createMySQLXMLDataSet(dirname(__FILE__).'/_fixtures/postcode.xml');
    }

	public function setUp()
	{
        parent::setUp();
        include_once('www/includes/postcode.inc');
    }

    /**
     * Test converting a postcode to a constituency
     */
	public function testPostcodeToConstituency()
    {
        $this->assertEquals(
            'Cities of London and Westminster',
            postcode_to_constituency('SW1A 1AA')
        );
    }

    /**
     * Test converting a broken postcode to a constituency, make sure we get an empty string
     */
    public function testBrokenPostcodeToConstituency()
    {
        $this->assertEquals(
            '',
            postcode_to_constituency('ZZ00 ABC')
        );
    }

    /**
     * Test canonicalising a postcode
     */
    public function testCanonicalisePostcode()
    {
        $this->assertEquals(
            'SW1A 1AA',
            canonicalise_postcode('SW1A 1AA')
        );
        $this->assertEquals(
            'SW1A 1AA',
            canonicalise_postcode('SW1A1AA')
        );
        $this->assertEquals(
            'SW1A 1AA',
            canonicalise_postcode('sw1a 1aa')
        );
        $this->assertEquals(
            'SW1A 1AA',
            canonicalise_postcode(' SW1A 1AA ')
        );
        $this->assertEquals(
            'SW1A 1AA',
            canonicalise_postcode('SW1 A1AA')
        );
    }

    /**
     * Test testing for Scottish postcode
     */
    public function testPostcodeIsScottish()
    {
        $this->assertEquals(
            true,
            postcode_is_scottish('EH1 0AA')
        );
        $this->assertEquals(
            false,
            postcode_is_scottish('SW1A 1AA')
        );
    }

    /**
     * Test testing for NI postcode
     */
    public function testPostcodeIsNi()
    {
        $this->assertEquals(
            true,
            postcode_is_ni('BT1 0AA')
        );
        $this->assertEquals(
            false,
            postcode_is_ni('SW1A 1AA')
        );
    }

}
