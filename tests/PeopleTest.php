<?php

/**
 * Provides test methods for people list functionality.
 */
class PeopleTest extends TWFY_Database_TestCase
{

    /**
     * Loads the member testing fixture.
     */
    public function getDataSet()
    {
        return $this->createMySQLXMLDataSet(dirname(__FILE__).'/_fixtures/people.xml');
    }

    /**
     * Test that a member is correctly retrieved by person ID alone.
     */
    public function testGetMPList()
    {
        $people = new MySociety\TheyWorkForYou\People('mps');

        $expectedMP = array(
            'person_id' => '2',
            'given_name' => 'Test',
            'family_name' => 'Current-MP',
            'lordofname' => '',
            'name' => 'Mrs Test Current-MP',
            'url' => '2/mrs_test_current-mp/test_westminster_constituency',
            'constituency' => 'Test Westminster Constituency',
            'party' => 'Lab',
            'left_reason' => 'still_in_office',
            'dept' => null,
            'pos' => null,
            'image' => '/images/unknownperson.png'
        );
        $MPList = $people->getData();

        $this->assertEquals(9, count($MPList['data']));
        $this->assertEquals($expectedMP, $MPList['data'][2]);
    }

    public function testGetMSPList()
    {
        $people = new MySociety\TheyWorkForYou\People('msps');

        $expectedMSP = array(
            'person_id' => '5',
            'given_name' => 'Test',
            'family_name' => 'Current-MSP',
            'lordofname' => '',
            'name' => 'Ms Test Current-MSP',
            'url' => '5/ms_test_current-msp',
            'constituency' => 'Test Scotland Constituency',
            'party' => 'Scottish National Party',
            'left_reason' => 'still_in_office',
            'dept' => null,
            'pos' => null,
            'image' => '/images/unknownperson.png'
        );
        $MSPList = $people->getData();

        $this->assertEquals(4, count($MSPList['data']));
        $this->assertEquals($expectedMSP, $MSPList['data'][5]);
    }

    public function testGetMLAList()
    {
        $people = new MySociety\TheyWorkForYou\People('mlas');

        $expectedMLA = array(
            'person_id' => '4',
            'given_name' => 'Test',
            'family_name' => 'Current-MLA',
            'lordofname' => '',
            'name' => 'Miss Test Current-MLA',
            'url' => '4/miss_test_current-mla',
            'constituency' => 'Test Northern Ireland Constituency',
            'party' => 'SF',
            'left_reason' => 'still_in_office',
            'dept' => null,
            'pos' => null,
            'image' => '/images/unknownperson.png'
        );
        $MLAList = $people->getData();

        $this->assertEquals(4, count($MLAList['data']));
        $this->assertEquals($expectedMLA, $MLAList['data'][4]);
    }

    public function testGetPeerList()
    {
        $people = new MySociety\TheyWorkForYou\People('peers');

        $expectedPeer = array(
            'person_id' => '3',
            'given_name' => 'Test',
            'family_name' => 'Current-Lord',
            'lordofname' => '',
            'name' => 'Mr Current-Lord',
            'url' => '3/mr_current-lord',
            'constituency' => '',
            'party' => 'XB',
            'left_reason' => 'still_in_office',
            'dept' => null,
            'pos' => null,
            'image' => '/images/unknownperson.png'
        );
        $PeerList = $people->getData();

        $this->assertEquals(2, count($PeerList['data']));
        $this->assertEquals($expectedPeer, $PeerList['data'][3]);
    }

    public function getOldMPList() {
        $people = new MySociety\TheyWorkForYou\People('mps');

        $MPList = $people->getData(array('date' => '1995-01-01'));

        $this->assertEquals(2, count($MPList['data']));
    }

    public function getAllMPList() {
        $people = new MySociety\TheyWorkForYou\People('mps');

        $MPList = $people->getData(array('all' => 1));

        $this->assertEquals(11, count($MPList['data']));
    }
}
