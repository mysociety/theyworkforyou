<?php

/**
 * Test Party class
 */
class PartyTest extends FetchPageTestCase {
    /**
     * Loads the member testing fixture.
     */
    public function getDataSet() {
        return $this->createMySQLXMLDataSet(dirname(__FILE__) . '/_fixtures/cohorts.xml');
    }

    private function fetch_page($vars) {
        return $this->base_fetch_page($vars, 'mp');
    }


    public function testLoad() {
        // Party class name function is working
        $party = new MySociety\TheyWorkForYou\Party('A Party');

        $this->assertNotNull($party);
        $this->assertEquals('A Party', $party->name);
    }


    public function testTwoLabourPartiesAreOneParty() {
        //A labour and labour/coop mp end up in the same cohort.
        //Person ID 5 and 6 have the same cohort.

        $member = $this->getMemberFromPersonId(5);
        $cohortkeya = $member->cohortParty();

        $member = $this->getMemberFromPersonId(6);
        $cohortkeyb = $member->cohortParty();
        $this->assertEquals($cohortkeya, $cohortkeyb);
    }

    public function testPartyChangerComparison() {
        //An MP who changes party should have their first party as their comparison party.
        // Person id 7, has changed party but should have 'A Party' not 'C Party' as their comparison party.

        $member = $this->getMemberFromPersonId(7);
        $comparison_party = $member->cohortParty();
        $this->assertEquals($comparison_party, "a-party");

    }

    public function testManualPartyChangerComparison() {
        // Person 10172 has changed party but has a manual override so should use their last party

        $member = $this->getMemberFromPersonId(10172);
        $comparison_party = $member->cohortParty();
        $this->assertEquals($comparison_party, "i-party");

    }

    public function testSpeakerPartyChangerComparison() {
        // Person 25 is the speaker, and so doesn't get their former party comparison.

        $member = $this->getMemberFromPersonId(25);
        $comparison_party = $member->cohortParty();
        $this->assertEquals($comparison_party, "speaker");

    }

    public function testLabourCoOp() {
        // Test Labour/Coop party name is correctly aliased to Labour
        $party = new MySociety\TheyWorkForYou\Party('Labour/Co-operative');

        $this->assertEquals('Labour', $party->name);
    }

    public function testCountMembers() {
        // Test the test data contains the correct number of people in party A
        $party = new MySociety\TheyWorkForYou\Party('A Party');
        $this->assertEquals($party->getCurrentMemberCount(HOUSE_TYPE_COMMONS), 4);
    }

    public function testCalculatePositionsPolicyAgree() {
        # The labour/coop party's position on policy 996 was that they
        # were agree (0 distance)
        # Person 6 is a coop MP - we want to test they correctly get compared to labour

        $person_id = 6;
        $voting_comparison_period_slug = "all_time";
        $member = $this->getMemberFromPersonId($person_id);
        $sets = ['reform'];

        $collections = MySociety\TheyWorkForYou\PolicyDistributionCollection::getPersonDistributions($sets, $member->person_id(), $member->cohortParty(), $voting_comparison_period_slug, HOUSE_TYPE_COMMONS);

        $reform = $collections[0];

        $pair = $reform->getPairfromPolicyID(996);
        $comparison_score = $pair->comparison_distribution->distance_score;

        $this->assertEquals(0, $comparison_score);
    }

    public function testGetAllParties() {

        $parties = MySociety\TheyWorkForYou\Party::getParties();

        $expected = ['A Party', 'B Party', 'C Party', 'D Party', 'E Party', 'F Party', 'G Party', 'Labour', 'Labour/Co-operative'];
        $this->assertCount(0, array_diff($expected, $parties));
    }


    private function getMemberFromPersonId($person_id) {
        return new MySociety\TheyWorkForYou\Member([ "person_id" => $person_id ]);
    }

    public function testMPPartyPolicyTextWhenDiffersVotes() {
        // Checks that an MP that differs from party gets the 'sometimes differs from their party' on the profile page

        // for this one we need some mock data on how person 15 sometimes differs from their party

        $page = $this->fetch_page(['pagetype' => 'votes', 'pid' => 15, 'url' => '/mp/15/test_mp_g_party_1/test_westminster_constituency/votes']);
        $this->assertStringContainsString('is a G Party MP', $page);
        $this->assertStringContainsString('Test MP G Party 1', $page);
        $this->assertStringContainsString('sometimes differs from their party', $page);
    }

    public function testSingleMemberPartyPolicyText() {
        // this test checks it doesn't say they are an X party MP when they are the only MP of that party

        $page = $this->fetch_page(['pid' => 7, 'url' => '/mp/7/test_mp_g/test_westminster_constituency']);
        $this->assertStringContainsString('Test MP G', $page);
        $this->assertStringNotContainsString('is a B Party MP', $page);
    }

    public function testMPPartyPolicyWherePartyMissingPositions() {
        // When an MP has votes, but there is no broader party policy to compare it to
        // this goes down a funnel that shows the votes, but does not make the comparison to party.

        // this is a single person party i think - so no party comparison?
        // need to check what votes would actually produce

        $page = $this->fetch_page(['pid' => 4, 'pagetype' => 'votes','url' => '/mp/4/test_mp_d/test_westminster_constituency/votes']);
        $this->assertStringContainsString('Test MP D', $page);
        $this->assertStringContainsString('<li class="vote-description"', $page);
        $this->assertStringNotContainsString('comparable B Party MPs voted', $page);
    }

    public function testMPPartyPolicyTextWhenAgrees() {
        // Test when an MP mostly agrees with their party, as MP G Party 2 does with party G

        // this is just a boring, person aligned with party example

        $page = $this->fetch_page(['pagetype' => 'votes', 'pid' => 16,  'url' => '/mp/16/test_mp_g_party_2/test_westminster_constituency/votes']);
        $this->assertStringContainsString('Test MP G Party 2', $page);

        $this->assertStringNotContainsString('sometimes differs from their party colleagues', $page);
    }


    public function testCrossPartyDisclaimer() {
        // Test if the cross party disclaimer is there

        // this should still work fine
        $page = $this->fetch_page(['pagetype' => 'votes', 'pid' => 7, 'url' => '/mp/7/test_mp_g/test_westminster_constituency/votes']);
        $this->assertStringContainsString('Test MP G', $page);
        $this->assertStringContainsString('In the votes below they are compared to their original party', $page);
    }
}
