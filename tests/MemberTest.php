<?php

/**
 * Provides test methods for member functionality.
 */
class MemberTest extends TWFY_Database_TestCase {
    /**
     * Loads the member testing fixture.
     */
    public function getDataSet() {
        return $this->createMySQLXMLDataSet(dirname(__FILE__) . '/_fixtures/member.xml');
    }

    /**
     * Ensures the database is prepared and the member class is included for every test.
     */
    public function setUp(): void {
        parent::setUp();

        include_once('www/includes/easyparliament/member.php');
    }

    /**
     * Test that a member is correctly retrieved by person ID alone.
     */
    public function testGetMPByPersonID() {
        $MEMBER = new MEMBER(['person_id' => 2]);

        $this->assertEquals(1, $MEMBER->member_id);
        $this->assertEquals(2, $MEMBER->person_id);
    }

    /**
     * Test that a member is correctly retrieved by member ID alone.
     */
    public function testGetMPByMemberID() {
        $MEMBER = new MEMBER(['member_id' => 1]);

        $this->assertEquals(1, $MEMBER->member_id);
        $this->assertEquals(2, $MEMBER->person_id);
    }

    /**
     * Test that a member is correctly retrieved by name.
     */
    public function testGetMPByName() {
        global $this_page;
        $this_page = 'mp';

        $MEMBER = new MEMBER(['name' => 'test current-mp']);

        $this->assertEquals(1, $MEMBER->member_id);
        $this->assertEquals(2, $MEMBER->person_id);
    }

    /**
     * Test that the current member is correctly retrieved by constituency.
     */
    public function testGetMPByConstituency() {
        $MEMBER = new MEMBER(['constituency' => 'test westminster constituency']);

        $this->assertEquals(1, $MEMBER->member_id);
        $this->assertEquals(2, $MEMBER->person_id);
    }

    /**
     * Test that a member is correctly retrieved by name and constituency.
     */
    public function testGetMPByNameAndConstituency() {
        global $this_page;
        $this_page = 'mp';

        $MEMBER = new MEMBER(['name' => 'test current-mp', 'constituency' => 'test westminster constituency']);

        $this->assertEquals(1, $MEMBER->member_id);
        $this->assertEquals(2, $MEMBER->person_id);
    }

    /**
     * Test that members with duplicate names are retrieved as expected.
     */
    public function testGetDuplicateMPsByName() {
        global $this_page;
        $this_page = 'mp';

        try {
            $MEMBER = new MEMBER(['name' => 'test duplicate-mp']);
        } catch (MySociety\TheyWorkForYou\MemberMultipleException $e) {
            $this->assertEquals([11 => 'Test Westminster Constituency',
                10 => 'Test Westminster Constituency'], $e->ids);
            return;
        }
        $this->fail('Multiple member exception not raised');
    }

    /**
     * Test that MP URLs are generated correctly.
     */
    public function testGetMPURL() {
        $MEMBER = new MEMBER(['person_id' => 2]);

        $this->assertEquals('/mp/2/test_current-mp/test_westminster_constituency', $MEMBER->url());
    }

    /**
     * Test that MP URLs with special characters are generated correctly.
     *
     * Special characters in URLs *should* be encoded.
     */
    public function testGetMPSpecialCharacterURL() {
        $MEMBER = new MEMBER(['person_id' => 12]);

        $this->assertEquals('/mp/12/test_special-character-constituency/test_constituency%2C_comma', $MEMBER->url());
    }

    /**
     * Test that Peer URLs are generated correctly.
     */
    public function testGetPeerURL() {
        $MEMBER = new MEMBER(['person_id' => 3]);

        $this->assertEquals('/peer/3/mr_current-lord', $MEMBER->url());
    }

    /**
     * Test that MLA URLs are generated correctly.
     */
    public function testGetMLAURL() {
        $MEMBER = new MEMBER(['person_id' => 8]);

        $this->assertEquals('/mla/8/test_previous-mla', $MEMBER->url());
    }

    /**
     * Test that MSP URLs are generated correctly.
     */
    public function testGetMSPURL() {
        $MEMBER = new MEMBER(['person_id' => 5]);

        $this->assertEquals('/msp/5/test_current-msp', $MEMBER->url());
    }

    /**
     * Test that edge case URL for Elizabeth II is generated correctly.
     */
    public function testGetElizabethIIURL() {
        $MEMBER = new MEMBER(['person_id' => 13935]);

        $this->assertEquals('/royal/elizabeth_the_second', $MEMBER->url());
    }

    /**
     * Test that entered/left house strings are being generated
     */
    public function testEnteredLeftHouseString() {
        $MEMBER = new MySociety\TheyWorkForYou\Member(['person_id' => 9]);

        $this->assertEquals([
            "<strong>Entered the Scottish Parliament on  1 January 1990</strong> &mdash; General election",
            "<strong>Left the Scottish Parliament on 31 December 1999</strong> &mdash; General election",
        ], $MEMBER->getEnterLeaveStrings());
    }

    /**
     * Test loading extra info
     *
     * @todo Implement testing of the Public Whip info loading
     */
    public function testLoadExtraInfo() {
        $MEMBER = new MEMBER(['person_id' => 16]);

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
        $this->assertEquals([
            'title' => 'Test Bill',
            'session' => '2013-14',
            'attending' => 1,
            'chairman' => 1,
            'outof' => 0,
        ], $MEMBER->extra_info['pbc'][1]);
    }

