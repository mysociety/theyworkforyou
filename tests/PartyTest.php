<?php

/**
 * Test Party class
 */
class PartyTest extends FetchPageTestCase
{

    /**
     * Loads the member testing fixture.
     */
    public function getDataSet()
    {
        return $this->createMySQLXMLDataSet(dirname(__FILE__) . '/_fixtures/cohorts.xml');
    }

    private function fetch_page($vars)
    {
        return $this->base_fetch_page($vars, 'mp');
    }

    public function testLoad()
    {
        // Party class name function is working
        $party = new MySociety\TheyWorkForYou\Party('A Party');

        $this->assertNotNull($party);
        $this->assertEquals('A Party', $party->name);
    }

    public function testLabourCoOp()
    {
        // Test Labour/Coop party name is correctly aliased to Labour
        $party = new MySociety\TheyWorkForYou\Party('Labour/Co-operative');

        $this->assertEquals('Labour', $party->name);
    }

    public function testCountMembers()
    {
        // Test the test data contains the correct number of people in party A
        $party = new MySociety\TheyWorkForYou\Party('A Party');
        $this->assertEquals($party->getCurrentMemberCount(HOUSE_TYPE_COMMONS), 3);
    }

    public function testCalcAndGetPolicyPositions()
    {
        # Calculate all party positions
        # For an A Party MP (1), get their cohort's preference

        MySociety\TheyWorkForYou\PartyCohort::populateCohorts();
        MySociety\TheyWorkForYou\PartyCohort::calculatePositions();        

        $positions = $this->positionsForPersonID(1);

        $expectedPositions = array(
            '1113' => array(
                'position' => 'voted a mixture of for and against',
                'score' => '0.5',
                'desc' => 'an <b>equal number of electors</b> per parliamentary constituency',
                'divisions' => 1,
                'date_min' => '2021-02-11',
                'date_max' => '2021-02-11',
                'policy_id' => 1113
            ),
            '810' => array(
                'position' => 'voted a mixture of for and against',
                'score' => '0.5',
                'desc' => 'greater <b>regulation of gambling</b>',
                'divisions' => 1,
                'date_min' => '2021-02-11',
                'date_max' => '2021-02-11',
                'policy_id' => 810
            )
        );

        $this->assertEquals($expectedPositions, $positions);
    }

    public function testCalcAndGetPolicyPositionsForIndependents()
    {
        # Independent MPs should not have policy positions
        # MP 14 is an independent MP
        $positions = $this->positionsForPersonID(14);

        $this->assertEquals(array(), $positions);
    }

    public function testGetRestrictedPositions()
    {
    # Person 1 is a member of party A. 
    # Party A should have no policies for polices 6667

        $member = $this->getMemberFromPersonId(1);
        $cohortkey = $member->cohortKey();
        $cohort = new MySociety\TheyWorkForYou\PartyCohort($cohortkey, True);
        $policies = new MySociety\TheyWorkForYou\Policies(6667);
        $positions = $cohort->getAllPolicyPositions($policies);
        $expectedPositions = array();

        $this->assertEquals($expectedPositions, $positions);
    }

    public function testCalculatePositionsPolicyAbsent()
    {
        # this is currently saying that the labour/coop party's position on policy 90 was that they
        # were absent (-1)

        $party = new MySociety\TheyWorkForYou\Party('Labour/Co-operative');
        $data = $party->calculate_policy_position(900);

        $this->assertEquals(-1, $data['score']);
    }



    public function testGetAllParties()
    {
        // Updated with new set of test data

        $parties = MySociety\TheyWorkForYou\Party::getParties();

        $expected = array('A Party', 'B Party', 'C Party', 'D Party', 'E Party', 'F Party', 'Labour', 'Labour/Co-operative', 'A Second Party');

        $this->assertEquals($expected, $parties);
    }

