<?php

/**
 * Provides test methods to ensure pages contain what we want them to in various ways.
 */
class VotesTest extends FetchPageTestCase {
    public function getDataSet() {
        return $this->createMySQLXMLDataSet(dirname(__FILE__) . '/_fixtures/divisions.xml');
    }

    private function fetch_page($vars) {
        return $this->base_fetch_page($vars, 'mp', 'index.php', '/mp/votes.php');
    }

    private function fetch_votes_page() {
        return $this->fetch_page([ 'pagetype' => 'votes', 'pid' => 2, 'url' => '/mp/2/test_current-mp/test_westminster_constituency/votes' ]);
    }

    public function testVoteSummary() {
        $page = $this->fetch_votes_page();
        $this->assertStringContainsString('2 votes against, 1 abstention, in 2013', $page);
    }

    public function testLastUpdate() {
        $page = $this->fetch_votes_page();
        $this->assertStringContainsString('Last updated:  1 January 2013', $page);
    }

    public function testVoteRecencyHeadingsForReturningMember() {
        // Person 7 was an MP 2001-2010 and again 2015-2017, so has votes
        // from before the start of their most recent membership.
        $page = $this->fetch_page([ 'pagetype' => 'votes', 'pid' => 7, 'url' => '/mp/7/test_returning-mp/test_westminster_constituency/votes' ]);
        $this->assertStringContainsString('Last voted on between 2015 and 2017', $page);
        $this->assertStringContainsString('Last voted on between 2005 and 2009', $page);
        $this->assertStringNotContainsString('between 2015 and 2009', $page);
    }
}
