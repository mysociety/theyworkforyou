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
        return $this->createMySQLXMLDataSet(dirname(__FILE__).'/_fixtures/party.xml');
    }

    private function fetch_page($vars)
    {
        return $this->base_fetch_page($vars, 'mp');
    }

    public function testLoad() {
        $party = new MySociety\TheyWorkForYou\Party('A Party');

        $this->assertNotNull($party);
        $this->assertEquals( 'A Party', $party->name );
    }

    public function testCountMembers() {
        $party = new MySociety\TheyWorkForYou\Party('A Party');
        $this->assertEquals( $party->getCurrentMemberCount(HOUSE_TYPE_COMMONS), 2 );
    }

    public function testGetPolicyPositions() {
        $positions = $this->getAllPositions('getAllPolicyPositions');

        $expectedPositions = array(
            '363' => array(
                'position' => 'almost always voted against',
                'score' => '0.9',
                'desc' => 'introducing <b>foundation hospitals</b>',
                'policy_id' => 363
            )
        );

        $this->assertEquals($expectedPositions, $positions);

    }

    public function testGetPolicyPositionsForIndependents() {
        $positions = $this->getAllPositions('getAllPolicyPositions', 'Independent');
        $this->assertEquals(array(), $positions);
    }

    public function testGetRestrictedPositions() {
        $party = new MySociety\TheyWorkForYou\Party('A Party');
        $policies = new MySociety\TheyWorkForYou\Policies(6667);

        $positions = $party->getAllPolicyPositions($policies);

        $expectedPositions = array();

        $this->assertEquals($expectedPositions, $positions);

    }

    public function testCalculatePositions() {
        $positions = $this->getAllPositions('calculateAllPolicyPositions');

        $expectedResults = array(
            '810' => array(
                'policy_id' => 810,
                'position' => 'voted a mixture of for and against',
                'score' => 0.5,
                'desc' => 'greater <b>regulation of gambling</b>'
            )
        );

        $this->assertEquals($expectedResults, $positions);
    }

    public function testCalculatePositionsPolicyAbsent() {
        $party = new MySociety\TheyWorkForYou\Party('Labour/Co-operative');
        list($desc, $score) = $party->calculate_policy_position(900, true);

        $this->assertEquals(-1, $score);
    }

    public function testLabourCoOp() {
        $party = new MySociety\TheyWorkForYou\Party('Labour/Co-operative');

        $this->assertEquals('Labour', $party->name);
    }

    public function testGetAllParties() {
        $parties = MySociety\TheyWorkForYou\Party::getParties();

        $expected = array('A Party', 'Labour', 'Labour/Co-operative', 'A Second Party');

        $this->assertEquals($expected, $parties);
    }

    public function testLabourCoOpPositionCalc() {
        $positions = $this->getAllPositions('calculateAllPolicyPositions', 'Labour');

        $expectedResults = array(
            '810' => array(
                'policy_id' => 810,
                'position' => 'voted a mixture of for and against',
                'score' => 0.5,
                'desc' => 'greater <b>regulation of gambling</b>'
            )
        );

        $this->assertEquals($expectedResults, $positions);

        $party = new MySociety\TheyWorkForYou\Party('Labour/Co-operative');
        $party->cache_position( $positions['810'] );

        $position = $party->policy_position(810);
        $expected = ('voted a mixture of for and against');

        $this->assertEquals($expected, $position);
    }

    private function getAllPositions($method, $party = 'A Party') {
        $party = new MySociety\TheyWorkForYou\Party($party);
        $policies = new MySociety\TheyWorkForYou\Policies();

        $positions = $party->$method($policies);
        return $positions;
    }

    public function testMPPartyPolicyTextWhenDiffers()
    {
        $page = $this->fetch_page( array( 'pid' => 2, 'url' => '/mp/2/test_current-mp/test_westminster_constituency' ) );
        $this->assertContains('Test Current-MP', $page);
        $this->assertContains('is a A Party MP', $page);
        $this->assertContains('sometimes <b>differs</b> from their party', $page);
    }

    public function testSingleMemberPartyPolicyText()
    {
        $page = $this->fetch_page( array( 'pid' => 7, 'url' => '/mp/7/test_second-party-mp/test_westminster_constituency' ) );
        $this->assertContains('Test Second-Party-MP', $page);
        $this->assertNotContains('is a A Second Party MP', $page);
    }

    public function testMPPartyPolicyWherePartyMissingPositions()
    {
        $page = $this->fetch_page( array( 'pid' => 3, 'url' => '/mp/3/test_current-mp/test_westminster_constituency' ) );
        $this->assertContains('Test Current-MP', $page);
        $this->assertContains('is a A Party MP', $page);
        $this->assertNotContains('while most A Party MPs voted', $page);
    }

    public function testMPPartyPolicyTextWhenAgrees()
    {
        $page = $this->fetch_page( array( 'pid' => 6, 'url' => '/mp/6/test_further-mp/test_westminster_constituency' ) );
        $this->assertContains('Test Further-MP', $page);
        $this->assertContains('This is a selection of Miss Test Further-MP&rsquo;s votes', $page);
    }
}
