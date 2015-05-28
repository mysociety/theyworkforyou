<?php

/**
 * Provides test methods for alerts functionality.
 */
class AlertsTest extends TWFY_Database_TestCase
{

    /**
     * Loads the alerts testing fixture.
     */
    public function getDataSet()
    {
        return $this->createMySQLXMLDataSet(dirname(__FILE__).'/_fixtures/alerts.xml');
    }

    /**
     * Ensures the database is prepared and the alert class is included for every test.
     */
    public function setUp()
    {
        parent::setUp();

        include_once('www/includes/easyparliament/alert.php');
    }

    /**
     * Test that unconfirmed, undeleted tests are correctly retrieved
     */
    public function testFetchUnconfirmedUndeleted()
    {
        $ALERT = new ALERT();

        $response = $ALERT->fetch(0,0);

        // Make sure we only get one response
        $this->assertEquals(1, count($response['data']));

        // Make sure the response has the correct attributes
        $this->assertEquals(0, $response['data'][0]['confirmed']);
        $this->assertEquals(0, $response['data'][0]['deleted']);
    }

    /**
     * Test that confirmed, undeleted tests are correctly retrieved
     */
    public function testFetchConfirmedUndeleted()
    {
        $ALERT = new ALERT();

        $response = $ALERT->fetch(1,0);

        // Make sure we only get two responses
        $this->assertEquals(2, count($response['data']));

        // Make sure a response has the correct attributes
        $this->assertEquals(1, $response['data'][0]['confirmed']);
        $this->assertEquals(0, $response['data'][0]['deleted']);
    }

    /**
     * Test that unconfirmed, deleted tests are correctly retrieved
     */
    public function testFetchUnconfirmedDeleted()
    {
        $ALERT = new ALERT();

        $response = $ALERT->fetch(0,1);

        // Make sure we only get one response
        $this->assertEquals(1, count($response['data']));

        // Make sure the response has the correct attributes
        $this->assertEquals(0, $response['data'][0]['confirmed']);
        $this->assertEquals(1, $response['data'][0]['deleted']);
    }

    /**
     * Test that confirmed, deleted tests are correctly retrieved
     */
    public function testFetchConfirmedDeleted()
    {
        $ALERT = new ALERT();

        $response = $ALERT->fetch(1,1);

        // Make sure we only get one response
        $this->assertEquals(1, count($response['data']));

        // Make sure the response has the correct attributes
        $this->assertEquals(1, $response['data'][0]['confirmed']);
        $this->assertEquals(1, $response['data'][0]['deleted']);
    }

    /**
     * Test that the correct alerts between given dates are retrieved
     */
    public function testFetchBetween()
    {
        $ALERT = new ALERT();

        $response = $ALERT->fetch_between(1,1, '2014-02-01', '2014-02-02');

        // Make sure we only get one response
        $this->assertEquals(1, count($response['alerts']));
    }

    /**
     * Test that alerts can be added
     */
    public function testAdd()
    {
        $ALERT = new ALERT();

        $details = array(
            'email' => 'test@theyworkforyou.com',
            'keyword' => 'test',
            'pc' => 'SW1A 1AA'
        );

        $response = $ALERT->add($details, false, true);

        // We *should* get a return of 1
        $this->assertEquals(1, $response);

        // There is no way to get the last insert ID from the response itself.
        // Currently we trust that add() can spot its own errors.
        // TODO: Refactor add() so that component parts are more testable.
    }

    /**
     * Test that adding an already existing alert works as expected
     */
    public function testAddExisting()
    {
        $ALERT = new ALERT();

        $details = array(
            'email' => 'test3@theyworkforyou.com',
            'keyword' => 'test3',
            'pc' => 'SW1A 1AA'
        );

        $response = $ALERT->add($details, false, true);

        // We *should* get a return of -2
        $this->assertEquals(-2, $response);

        // There is no way to get the last insert ID from the response itself.
        // Currently we trust that add() can spot its own errors.
    }