    /**
     * Test to ensure Utility\Member::findMemberImage() works as expected
     */
    public function testFindMemberImage() {

        // A missing image with no backup should reply null/null
        $this->assertEquals(
            [null, null],
            \MySociety\TheyWorkForYou\Utility\Member::findMemberImage(1)
        );

        // Missing, small, use default
        $this->assertEquals(
            ['/images/unknownperson_large.png', 'L'],
            \MySociety\TheyWorkForYou\Utility\Member::findMemberImage(1, false, true)
        );

        // Missing, large, use default Lord
        $this->assertEquals(
            ['/images/unknownlord_large.png', 'L'],
            \MySociety\TheyWorkForYou\Utility\Member::findMemberImage(1, false, 'lord')
        );

        // Missing, small, use default
        $this->assertEquals(
            ['/images/unknownperson.png', 'S'],
            \MySociety\TheyWorkForYou\Utility\Member::findMemberImage(1, true, true)
        );

        // Missing, small, use default Lord
        $this->assertEquals(
            ['/images/unknownlord.png', 'S'],
            \MySociety\TheyWorkForYou\Utility\Member::findMemberImage(1, true, 'lord')
        );

        // Does a large JPEG work?
        $this->assertEquals(
            ['/images/mpsL/11132.jpeg', 'L'],
            \MySociety\TheyWorkForYou\Utility\Member::findMemberImage(11132)
        );

        // Does a small JPEG work?
        $this->assertEquals(
            ['/images/mps/10001.jpeg', 'S'],
            \MySociety\TheyWorkForYou\Utility\Member::findMemberImage(10001, true)
        );

        // Does a large PNG work? No large PNGs
        $this->assertEquals(
            ['/images/mps/13943.png', 'S'],
            \MySociety\TheyWorkForYou\Utility\Member::findMemberImage(13943)
        );

        // Does a small PNG work?
        $this->assertEquals(
            ['/images/mps/13943.png', 'S'],
            \MySociety\TheyWorkForYou\Utility\Member::findMemberImage(13943, true)
        );

        // Does a large JPG work?
        $this->assertEquals(
            ['/images/mpsL/10001.jpg', 'L'],
            \MySociety\TheyWorkForYou\Utility\Member::findMemberImage(10001)
        );

        // Does a small JPG work?
        $this->assertEquals(
            ['/images/mps/10552.jpg', 'S'],
            \MySociety\TheyWorkForYou\Utility\Member::findMemberImage(10552, true)
        );

        // If we request one we know we have, but also say use a substitute?
        $this->assertEquals(
            ['/images/mps/10001.jpeg', 'S'],
            \MySociety\TheyWorkForYou\Utility\Member::findMemberImage(10001, true, true)
        );

        // If we only have a small, but don't request explicitly?
        $this->assertEquals(
            ['/images/mps/28619.jpg', 'S'],
            \MySociety\TheyWorkForYou\Utility\Member::findMemberImage(28619)
        );

    }

    public function testIsNew() {
        $MEMBER = new MySociety\TheyWorkForYou\Member(['person_id' => 17]);

        $this->assertNotTrue($MEMBER->isNew());

        self::$db->query("UPDATE member SET entered_house = NOW() WHERE person_id = 17");

        // do this to force a reload
        $MEMBER = new MySociety\TheyWorkForYou\Member(['person_id' => 17]);
        $this->assertTrue($MEMBER->isNew());
    }

    public function testGetEntryDate() {
        $MEMBER = new MySociety\TheyWorkForYou\Member(['person_id' => 18]);

        $this->assertEquals($MEMBER->getEntryDate(), '2014-05-01');
        $this->assertEquals($MEMBER->getEntryDate(1), '2014-05-01');
        $this->assertEquals($MEMBER->getEntryDate(2), '2010-05-01');
        $this->assertEquals($MEMBER->getEntryDate(3), '2012-05-01');
        $this->assertEquals($MEMBER->getEntryDate(4), '2004-05-01');
        $this->assertEquals($MEMBER->getEntryDate(5), '');
    }

    public function testGetRegionalList() {
        $this->assertEquals([], \MySociety\TheyWorkForYou\Member::getRegionalList('', '', ''));

        $msps = [
            [
                'person_id' => "19",
                'name' => "Mr Regional MSP1",
                'constituency' => "Mid Scotland and Fife",
                'house' => "4",
            ],
            [
                'person_id' => "20",
                'name' => "Mr Regional MSP2",
                'constituency' => "Mid Scotland and Fife",
                'house' => "4",
            ],
        ];
        $this->assertEquals($msps, \MySociety\TheyWorkForYou\Member::getRegionalList('KY16 8YG', 4, 'SPE'));
        $mlas = [
            [
                'person_id' => "21",
                'name' => "Mr Regional MLA1",
                'constituency' => "Belfast West",
                'house' => "3",
            ],
            [
                'person_id' => "22",
                'name' => "Mr Regional MLA2",
                'constituency' => "Belfast West",
                'house' => "3",
            ],
        ];
        $this->assertEquals($mlas, \MySociety\TheyWorkForYou\Member::getRegionalList('BT17 0XD', 3, 'NIE'));
        $this->assertEquals([], \MySociety\TheyWorkForYou\Member::getRegionalList('BT17 0XD', 4, 'NIE'));
        $this->assertEquals([], \MySociety\TheyWorkForYou\Member::getRegionalList('BT17 0XD', 3, 'SPE'));
        $this->assertEquals([], \MySociety\TheyWorkForYou\Member::getRegionalList('KY16 8YG', 3, 'SPE'));
        $this->assertEquals([], \MySociety\TheyWorkForYou\Member::getRegionalList('KY16 8YG', 4, 'NIE'));
        $this->assertEquals([], \MySociety\TheyWorkForYou\Member::getRegionalList('KY16 8YG', 4, ''));
        $this->assertEquals([], \MySociety\TheyWorkForYou\Member::getRegionalList('KY16 8YG', '', ''));
    }

}
