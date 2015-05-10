<?php

/**
 * Provides acceptance(ish) tests for API functions.
 */
class AcceptApiTest extends FetchPageTestCase
{

    /**
     * Loads the api testing fixture.
     */
    public function getDataSet()
    {
        return $this->createMySQLXMLDataSet(dirname(__FILE__).'/_fixtures/api.xml');
    }

    private function fetch_page($method, $vars = array())
    {
        return $this->base_fetch_page($method, $vars, 'www/docs/api');
    }

    /**
     * Ensure that not providing a key throws the right error
     */
    public function testMissingKeyFailure()
    {
        $page = $this->fetch_page('getConstituencies');
        $this->assertEquals('{"error":"No API key provided. Please see http://www.theyworkforyou.com/api/key for more information."}', $page);
    }

    /**
     * Ensure that providing an incorrect key throws the right error
     */
    public function testIncorrectKeyFailure()
    {
        $page = $this->fetch_page('getConstituencies', array(
            'key' => 'invalid_key'
        ));
        $this->assertEquals('{"error":"Invalid API key."}', $page);
    }

    /**
     * Test getting a list of all constituencies
     */
    public function testGetConstituencies()
    {
        $page = $this->fetch_page('getConstituencies', array(
            'key' => 'test_key'
        ));
        $this->assertEquals('[{"name":"Alyn and Deeside"},{"name":"Amber Valley"},{"name":"Cities of London and Westminster"}]', $page);
    }

    /**
     * Test getting a constituency by name
     */
    public function testGetConstituencyByName()
    {
        $page = $this->fetch_page('getConstituency', array(
            'key' => 'test_key',
            'name' => 'Amber Valley'
        ));
        $this->assertEquals('{"name":"Amber Valley"}', $page);
    }

    /**
     * Test getting a constituency by postcode
     */
    public function testGetConstituencyByPostcode()
    {
        $page = $this->fetch_page('getConstituency', array(
            'key' => 'test_key',
            'postcode' => 'SW1A 1AA'
        ));
        $this->assertEquals('{"name":"Cities of London and Westminster"}', $page);
    }

    /**
     * Test getting a constituency by an alternate name
     */
    public function testGetConstituencyByAlternateName()
    {
        $page = $this->fetch_page('getConstituency', array(
            'key' => 'test_key',
            'name' => 'Alyn & Deeside'
        ));
        $this->assertEquals('{"name":"Alyn and Deeside"}', $page);
    }

    /**
     * Test getting a constituency by incorrect name
     */
    public function testGetConstituencyByIncorrectName()
    {
        $page = $this->fetch_page('getConstituency', array(
            'key' => 'test_key',
            'name' => 'No Such Constituency'
        ));
        $this->assertEquals('{"error":"Could not find anything with that name"}', $page);
    }

    /**
     * Test getting a MP by postcode
     */
    public function testGetMpByPostcode()
    {
        $page = $this->fetch_page('getMP', array(
            'key' => 'test_key',
            'postcode' => 'SW1A 1AA'
        ));
        $page = json_decode($page);
        $this->assertEquals(json_decode('{"member_id":"2","house":"1","first_name":"Test","last_name":"Current-City-MP","constituency":"Cities of London and Westminster","party":"Labour","entered_house":"2000-01-01","left_house":"9999-12-31","entered_reason":"general_election","left_reason":"still_in_office","person_id":"3","title":"Mr","lastupdate":"2013-08-07 15:06:19","full_name":"Mr Test Current-City-MP","url":"/mp/3/mr_test_current-city-mp/cities_of_london_and_westminster"}'), $page);
    }

    /**
     * Test getting a MP by constituency
     */
    public function testGetMpByConstituency()
    {
        $page = $this->fetch_page('getMP', array(
            'key' => 'test_key',
            'constituency' => 'Amber Valley'
        ));
        $page = json_decode($page);
        $this->assertEquals(json_decode('{"member_id":"1","house":"1","first_name":"Test","last_name":"Current-MP","constituency":"Amber Valley","party":"Labour","entered_house":"2000-01-01","left_house":"9999-12-31","entered_reason":"general_election","left_reason":"still_in_office","person_id":"2","title":"Mrs","lastupdate":"2013-08-07 15:06:19","full_name":"Mrs Test Current-MP","url":"/mp/2/mrs_test_current-mp/amber_valley"}'), $page);
    }

    /**
     * Test getting a MP by ID
     */
    public function testGetMpById()
    {
        $page = $this->fetch_page('getMP', array(
            'key' => 'test_key',
            'id' => '2'
        ));
        $page = json_decode($page);
        $this->assertEquals(json_decode('[{"member_id":"1","house":"1","first_name":"Test","last_name":"Current-MP","constituency":"Amber Valley","party":"Labour","entered_house":"2000-01-01","left_house":"9999-12-31","entered_reason":"general_election","left_reason":"still_in_office","person_id":"2","title":"Mrs","lastupdate":"2013-08-07 15:06:19","full_name":"Mrs Test Current-MP","url":"/mp/2/mrs_test_current-mp/amber_valley"}]'), $page);
    }

}