    /**
     * Test that adding an already deleted alert works as expected
     */
    public function testAddDeleted()
    {
        $ALERT = new ALERT();

        $details = array(
            'email' => 'test4@theyworkforyou.com',
            'keyword' => 'test4',
            'pc' => 'SW1A 1AA'
        );

        $response = $ALERT->add($details, false, true);

        // We *should* get a return of 1
        $this->assertEquals(1, $response);

        // There is no way to get the last insert ID from the response itself.
        // Currently we trust that add() can spot its own errors.
    }

    /**
     * Test that a correct token will pass
     */
    public function testCheckTokenCorrect()
    {
        $ALERT = new ALERT();

        $response = $ALERT->check_token('1::token1');

        $this->assertEquals(array(
                'id' => 1,
                'email' => 'test@theyworkforyou.com',
                'criteria' => 'test1',
            ), $response);
    }

    /**
     * Test that an incorrect token (wrong token for the alert ID) will fail
     */
    public function testCheckTokenIncorrectToken()
    {
        $ALERT = new ALERT();

        $response = $ALERT->check_token('1::token2');

        $this->assertEquals(false, $response);
    }

    /**
     * Test that an incorrect token (wrong parts count) will fail
     */
    public function testCheckTokenWrongPartsCount()
    {
        $ALERT = new ALERT();

        $response = $ALERT->check_token('foo');

        $this->assertEquals(false, $response);
    }

    /**
     * Test that an incorrect token (non-numeric alert ID) will fail
     */
    public function testCheckTokenNonNumericId()
    {
        $ALERT = new ALERT();

        $response = $ALERT->check_token('one:token1');

        $this->assertEquals(false, $response);
    }

    /**
     * Test that fetching alerts for an MP succeeds
     */
    public function testCheckFetchByMpExists()
    {
        $ALERT = new ALERT();

        $response = $ALERT->fetch_by_mp('test5@theyworkforyou.com', 1234);

        $this->assertEquals(true, $response);
    }

    /**
     * Test that fetching alerts for an MP which doesn't exist fails
     */
    public function testCheckFetchByMpNotExists()
    {
        $ALERT = new ALERT();

        $response = $ALERT->fetch_by_mp('test5@theyworkforyou.com', 9876);

        $this->assertEquals(false, $response);
    }

    /**
     * Test that confirming an alert with a valid token succeeds
     */
    public function testConfirm()
    {
        $ALERT = new ALERT();

        $response = $ALERT->confirm('1::token1');

        $this->assertEquals(true, $response);

        // TODO: Check that this really does delete the right alert as expected
    }

    /**
     * Test that confirming an alert with an invalid token succeeds
     */
    public function testConfirmInvalid()
    {
        $ALERT = new ALERT();

        $response = $ALERT->confirm('1::badtoken');

        $this->assertEquals(false, $response);
    }

    /**
     * Test that we can delete an alert
     */
    public function testDelete()
    {
        $ALERT = new ALERT();

        $response = $ALERT->delete('1::token1');

        $this->assertEquals(true, $response);

        // TODO: Check that this really does delete the right alert as expected
    }

    /**
     * Test that we can't delete an alert with a bad token
     */
    public function testDeleteInvalid()
    {
        $ALERT = new ALERT();

        $response = $ALERT->delete('1::badtoken');

        $this->assertEquals(false, $response);
    }

    /**
     * Test that we can suspend an alert
     */
    public function testSuspend()
    {
        $ALERT = new ALERT();

        $response = $ALERT->suspend('3::token3');

        $this->assertEquals(true, $response);

        // TODO: Check that this really does suspend the right alert as expected
    }

    /**
     * Test that we can't suspend an alert with a bad token
     */
    public function testSuspendInvalid()
    {
        $ALERT = new ALERT();

        $response = $ALERT->suspend('3::badtoken');

        $this->assertEquals(false, $response);
    }

    /**
     * Test that we can resume an alert
     */
    public function testResume()
    {
        $ALERT = new ALERT();

        $response = $ALERT->resume('6::token6');

        $this->assertEquals(true, $response);

        // TODO: Check that this really does resume the right alert as expected
    }

    /**
     * Test that we can't delete an alert with a bad token
     */
    public function testResumeInvalid()
    {
        $ALERT = new ALERT();

        $response = $ALERT->resume('6::badtoken');

        $this->assertEquals(false, $response);
    }

}
