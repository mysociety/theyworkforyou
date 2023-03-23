<?php

/**
 * Testing for postcode Utility functions
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

    /**
     * Test converting a postcode to a constituency
     */
    public function testPostcodeToConstituency()
    {
        $this->assertEquals(
            'Cities of London and Westminster',
            MySociety\TheyWorkForYou\Utility\Postcode::postcodeToConstituency('SW1A 1AA')
        );
    }

    /**
     * Test converting a broken postcode to a constituency, make sure we get an empty string
     */
    public function testBrokenPostcodeToConstituency()
    {
        $this->assertEquals(
            '',
            MySociety\TheyWorkForYou\Utility\Postcode::postcodeToConstituency('ZZ00 ABC')
        );
    }

    /**
     * Test canonicalising a postcode
     */
    public function testCanonicalisePostcode()
    {
        $this->assertEquals(
            'SW1A 1AA',
            MySociety\TheyWorkForYou\Utility\Postcode::canonicalisePostcode('SW1A 1AA')
        );
        $this->assertEquals(
            'SW1A 1AA',
            MySociety\TheyWorkForYou\Utility\Postcode::canonicalisePostcode('SW1A1AA')
        );
        $this->assertEquals(
            'SW1A 1AA',
            MySociety\TheyWorkForYou\Utility\Postcode::canonicalisePostcode('sw1a 1aa')
        );
        $this->assertEquals(
            'SW1A 1AA',
            MySociety\TheyWorkForYou\Utility\Postcode::canonicalisePostcode(' SW1A 1AA ')
        );
        $this->assertEquals(
            'SW1A 1AA',
            MySociety\TheyWorkForYou\Utility\Postcode::canonicalisePostcode('SW1 A1AA')
        );
    }

    /**
     * Test testing for Scottish postcode
     */
    public function testPostcodeIsScottish()
    {
        $this->assertEquals(
            "Edinburgh",
            MySociety\TheyWorkForYou\Utility\Postcode::postcodeToConstituency('EH1 0AA')
        );
    }

    /**
     * Test testing for NI postcode
     */
    public function testPostcodeIsNi()
    {
        $this->assertEquals(
            "Belfast",
            MySociety\TheyWorkForYou\Utility\Postcode::postcodeToConstituency('BT1 0AA')
        );
    }

}
