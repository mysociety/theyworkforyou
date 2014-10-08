<?php

/**
 * Provides test methods for Hansard functionality.
 */
class HansardTest extends PHPUnit_Extensions_Database_TestCase
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
     * Test that getting data by person works
     */
    public function testGetDataByPerson()
    {
        $HANSARD = new \MySociety\TheyWorkForYou\HansardList();

        $args = array(
            'member_ids' => '1,2'
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
        $HANSARD = new \MySociety\TheyWorkForYou\HansardList();

        $HANSARD->major = 1;
        $HANSARD->listpage = 'test';

        $args = array(
            'date' => '2014-01-01'
        );

        $response = $HANSARD->_get_data_by_date($args);

        // Ensure we have five rows
        $this->assertEquals(5, count($response['rows']));

        // Make sure all five rows are the expected ones, in the expected order
        $this->assertEquals(3, $response['rows'][0]['gid']);
        $this->assertEquals(5, $response['rows'][1]['gid']);
        $this->assertEquals(4, $response['rows'][2]['gid']);
        $this->assertEquals(6, $response['rows'][3]['gid']);
        $this->assertEquals(7, $response['rows'][4]['gid']);
    }

}
