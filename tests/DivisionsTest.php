<?php

/**
 * Provides test methods to ensure pages contain what we want them to in various ways.
 */
class DivisionsTest extends FetchPageTestCase {
    public function getDataSet() {
        return $this->createMySQLXMLDataSet(dirname(__FILE__) . '/_fixtures/divisions.xml');
    }

    private function fetch_page($vars) {
        return $this->base_fetch_page($vars, 'mp', 'index.php', '/mp/divisions.php');
    }

    private function fetch_mp_recent_page() {
        $vars = [ 'pagetype' => 'recent', 'pid' => 2, 'url' => '/mp/2/test_current-mp/test_westminster_constituency/recent' ];
        return $this->base_fetch_page($vars, 'mp', 'index.php', '/mp/recent.php');
    }

    private function fetch_recent_page() {
        return $this->base_fetch_page(['url' => '/divisions' ], 'divisions', 'index.php', '/divisions/index.php');
    }


    public function testSinglePolicy() {
        $p = new MySociety\TheyWorkForYou\Policies();
        $this->assertEquals(count($p->getPoliciesData()), 92);

        $p = $p->limitToSet('health');
        $this->assertEquals(count($p->getPoliciesData()), 5);

        $p = new MySociety\TheyWorkForYou\Policies();
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



    public function testRecentDivisionsForMP() {
        $page = $this->fetch_mp_recent_page();
        $this->assertStringContainsString('<li id="pw-2013-01-01-3-commons"', $page);
        $this->assertStringNotContainsString('<li id="pw-2012-01-01-13-commons"', $page);
    }

    public function testSingleDivision() {
        $page = $this->base_fetch_page(['url' => '/divisions/division.php', 'vote' => 'pw-3012-01-01-1-commons' ], 'divisions', 'division.php', '/divisions/division.php');
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
