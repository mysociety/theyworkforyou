<?php
/*
 * SimpleTest tests for the functions in utility.php
 * $Id: utility_test.php,v 1.1 2009-05-06 13:25:29 louise Exp $
 */
include_once dirname(__FILE__) . '/../../../conf/general'; 
include_once '../utility.php';
include_once 'simpletest/unit_tester.php';

class UtilityTest extends UnitTestCase{
  
  function testVerpEnvelopeSenderCanCreateStandardSender(){
    $sender = twfy_verp_envelope_sender('aperson@a.nother.dom');
    $expected_sender = 'twfy+aperson=a.nother.dom@' + EMAILDOMAIN;
    $this->assertEqual($sender, $expected_sender, 'verp_envelope_sender can create a sender for a standard address');
  }
  
}
$test = new UtilityTest();
$test->run(new DefaultReporter);

?>