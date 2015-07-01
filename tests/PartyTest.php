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

    private function getAllPositions($method) {
        $party = new MySociety\TheyWorkForYou\Party('A Party');
        $policies = new MySociety\TheyWorkForYou\Policies();

        $positions = $party->$method($policies);
        return $positions;
    }

}
