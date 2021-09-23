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


    public function testSameDateRangeSameCohort()
    {
        // Person ID 1 and 2 should have the same cohort
        // They both belong to A party and have the same date ranges

        $member = $this->getMemberFromPersonId(1);
        $cohortkeya = $member->cohortKey();

        $member = $this->getMemberFromPersonId(2);
        $cohortkeyb = $member->cohortKey();
        $this->assertEquals($cohortkeya, $cohortkeyb);
    }

    public function testdifferentDatesDifferentCohorts()
    {
        //A third MP, but with a known absence added, end up in different cohorts. 
        //Person ID 3 has a known absence, and so should be in a different cohort to 1.

        MySociety\TheyWorkForYou\PartyCohort::populateCohorts();
        MySociety\TheyWorkForYou\PartyCohort::calculatePositions();   

        $query = MySociety\TheyWorkForYou\PartyCohort::getCohortQuery();

        $member = $this->getMemberFromPersonId(1);
        $cohortkeya = $member->cohortKey();

        $member = $this->getMemberFromPersonId(3);
        $cohortkeyb = $member->cohortKey();

        $this->assertNotEquals($cohortkeya, $cohortkeyb);
    }

    public function testdifferentPartiesDifferentCohorts()
    {
        //Two MPs of different parties with the same date range end up in different cohorts. 
        //Person ID 1 and person ID 4 should have different cohorts. 

        MySociety\TheyWorkForYou\PartyCohort::populateCohorts();
        MySociety\TheyWorkForYou\PartyCohort::calculatePositions();        

        $member = $this->getMemberFromPersonId(1);
        $cohortkeya = $member->cohortKey();

        $member = $this->getMemberFromPersonId(4);
        $cohortkeyb = $member->cohortKey();
        $this->assertNotEquals($cohortkeya, $cohortkeyb);
    }

    public function testTwoLabourPartiesAreOneParty()
    {
        //A labour and labour/coop mp with the same date ranges end up in the same cohort. 
        //Person ID 5 and 6 have the same cohort. 

        MySociety\TheyWorkForYou\PartyCohort::populateCohorts();
        MySociety\TheyWorkForYou\PartyCohort::calculatePositions();        

        $member = $this->getMemberFromPersonId(5);
        $cohortkeya = $member->cohortKey();

        $member = $this->getMemberFromPersonId(6);
        $cohortkeyb = $member->cohortKey();
        $this->assertEquals($cohortkeya, $cohortkeyb);
    }

    public function testPartyChangerComparison()
    {
        //An MP who changes party should have their first party as their comparison party. 
        // Person id 7, has changed party but should have 'A Party' not 'C Party' as their comparison party.

        MySociety\TheyWorkForYou\PartyCohort::populateCohorts();
        MySociety\TheyWorkForYou\PartyCohort::calculatePositions();        

        $member = $this->getMemberFromPersonId(7);
        $comparison_party = $member->cohortParty();
        $this->assertEquals($comparison_party, "A Party");

    }

    public function testComparisonDateRange()
    {
        //A policy comparison for a policy with divisions that include 1 outside an mps memberships will not include that division. 
        // Person 1 should end up with a policy with a comparison period for policy 1120 starting in 2002 and ending in 2006

        MySociety\TheyWorkForYou\PartyCohort::populateCohorts();
        MySociety\TheyWorkForYou\PartyCohort::calculatePositions();        

        $positions = $this->positionsForPersonID(1);

        $specific_policy = $positions[1120];

        $this->assertEquals($specific_policy["date_min"], "2002-01-01");
        $this->assertEquals($specific_policy["date_max"], "2006-05-01");
    }

    public function testComparisonDateLongerRange()
    {
        //For same policy as testComparisonDateExclusion, someone with a longer range should have a different date_min
        // Person 8 starts from 1995, so should end with a comparison period for policy 1120 in 1998.

        MySociety\TheyWorkForYou\PartyCohort::populateCohorts();
        MySociety\TheyWorkForYou\PartyCohort::calculatePositions();        

        $member = $this->getMemberFromPersonId(1);
        $cohortkeya = $member->cohortKey();

        $member = $this->getMemberFromPersonId(8);
        $cohortkeyb = $member->cohortKey();

        $this->assertNotEquals($cohortkeya, $cohortkeyb);

        $positions = $this->positionsForPersonID(8);

        $specific_policy = $positions['1120'];

        $this->assertEquals($specific_policy["date_min"], "1998-01-01");
        $this->assertEquals($specific_policy["date_max"], "2006-05-01");
    }


    public function testComparisonDateExclusions()
    {
        // A division that falls inside a known absence for an MP will lead to the policy comparison excluding that vote. 

        // Person 3 has a known absence during 2006, and so their cohort will only cover the one division for 1120

        MySociety\TheyWorkForYou\PartyCohort::populateCohorts();
        MySociety\TheyWorkForYou\PartyCohort::calculatePositions();        

        $positions = $this->positionsForPersonID(3);

        $specific_policy = $positions['1120'];

        $this->assertEquals($specific_policy["date_min"], "2002-01-01");
        $this->assertEquals($specific_policy["date_max"], "2002-01-01");
    }

    public function testPartyChangeHistoryRobustness(){
        //The cohort comparison for a set of MPs does not change if one of those MPs changes party *after* the division.
        //Person 10 and 11 are in party D. 
        //Person 12 is in Party E the whole time.
        //Person 13 is in Party E, but switches to party F in 2015. 
        //10 and 12 vote the same way of on the division for policy 1124 in 2014, 11 and 13 vote the opposite way.
        //The cohort position should be the same for the cohorts person 10, 12 and 13 belong to. 
      
        MySociety\TheyWorkForYou\PartyCohort::populateCohorts();
        MySociety\TheyWorkForYou\PartyCohort::calculatePositions();        

        $positionsa = $this->positionsForPersonID(10)[1124];
        $positionsb = $this->positionsForPersonID(12)[1124];
        $positionsc = $this->positionsForPersonID(13)[1124];

        $this->assertCount(0, array_diff($positionsa, $positionsb));
        $this->assertCount(0, array_diff($positionsb, $positionsc));
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
        $this->assertEquals($party->getCurrentMemberCount(HOUSE_TYPE_COMMONS), 4);
    }

    public function testCalcAndGetPolicyPositions()
    {
        # Calculate all party positions
        # For an A Party MP (1), get their cohort's preference

        MySociety\TheyWorkForYou\PartyCohort::populateCohorts();
        MySociety\TheyWorkForYou\PartyCohort::calculatePositions();        

        $positions = $this->positionsForPersonID(1);

        $comparison = array('1113' => $positions['1113'], '810' => $positions['810']);

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

        $this->assertEquals($expectedPositions, $comparison);
    }

    public function testCalcAndGetPolicyPositionsForIndependents()
    {
        # Independent MPs should not have policy positions
        # MP 14 is an independent MP

        MySociety\TheyWorkForYou\PartyCohort::populateCohorts();
        MySociety\TheyWorkForYou\PartyCohort::calculatePositions();     

        $positions = $this->positionsForPersonID(14);

        $this->assertEquals(array(), $positions);
    }

    public function testGetRestrictedPositions()
    {
    # Person 1 is a member of party A. 
    # Party A should have no policies for polices 6667

        MySociety\TheyWorkForYou\PartyCohort::populateCohorts();
        MySociety\TheyWorkForYou\PartyCohort::calculatePositions();     

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
        # The labour/coop party's position on policy 996 was that they
        # were absent (-1)
        # Person 6 is a coop MP

        MySociety\TheyWorkForYou\PartyCohort::populateCohorts();
        MySociety\TheyWorkForYou\PartyCohort::calculatePositions();     

        $data = $this->positionsForPersonID(6);
        
        print_r($data);

        $data = $data['996'];

        $this->assertEquals(-1, $data['score']);
    }



    public function testGetAllParties()
    {

        $parties = MySociety\TheyWorkForYou\Party::getParties();

        $expected = array('A Party', 'B Party', 'C Party', 'D Party', 'E Party', 'F Party', 'G Party', 'Labour', 'Labour/Co-operative');
        $this->assertCount(0, array_diff($expected, $parties));
    }

    private function positionsForPersonID($person_id)
    {
        $member = $this->getMemberFromPersonId($person_id);
        $cohortkey = $member->cohortKey();
        $cohort = new MySociety\TheyWorkForYou\PartyCohort($cohortkey, True);
        $policies = new MySociety\TheyWorkForYou\Policies();

        print_r($policies->getPolicies());
        
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


    public function testMPPartyPolicyTextWhenDiffers()
    {
        // Checks that an MP that differs from party gets the 'sometimes differs from their party' on the profile page
        MySociety\TheyWorkForYou\PartyCohort::populateCohorts();
        MySociety\TheyWorkForYou\PartyCohort::calculatePositions();     
        $page = $this->fetch_page(array('pid' => 15, 'url' => '/mp/15/test_mp_g_party_1/test_westminster_constituency'));
        $this->assertContains('Test MP G Party 1', $page);
        $this->assertContains('is a G Party MP', $page);
        $this->assertContains('sometimes <b>differs</b> from their party', $page);
    }

    public function testSingleMemberPartyPolicyText()
    {
        // this test checks it doesn't say they are an X party MP when they are the only MP of that party
        MySociety\TheyWorkForYou\PartyCohort::populateCohorts();
        MySociety\TheyWorkForYou\PartyCohort::calculatePositions();     
        $page = $this->fetch_page(array('pid' => 7, 'url' => '/mp/7/test_mp_g/test_westminster_constituency'));
        $this->assertContains('Test MP G', $page);
        $this->assertNotContains('is a B Party MP', $page);
    }

    public function testMPPartyPolicyWherePartyMissingPositions()
    {
        // When an MP has votes, but there is no broader party policy to compare it to
        // this goes down a funnel that shows the votes, but does not make the comparison to party.
        MySociety\TheyWorkForYou\PartyCohort::populateCohorts();
        MySociety\TheyWorkForYou\PartyCohort::calculatePositions();     
        $page = $this->fetch_page(array('pid' => 4, 'url' => '/mp/4/test_mp_d/test_westminster_constituency'));
        $this->assertContains('Test MP D', $page);
        $this->assertContains('This is a selection of Mrs Test MP D&rsquo;s votes.', $page);
        $this->assertContains('<li class = "vote-description"', $page);
        $this->assertNotContains('comparable B Party MPs voted', $page);
    }

    public function testMPPartyPolicyTextWhenAgrees()
    {
        // Test when an MP mostly agrees with their party, as MP G Party 2 does with party G
        MySociety\TheyWorkForYou\PartyCohort::populateCohorts();
        MySociety\TheyWorkForYou\PartyCohort::calculatePositions();     
        $page = $this->fetch_page(array('pid' => 16, 'url' => '/mp/16/test_mp_g_party_2/test_westminster_constituency'));
        $this->assertContains('Test MP G Party 2', $page);

        $member = $this->getMemberFromPersonId(16);
        $divisions = new MySociety\TheyWorkForYou\Divisions($member);
        $recent = $divisions->getMemberDivisionsForPolicy(1113);

        $this->assertContains('This is a selection of Mrs Test MP G Party 2&rsquo;s votes', $page);
    }
}
