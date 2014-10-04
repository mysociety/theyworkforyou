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
        $HANSARD = new HANSARDLIST();

        $args = array(
            'member_ids' => '1,2'
        );

        $response = $HANSARD->_get_data_by_person($args);

        // Ensure we have four rows
        $this->assertEquals(4, count($response['rows']));

        // Make sure all four rows are the expected ones
        $this->assertEquals(7, $response['rows'][0]['gid']);
        $this->assertEquals(6, $response['rows'][1]['gid']);
        $this->assertEquals(4, $response['rows'][2]['gid']);
        $this->assertEquals(3, $response['rows'][3]['gid']);
    }

}
