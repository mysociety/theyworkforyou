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
        $vars['method'] = $method;
        return $this->base_fetch_page($vars, 'api');
    }

    /**
     * Ensure that not providing a key throws the right error
     */
    public function testMissingKeyFailure()
    {
        $page = $this->fetch_page('getConstituencies');
        $this->assertEquals('{"error":"No API key provided. Please see https://www.theyworkforyou.com/api/key for more information."}', $page);
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

    private function _testGetJSON($page, $key, $value, $result) {
        $page = $this->fetch_page($page, array(
            'key' => 'test_key',
            $key => $value
        ));
        $this->assertEquals(json_decode($result), json_decode($page));
    }

    public function testGetConstituencies() {
        $this->_testGetJSON('getConstituencies', 'dummy', 1, '[
            {"name":"Alyn and Deeside"},
            {"name":"Amber Valley"},
            {"name":"Belfast West"},
            {"name":"Cities of London and Westminster"}
            ]');
    }

    public function testGetConstituencyByName() {
        $this->_testGetJSON('getConstituency', 'name', 'Amber Valley',
            '{"name":"Amber Valley"}');
    }

    public function testGetConstituencyByPostcode() {
        $this->_testGetJSON('getConstituency', 'postcode', 'SW1A 1AA',
            '{"name":"Cities of London and Westminster"}');
    }

    public function testGetConstituencyByAlternateName() {
        $this->_testGetJSON('getConstituency', 'name', 'Alyn & Deeside',
            '{"name":"Alyn and Deeside"}');
    }

    public function testGetConstituencyByIncorrectName() {
        $this->_testGetJSON('getConstituency', 'name', 'No Such Constituency',
            '{"error":"Could not find anything with that name"}');
    }

    public function testGetMpByPostcode()
    {
        $this->_testGetJSON('getMP', 'postcode', 'SW1A 1AA',
            '{"member_id":"2","house":"1","given_name":"Test","family_name":"Current-City-MP","constituency":"Cities of London and Westminster","party":"Labour","entered_house":"2000-01-01","left_house":"9999-12-31","entered_reason":"general_election","left_reason":"still_in_office","person_id":"3","title":"Mr","lastupdate":"2013-08-07 15:06:19","full_name":"Mr Test Current-City-MP","url":"/mp/3/mr_test_current-city-mp/cities_of_london_and_westminster"}');
    }

    public function testGetMpByConstituency()
    {
        $this->_testGetJSON('getMP', 'constituency', 'Amber Valley',
            '{"member_id":"1","house":"1","given_name":"Test","family_name":"Current-MP","constituency":"Amber Valley","party":"Labour","entered_house":"2000-01-01","left_house":"9999-12-31","entered_reason":"general_election","left_reason":"still_in_office","person_id":"2","title":"Mrs","lastupdate":"2013-08-07 15:06:19","full_name":"Mrs Test Current-MP","url":"/mp/2/mrs_test_current-mp/amber_valley"}');
    }

    public function testGetMpById()
    {
        $this->_testGetJSON('getMP', 'id', '2',
            '[{"member_id":"1","house":"1","given_name":"Test","family_name":"Current-MP","constituency":"Amber Valley","party":"Labour","entered_house":"2000-01-01","left_house":"9999-12-31","entered_reason":"general_election","left_reason":"still_in_office","person_id":"2","title":"Mrs","lastupdate":"2013-08-07 15:06:19","full_name":"Mrs Test Current-MP","url":"/mp/2/mrs_test_current-mp/amber_valley"}]');
    }

    public function testGetMlasLookup() {
        $result = '[
{"member_id":"101","house":"3","given_name":"Test1","family_name":"Nimember","constituency":"Belfast West","party":"DUP","entered_house":"2000-01-01","left_house":"9999-12-31","entered_reason":"general_election","left_reason":"still_in_office","person_id":"101","title":"Mr","lastupdate":"2013-08-07 15:06:19","full_name":"Mr Test1 Nimember"},
{"member_id":"102","house":"3","given_name":"Test2","family_name":"Nimember","constituency":"Belfast West","party":"DUP","entered_house":"2000-01-01","left_house":"9999-12-31","entered_reason":"general_election","left_reason":"still_in_office","person_id":"102","title":"Ms","lastupdate":"2013-08-07 15:06:19","full_name":"Ms Test2 Nimember"}
        ]';
        $this->_testGetJSON('getMLA', 'postcode', 'BT17 0XD', $result);
        $this->_testGetJSON('getMLA', 'constituency', 'Belfast West', $result);
        $this->_testGetJSON('getMLA', 'id', '101',
            '[{"member_id":"101","house":"3","given_name":"Test1","family_name":"Nimember","constituency":"Belfast West","party":"DUP","entered_house":"2000-01-01","left_house":"9999-12-31","entered_reason":"general_election","left_reason":"still_in_office","person_id":"101","title":"Mr","lastupdate":"2013-08-07 15:06:19","full_name":"Mr Test1 Nimember"}]');
    }

}
