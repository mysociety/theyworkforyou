<?php

/**
 * Provides test methods for member functionality.
 */
class MemberTest extends PHPUnit_Extensions_Database_TestCase
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
     * Loads the member testing fixture.
     */
    public function getDataSet()
    {
        return $this->createMySQLXMLDataSet(dirname(__FILE__).'/_fixtures/member.xml');
    }

    /**
     * Ensures the database is prepared and the member class is included for every test.
     */
    public function setUp()
    {
        parent::setUp();

        include_once('www/includes/easyparliament/member.php');
    }

    /**
     * Test that a member is correctly retrieved by person ID alone.
     */
    public function testGetMPByPersonID()
    {
        $MEMBER = new MEMBER(array('person_id' => 2));

        $this->assertEquals(1, $MEMBER->member_id);
        $this->assertEquals(2, $MEMBER->person_id);
    }

    /**
     * Test that a member is correctly retrieved by member ID alone.
     */
    public function testGetMPByMemberID()
    {
        $MEMBER = new MEMBER(array('member_id' => 1));

        $this->assertEquals(1, $MEMBER->member_id);
        $this->assertEquals(2, $MEMBER->person_id);
    }

    /**
     * Test that a member is correctly retrieved by name.
     */
    public function testGetMPByName()
    {
        global $this_page;
        $this_page = 'mp';

        $MEMBER = new MEMBER(array('name' => 'test current-mp'));

        $this->assertEquals(1, $MEMBER->member_id);
        $this->assertEquals(2, $MEMBER->person_id);
    }

    /**
     * Test that the current member is correctly retrieved by constituency.
     */
    public function testGetMPByConstituency()
    {
        $MEMBER = new MEMBER(array('constituency' => 'test westminster constituency'));

        $this->assertEquals(1, $MEMBER->member_id);
        $this->assertEquals(2, $MEMBER->person_id);
    }

    /**
     * Test that a member is correctly retrieved by name and constituency.
     */
    public function testGetMPByNameAndConstituency()
    {
        global $this_page;
        $this_page = 'mp';

        $MEMBER = new MEMBER(array('name' => 'test current-mp', 'constituency' => 'test westminster constituency'));

        $this->assertEquals(1, $MEMBER->member_id);
        $this->assertEquals(2, $MEMBER->person_id);
    }

    /**
     * Test that members with duplicate names are retrieved as expected.
     */
    public function testGetDuplicateMPsByName()
    {
        global $this_page;
        $this_page = 'mp';

        $MEMBER = new MEMBER(array('name' => 'test duplicate-mp'));

        #var_dump($MEMBER);

        $this->assertEquals(NULL, $MEMBER->member_id);
        $this->assertEquals(array(11, 10), $MEMBER->person_id);
    }

    /**
     * Test that MP URLs are generated correctly.
     */
    public function testGetMPURL()
    {
        $MEMBER = new MEMBER(array('person_id' => 2));

        $this->assertEquals('http://' . DOMAIN . '/mp/2/test_current-mp/test_westminster_constituency', $MEMBER->url());
    }

    /**
     * Test that MP URLs with special characters are generated correctly.
     *
     * Special characters in URLs *should* be encoded.
     */
    public function testGetMPSpecialCharacterURL()
    {
        $MEMBER = new MEMBER(array('person_id' => 12));

        $this->assertEquals('http://' . DOMAIN . '/mp/12/test_special-character-constituency/test_constituency%2C_comma', $MEMBER->url());
    }

    /**
     * Test that Peer URLs are generated correctly.
     */
    public function testGetPeerURL()
    {
        $MEMBER = new MEMBER(array('person_id' => 3));

        $this->assertEquals('http://' . DOMAIN . '/peer/3/mr_current-lord', $MEMBER->url());
    }

    /**
     * Test that MLA URLs are generated correctly.
     */
    public function testGetMLAURL()
    {
        $MEMBER = new MEMBER(array('person_id' => 8));

        $this->assertEquals('http://' . DOMAIN . '/mla/8/test_previous-mla', $MEMBER->url());
    }

    /**
     * Test that MSP URLs are generated correctly.
     */
    public function testGetMSPURL()
    {
        $MEMBER = new MEMBER(array('person_id' => 5));

        $this->assertEquals('http://' . DOMAIN . '/msp/5/test_current-msp', $MEMBER->url());
    }

    /**
     * Test that edge case URL for Elizabeth II is generated correctly.
     */
    public function testGetElizabethIIURL()
    {
        $MEMBER = new MEMBER(array('person_id' => 1));

        $this->assertEquals('http://' . DOMAIN . '/royal/elizabeth_the_second', $MEMBER->url());
    }

}
