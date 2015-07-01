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

    public function testCalculatePositions() {
        $party = new MySociety\TheyWorkForYou\Party('A Party');
        $policies = new MySociety\TheyWorkForYou\Policies();

        $positions = $party->calculateAllPolicyPositions($policies);

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

}
