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
     * Test that a member is correctly retrieved by person ID alone.
     */
    public function testGetMPByPersonID()
    {
        $MEMBER = new \MySociety\TheyWorkForYou\Member(array('person_id' => 2));

        $this->assertEquals(1, $MEMBER->member_id);
        $this->assertEquals(2, $MEMBER->person_id);
    }

    /**
     * Test that a member is correctly retrieved by member ID alone.
     */
    public function testGetMPByMemberID()
    {
        $MEMBER = new \MySociety\TheyWorkForYou\Member(array('member_id' => 1));

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

        $MEMBER = new \MySociety\TheyWorkForYou\Member(array('name' => 'test current-mp'));

        $this->assertEquals(1, $MEMBER->member_id);
        $this->assertEquals(2, $MEMBER->person_id);
    }

    /**
     * Test that the current member is correctly retrieved by constituency.
     */
    public function testGetMPByConstituency()
    {
        $MEMBER = new \MySociety\TheyWorkForYou\Member(array('constituency' => 'test westminster constituency'));

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

        $MEMBER = new \MySociety\TheyWorkForYou\Member(array('name' => 'test current-mp', 'constituency' => 'test westminster constituency'));

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

        $MEMBER = new \MySociety\TheyWorkForYou\Member(array('name' => 'test duplicate-mp'));

        #var_dump($MEMBER);

        $this->assertEquals(NULL, $MEMBER->member_id);
        $this->assertEquals(array(11, 10), $MEMBER->person_id);
    }

    /**
     * Test that MP URLs are generated correctly.
     */
    public function testGetMPURL()
    {
        $MEMBER = new \MySociety\TheyWorkForYou\Member(array('person_id' => 2));

        $this->assertEquals('http://' . DOMAIN . '/mp/2/test_current-mp/test_westminster_constituency', $MEMBER->url());
    }

    /**
     * Test that MP URLs with special characters are generated correctly.
     *
     * Special characters in URLs *should* be encoded.
     */
    public function testGetMPSpecialCharacterURL()
    {
        $MEMBER = new \MySociety\TheyWorkForYou\Member(array('person_id' => 12));

        $this->assertEquals('http://' . DOMAIN . '/mp/12/test_special-character-constituency/test_constituency%2C_comma', $MEMBER->url());
    }

    /**
     * Test that Peer URLs are generated correctly.
     */
    public function testGetPeerURL()
    {
        $MEMBER = new \MySociety\TheyWorkForYou\Member(array('person_id' => 3));

        $this->assertEquals('http://' . DOMAIN . '/peer/3/mr_current-lord', $MEMBER->url());
    }

    /**
     * Test that MLA URLs are generated correctly.
     */
    public function testGetMLAURL()
    {
        $MEMBER = new \MySociety\TheyWorkForYou\Member(array('person_id' => 8));

        $this->assertEquals('http://' . DOMAIN . '/mla/8/test_previous-mla', $MEMBER->url());
    }

    /**
     * Test that MSP URLs are generated correctly.
     */
    public function testGetMSPURL()
    {
        $MEMBER = new \MySociety\TheyWorkForYou\Member(array('person_id' => 5));

        $this->assertEquals('http://' . DOMAIN . '/msp/5/test_current-msp', $MEMBER->url());
    }

    /**
     * Test that edge case URL for Elizabeth II is generated correctly.
     */
    public function testGetElizabethIIURL()
    {
        $MEMBER = new \MySociety\TheyWorkForYou\Member(array('person_id' => 1));

        $this->assertEquals('http://' . DOMAIN . '/royal/elizabeth_the_second', $MEMBER->url());
    }

    /**
     * Test that entered/left house strings are being generated
     */
    public function testEnteredLeftHouseString()
    {
        $MEMBER = new \MySociety\TheyWorkForYou\Member(array('person_id' => 9));

        $this->assertEquals(array(
            "<strong>Entered the Scottish Parliament on 1 January 1990</strong> &mdash; General election",
            "<strong>Left the Scottish Parliament on 31 December 1999</strong> &mdash; General election"
        ), $MEMBER->getEnterLeaveStrings());
    }

    /**
     * Test loading extra info
     *
     * @todo Implement testing of the Public Whip info loading
     */
    public function testLoadExtraInfo()
    {
        $MEMBER = new \MySociety\TheyWorkForYou\Member(array('person_id' => 16));

        $MEMBER->load_extra_info();

        // Have we correctly loaded the office?
        $this->assertEquals(1, $MEMBER->extra_info['office'][0]['moffice_id']);

        // Have we correctly loaded the member arbitrary key/value pair?
        $this->assertEquals('Test Member Value', $MEMBER->extra_info['test_member_key']);

        // Have we correctly loaded the person arbitrary key/value pair?
        $this->assertEquals('Test Person Value', $MEMBER->extra_info['test_person_key']);

        // Have we correctly loaded the constituency arbitrary key/value pair?
        $this->assertEquals('Test Constituency Value', $MEMBER->extra_info['test_constituency_key']);

        // Have we correctly loaded the PBC membership?
        $this->assertEquals(array(
            'title' => 'Test Bill',
            'session' => '2013-14',
            'attending' => 1,
            'chairman' => 1,
            'outof' => 0
            ), $MEMBER->extra_info['pbc'][1]);
    }

    /**
     * Test finding a member by Guardian Aristotle ID
     */
    public function testGetByAristotleId()
    {
        $MEMBER = new \MySociety\TheyWorkForYou\Member(array('guardian_aristotle_id' => 123456789));

        $MEMBER->guardian_aristotle_id_to_person_id();

        // Have we correctly loaded the person by Guardian ID?
        $this->assertEquals(16, $MEMBER->person_id);
    }

}
