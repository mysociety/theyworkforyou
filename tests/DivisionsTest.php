<?php

include_once 'FetchPageTestCase.php';

/**
 * Provides test methods to ensure pages contain what we want them to in various ways.
 */
class DivisionsTest extends FetchPageTestCase
{

    /**
     * Connects to the testing database.
     */
    public function getConnection()
    {
        $dsn = 'mysql:host=' . OPTION_TWFY_DB_HOST . ' ;dbname=' . OPTION_TWFY_DB_NAME;
        $username = OPTION_TWFY_DB_USER;
        $password = OPTION_TWFY_DB_PASS;
        $pdo = new PDO($dsn, $username, $password);
        return $this->createDefaultDBConnection($pdo, OPTION_TWFY_DB_NAME);
    }

    public function getDataSet()
    {
        return $this->createMySQLXMLDataSet(dirname(__FILE__) . '/_fixtures/divisions.xml');
    }

    private function fetch_page($vars)
    {
        return $this->base_fetch_page('', $vars, 'www/docs/mp', 'index.php', 'REQUEST_URI=mp/divisions.php');
    }

    private function fetch_division_page() {
        return $this->fetch_page( array( 'pagetype' => 'divisions', 'pid' => 2, 'policy' => 363, 'url' => '/mp/2/test_current-mp/test_westminster_constituency/divisions' ) );
    }

    public function testSinglePolicy() {
        $p = new MySociety\TheyWorkForYou\Policies;
        $this->assertEquals(count($p->getArray()), 74);

        $p = $p->limitToSet('health');
        $this->assertEquals(count($p->getArray()), 4);

        $p = new MySociety\TheyWorkForYou\Policies;
        $p = $p->limitToSet('education');
        $this->assertEquals(count($p->getArray()), 5);

        $p = new MySociety\TheyWorkForYou\Policies(363);
        $this->assertEquals(count($p->getArray()), 1);

        $p = $p->limitToSet('health');
        $this->assertEquals(count($p->getArray()), 1);

        $p = new MySociety\TheyWorkForYou\Policies(363);
        $p = $p->limitToSet('education');
        $this->assertEquals(count($p->getArray()), 0);

    }

    public function testVoteDirection() {
        $page = $this->fetch_division_page();
        $this->assertContains('Test Current-MP voted Agreed', $page);
    }

    public function testPolicyDirection() {
        $page = $this->fetch_division_page();
        $this->assertContains('voted strongly against introducing <b>foundation', $page);
    }

    public function testVotedAgainst() {
        $page = $this->fetch_division_page();
        $this->assertContains('Test Current-MP voted Do not agree', $page);
    }

    public function testVotedAbsent() {
        $page = $this->fetch_division_page();
        $this->assertContains('Test Current-MP was absent for a vote on Absent Division Title', $page);
    }

    public function testVotedAbstain() {
        $page = $this->fetch_division_page();
        $this->assertContains('Test Current-MP abstained on a vote on Abstained Division Title', $page);
    }

    public function testVotedYesWithYesSentence() {
        $page = $this->fetch_division_page();
        $this->assertContains('Test Current-MP voted yes on Yes Division Title', $page);
    }

    public function testVotedYesWithNoSentence() {
        $page = $this->fetch_division_page();
        $this->assertContains('Test Current-MP voted no on No Division Title', $page);
    }

    public function testStrongIndicators() {
        $page = $this->fetch_division_page();
        $this->assertContains('<li id="pw-2013-01-01-3-commons" class="policy-vote--minor', $page);
        $this->assertContains('<li id="pw-2013-01-01-4-commons" class="policy-vote--major', $page);
        $this->assertContains('<li id="pw-2013-01-01-5-commons" class="policy-vote--major', $page);
        $this->assertContains('<li id="pw-2013-01-01-6-commons" class="policy-vote--minor', $page);
    }

    public function testWeakCount() {
        $page = $this->fetch_division_page();
        $this->assertContains('including 3 less important votes', $page);
    }
}
