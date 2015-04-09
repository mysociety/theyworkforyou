<?php

/**
 * Provides test methods for Hansard functionality.
 */
class HansardTest extends TWFY_Database_TestCase
{

    /**
     * Loads the Hansard testing fixture.
     */
    public function getDataSet()
    {
        return $this->createMySQLXMLDataSet(dirname(__FILE__).'/_fixtures/hansard.xml');
    }

    /**
     * Ensures the database is prepared and the alert class is included for every test.
     */
    public function setUp()
    {
        parent::setUp();

        include_once('www/includes/easyparliament/hansardlist.php');
    }

    /**
     * Test getting the most recent day
     */
    public function testMostRecentDay()
    {
        $HANSARD = new HANSARDLIST();
        $HANSARD->major = 1;
        $HANSARD->listpage = 'test';

        $response = $HANSARD->most_recent_day();

        // Make sure the date is as expected
        $this->assertEquals('2014-01-02', $response['hdate']);
    }

    /**
     * Test that getting data by person works
     */
    public function testGetDataByPerson()
    {
        $HANSARD = new HANSARDLIST();

        $args = array(
            'person_id' => '2'
        );

        $response = $HANSARD->_get_data_by_person($args);

        // Ensure we have four rows
        $this->assertEquals(4, count($response['rows']));

        // Make sure all four rows are the expected ones, in the expected order
        $this->assertEquals(7, $response['rows'][0]['gid']);
        $this->assertEquals(6, $response['rows'][1]['gid']);
        $this->assertEquals(5, $response['rows'][2]['gid']);
        $this->assertEquals(3, $response['rows'][3]['gid']);
    }

    /**
     * Test that getting data by date works
     */
    public function testGetDataByDate()
    {
        $HANSARD = new HANSARDLIST();

        $HANSARD->major = 1;
        $HANSARD->listpage = 'test';

        $args = array(
            'date' => '2014-01-01'
        );

        $response = $HANSARD->_get_data_by_date($args);

        // Ensure we have four rows
        $this->assertEquals(4, count($response['rows']));

        // Make sure all five rows are the expected ones, in the expected order
        $this->assertEquals(3, $response['rows'][0]['gid']);
        $this->assertEquals(5, $response['rows'][1]['gid']);
        $this->assertEquals(6, $response['rows'][2]['gid']);
        $this->assertEquals(7, $response['rows'][3]['gid']);
    }

    /**
     * Test that getting data by GID works as expected.
     *
     * This test inadvertently runs about a billion other bits of code.
     */
    public function testGetDataByGid()
    {
        $HANSARD = new HANSARDLIST();

        $HANSARD->major = 1;
        $HANSARD->gidprefix = 'com.theyworkforyou/test/hansard/';
        $HANSARD->listpage = 'test';

        $args = array(
            'gid' => '3'
        );

        $response = $HANSARD->_get_data_by_gid($args);

        // Ensure we have one row
        $this->assertEquals(1, count($response['rows']));

        // Make sure the row is the expected one
        $this->assertEquals(3, $response['rows'][0]['gid']);
    }

    /**
     * Test that getting an item works as expected.
     */
    public function testGetItem()
    {
        $HANSARD = new HANSARDLIST();

        $HANSARD->major = 1;
        $HANSARD->gidprefix = 'com.theyworkforyou/test/hansard/';

        $args = array(
            'gid' => '3'
        );

        $response = $HANSARD->_get_item($args);

        // Ensure the response is the expected object
        $this->assertEquals(3, $response['gid']);
    }

    /**
     * Test that getting a speaker works.
     */
    public function testGetSpeaker()
    {
        $HANSARD = new HANSARDLIST();

        $response = $HANSARD->_get_speaker(3, '2014-01-01', '00:00:00', 1);

        // Ensure the response is the expected object
        $this->assertEquals(3, $response['person_id']);
    }

}
