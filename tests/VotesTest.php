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
        $this->assertMatchesRegularExpression('#policy=363">\s*0 votes for, 2 votes against, 1 abstention, in 2013#', $page);
    }

    public function testLastUpdate() {
        $page = $this->fetch_votes_page();
        $this->assertStringContainsString('Last updated:  1 January 2013', $page);
    }
}