    public function testLabourCoOpPositionCalc()
    {
        // tbh this should be effectively replaced by the set of cophort tests anyway
        $positions = $this->getAllPositions('calculateAllPolicyPositions', 'Labour');

        $expectedResults = array(
            '810' => array(
                'policy_id' => 810,
                'position' => 'voted a mixture of for and against',
                'score' => 0.5,
                'divisions' => 1,
                'date_min' => '2021-02-11',
                'date_max' => '2021-02-11',
                'desc' => 'greater <b>regulation of gambling</b>'
            ),
            '1113' => array(
                'policy_id' => 1113,
                'position' => 'voted a mixture of for and against',
                'score' => 0.5,
                'divisions' => 1,
                'date_min' => '2021-02-11',
                'date_max' => '2021-02-11',
                'desc' => 'an <b>equal number of electors</b> per parliamentary constituency',
            ),
        );

        # Second test hidden in here that feels like it should be check labour/coop is the same
        # but is only checking one bit of it
        $this->assertEquals($expectedResults, $positions);

        $party = new MySociety\TheyWorkForYou\Party('Labour/Co-operative');
        $party->cache_position($positions['810']);

        $data = $party->policy_position(810);
        $expected = ('voted a mixture of for and against');

        $this->assertEquals($expected, $data['position']);
    }

    private function positionsForPersonID($person_id)
    {
        $member = $this->getMemberFromPersonId($person_id);
        $cohortkey = $member->cohortKey();
        $cohort = new MySociety\TheyWorkForYou\PartyCohort($cohortkey, True);
        $policies = new MySociety\TheyWorkForYou\Policies();
        $positions = $cohort->getAllPolicyPositions($policies);
        return $positions;
    }

    private function getMemberFromPersonId($person_id)
    {
        $db = new \ParlDB;
        $row = $db->query(
            "select member_id from member where person_id = :person_id order by entered_house desc",
            array(":person_id" => $person_id)
        )->first();

        if ($row) {
            return new MySociety\TheyWorkForYou\Member($row);
        } else {
            return NULL;
        }
    }

    private function getAllPositions($method, $party = 'A Party')
    {
        // This is the bit that feels like it needs to change
        $party = new MySociety\TheyWorkForYou\Party($party);
        $policies = new MySociety\TheyWorkForYou\Policies();

        $positions = $party->$method($policies);
        return $positions;
    }

    public function testMPPartyPolicyTextWhenDiffers()
    {
        // Checks that an MP that differs from party gets the 'sometimes differs from their party' on the profile page
        $page = $this->fetch_page(array('pid' => 15, 'url' => '/mp/15/test_mp_g_party_1/test_westminster_constituency'));
        $this->assertContains('Test MP G Party 1', $page);
        $this->assertContains('is a G Party MP', $page);
        $this->assertContains('sometimes <b>differs</b> from their party', $page);
    }

    public function testSingleMemberPartyPolicyText()
    {
        // this test checks it doesn't say they are an X party MP when they are the only MP of that party
        $page = $this->fetch_page(array('pid' => 7, 'url' => '/mp/7/test_mp_g/test_westminster_constituency'));
        $this->assertContains('Test MP G', $page);
        $this->assertNotContains('is a B Party MP', $page);
    }

    public function testMPPartyPolicyWherePartyMissingPositions()
    {
        // this test is when the party does not have positions, it should not have any 'most X MPs voted' messages. 
        // TO DO not really sure how to test this one
        $page = $this->fetch_page(array('pid' => 3, 'url' => '/mp/3/test_current-mp/test_westminster_constituency'));
        $this->assertContains('Test Current-MP', $page);
        $this->assertContains('is a A Party MP', $page);
        $this->assertNotContains('most A Party MPs voted', $page);
    }

    public function testMPPartyPolicyTextWhenAgrees()
    {
        // Test when an MP mostly agrees with their party
        $page = $this->fetch_page(array('pid' => 16, 'url' => '/mp/16/test_mp_g_party_2/test_westminster_constituency'));
        $this->assertContains('Test MP G', $page);
        $this->assertContains('This is a selection of Mrs Test Test MP G&rsquo;s votes', $page);
    }
}
