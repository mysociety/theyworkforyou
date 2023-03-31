<?php

/**
 * Testing for postcode Utility functions
 */

class PostcodePageTest extends FetchPageTestCase
{

    /**
     * Loads the member testing fixture.
     */
    public function getDataSet()
    {
        return $this->createMySQLXMLDataSet(dirname(__FILE__).'/_fixtures/postcodepage.xml');
    }

    private function fetch_postcode_page($postcode, $options = array()) {
      $args = array('pc' => $postcode);
      $args = array_merge($args, $options);

      return $this->base_fetch_page(
          $args,
          'postcode',
          'index.php',
          '/postcode/'
      );
    }

    public function testNoPostcodeGivesError()
    {
        $page = $this->fetch_postcode_page('');
        $this->assertContains('Please supply a postcode!', $page);
    }

    public function testRedirectsForWestminsterOnly()
    {
        $page = $this->fetch_postcode_page('SW1A 1AA');
        $this->assertContains('Location: /mp/2/test_current-mp/test_westminster_constituency', $page);
    }

    public function testShowsOptionsForScottishPostcode()
    {
        $page = $this->fetch_postcode_page('PH6 2DB');
        $this->assertContains('That postcode has multiple results', $page);
        $this->assertContains('Test2 Current-MP', $page);
        $this->assertContains('Test Current-MSP', $page);
        $this->assertContains('Test Current-Regional-MSP', $page);
    }

    public function testShowsOptionsForNIPostcode()
    {
        $page = $this->fetch_postcode_page('BT1 1AA');
        $this->assertContains('That postcode has multiple results', $page);
        $this->assertContains('Test3 Current-MP', $page);
        $this->assertContains('Test Current-MLA', $page);
    }

    public function testRedirectsWithPolicySet()
    {
        $page = $this->fetch_postcode_page('SW1A 1AA', array('policy_set' => 'social'));
        $this->assertContains('Location: /mp/2/test_current-mp/test_westminster_constituency/votes?policy=social', $page);
    }

    public function testRedirectsWithPolicySetForScottishPostcode()
    {
        $page = $this->fetch_postcode_page('PH6 2DB', array('policy_set' => 'social'));
        $this->assertContains('Location: /mp/3/test2_current-mp/test_scottish_westminster_constituency/votes?policy=social', $page);
    }

    public function testRedirectsWithPolicySetForNIPostcode()
    {
        $page = $this->fetch_postcode_page('BT1 1AA', array('policy_set' => 'social'));
        $this->assertContains('Location: /mp/6/test3_current-mp/test_ni_westminster_constituency/votes?policy=social', $page);
    }
}
