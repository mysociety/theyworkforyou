<?php

/**
 * Test Party class
 */
class PartyTest extends TWFY_Database_TestCase
{

    /**
     * Loads the member testing fixture.
     */
    public function getDataSet()
    {
        return $this->createMySQLXMLDataSet(dirname(__FILE__).'/_fixtures/party.xml');
    }

    public function testLoad() {
        $party = new MySociety\TheyWorkForYou\Party('A Party');

        $this->assertNotNull($party);
        $this->assertEquals( 'A Party', $party->name );
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

    public function testLabourCoOp() {
        $party = new MySociety\TheyWorkForYou\Party('Labour/Co-operative');

        $this->assertEquals('Labour', $party->name);
    }

    public function testGetAllParties() {
        $parties = MySociety\TheyWorkForYou\Party::getParties();

        $expected = array('A Party', 'Labour', 'Labour/Co-operative');

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

}
