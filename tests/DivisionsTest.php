<?php

/**
 * Provides test methods to ensure pages contain what we want them to in various ways.
 */
class DivisionsTest extends FetchPageTestCase
{

    public function getDataSet()
    {
        return $this->createMySQLXMLDataSet(dirname(__FILE__) . '/_fixtures/divisions.xml');
    }

    private function fetch_page($vars)
    {
        return $this->base_fetch_page($vars, 'mp', 'index.php', '/mp/divisions.php');
    }

    private function fetch_division_page() {
        return $this->fetch_page( array( 'pagetype' => 'divisions', 'pid' => 2, 'policy' => 363, 'url' => '/mp/2/test_current-mp/test_westminster_constituency/divisions' ) );
    }

    private function fetch_mp_recent_page()
    {
        $vars = array( 'pagetype' => 'recent', 'pid' => 2, 'url' => '/mp/2/test_current-mp/test_westminster_constituency/recent' );
        return $this->base_fetch_page($vars, 'mp', 'index.php', '/mp/recent.php');
    }

    private function fetch_recent_page() {
        return $this->base_fetch_page( array('url' => '/divisions' ), 'divisions', 'index.php', '/divisions/index.php' );
    }


    public function testSinglePolicy() {
        $p = new MySociety\TheyWorkForYou\Policies;
        $this->assertEquals(count($p->getPoliciesData()), 92);

        $p = $p->limitToSet('health');
        $this->assertEquals(count($p->getPoliciesData()), 5);

        $p = new MySociety\TheyWorkForYou\Policies;
        $p = $p->limitToSet('education');
        $this->assertEquals(count($p->getPoliciesData()), 5);

        $p = new MySociety\TheyWorkForYou\Policies(363);
        $this->assertEquals(count($p->getPoliciesData()), 1);

        $p = $p->limitToSet('health');
        $this->assertEquals(count($p->getPoliciesData()), 1);

        $p = new MySociety\TheyWorkForYou\Policies(363);
        $p = $p->limitToSet('education');
        $this->assertEquals(count($p->getPoliciesData()), 0);

    }

    public function testVoteDirection() {
        $page = $this->fetch_division_page();
        $this->assertStringContainsString('Test Current-MP voted Agreed', $page);
    }

    public function testPolicyDirection() {
        $page = $this->fetch_division_page();
        $this->assertStringContainsString('almost always voted against introducing <b>foundation', $page);
    }

    public function testVotedAgainst() {
        $page = $this->fetch_division_page();
        $this->assertStringContainsString('Test Current-MP voted Do not agree', $page);
    }

    public function testVotedAbsent() {
        $page = $this->fetch_division_page();
        $this->assertStringContainsString('Test Current-MP was absent for a vote on <em>Absent Division Title</em>', $page);
    }

    public function testVotedAbstain() {
        $page = $this->fetch_division_page();
        $this->assertStringContainsString('Test Current-MP abstained on a vote on <em>Abstained Division Title</em>', $page);
    }

    public function testVotedYesWithYesSentence() {
        $page = $this->fetch_division_page();
        $this->assertStringContainsString('Test Current-MP voted yes on <em>Yes Division Title</em>', $page);
    }

    public function testVotedNoWithNoSentence() {
        $page = $this->fetch_division_page();
        $this->assertStringContainsString('Test Current-MP voted no on <em>No Division Title</em>', $page);
    }

    public function testVotedTellNoWithNoSentence() {
        $page = $this->fetch_division_page();
        $this->assertStringContainsString('Test Current-MP acted as teller for a vote on <em>Tell No Division Title</em>', $page);
    }

    public function testVotedTellYesWithYesSentence() {
        $page = $this->fetch_division_page();
        $this->assertStringContainsString('Test Current-MP acted as teller for a vote on <em>Tell Yes Division Title</em>', $page);
    }

    public function testStrongIndicators() {
        $page = $this->fetch_division_page();
        preg_match('#Major votes</h3>.*?</ul>#s', $page, $m);
        $major = $m[0];
        $this->assertStringContainsString('<li id="pw-2013-01-01-4-commons"', $major);
        $this->assertStringContainsString('<li id="pw-2013-01-01-5-commons"', $major);
        preg_match('#Minor votes</h3>.*?</ul>#s', $page, $m);
        $minor = $m[0];
        $this->assertStringContainsString('<li id="pw-2013-01-01-3-commons"', $minor);
        $this->assertStringContainsString('<li id="pw-2013-01-01-6-commons"', $minor);
    }

    public function testNotEnoughInfoStatement() {
        return $this->fetch_page( array( 'pagetype' => 'divisions', 'pid' => 2, 'policy' => 810, 'url' => '/mp/2/test_current-mp/test_westminster_constituency/divisions' ) );
        $this->assertStringContainsString('we don&rsquo;t have enough information to calculate Test Current-MP&rsquo;s position', $page);
    }

    public function testRecentDivisionsForMP() {
        $page = $this->fetch_mp_recent_page();
        $this->assertStringContainsString('<li id="pw-2013-01-01-3-commons"', $page);
        $this->assertStringNotContainsString('<li id="pw-2012-01-01-13-commons"', $page);
    }

    public function testSingleDivision() {
        $page = $this->base_fetch_page( array('url' => '/divisions/division.php', 'vote' => 'pw-3012-01-01-1-commons' ), 'divisions', 'division.php', '/divisions/division.php' );
        $this->assertStringContainsString('A majority of MPs  <b>voted in favour</b> of a thing', $page);
        $this->assertStringContainsString('Aye: 200', $page);
        $this->assertStringNotContainsString('No:', $page); # Summary 100, but no actual votes. In reality, summary can only be <= actual.
        $this->assertStringNotContainsString('Abstained', $page);
        $this->assertStringNotContainsString('Absent', $page);
    }

    public function testRecentDivisions() {
        $page = $this->fetch_recent_page();
        $this->assertStringContainsString('<li id="pw-2013-01-01-1-commons"', $page);
        $this->assertStringNotContainsString('<li id="pw-3012-01-01-2-commons"', $page);
    }
}
