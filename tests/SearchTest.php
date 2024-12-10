<?php

/**
 * Provides test methods for search functionality.
 * Currently only the highlighting and constituency search.
 */

class SearchTest extends FetchPageTestCase {
    public function setUp(): void {
        parent::setUp();
        include_once('www/includes/easyparliament/searchengine.php');
    }

    public function getDataSet() {
        return $this->createMySQLXMLDataSet(dirname(__FILE__) . '/_fixtures/search.xml');
    }

    private function fetch_page($vars) {
        return $this->base_fetch_page($vars, 'search');
    }

    public function testConstituencySearch() {
        $this->assertEquals(
            [ [ 'Alyn and Deeside' ], false ],
            \MySociety\TheyWorkForYou\Utility\Search::searchConstituenciesByQuery('Alyn')
        );
        $this->assertEquals(
            [ [ 'Alyn and Deeside' ], false ],
            \MySociety\TheyWorkForYou\Utility\Search::searchConstituenciesByQuery('Alyn and Deeside')
        );
    }

    /**
     * Test looking up a person by a single name works as expected.
     */

    public function testSearchMemberDbLookupSingleName() {
        // Test a single (first) name.
        $results = \MySociety\TheyWorkForYou\Utility\Search::searchMemberDbLookup('Joseph');

        $this->assertEquals(1, count($results));
        $this->assertEquals(2, $results[0]);

        // Test a single (last) name.
        $results = \MySociety\TheyWorkForYou\Utility\Search::searchMemberDbLookup('Bloggs');

        $this->assertEquals(1, count($results));
        $this->assertEquals(2, $results[0]);

    }

    /**
     * Test looking up a person by full name works as expected.
     */

    public function testSearchMemberDbLookupFullName() {

        // Test a full name.
        $results = \MySociety\TheyWorkForYou\Utility\Search::searchMemberDbLookup('Mary Smith');

        $this->assertEquals(1, count($results));
        $this->assertEquals(3, $results[0]);

        // Test an inverse full name.
        $results = \MySociety\TheyWorkForYou\Utility\Search::searchMemberDbLookup('Smith Mary');

        $this->assertEquals(1, count($results));
        $this->assertEquals(3, $results[0]);

        // Test a name with title.
        $results = \MySociety\TheyWorkForYou\Utility\Search::searchMemberDbLookup('Mrs Smith');

        $this->assertEquals(1, count($results));
        $this->assertEquals(3, $results[0]);

    }

    /**
     * Test that glossarising a single word works as expected.
     *
     * @group xapian
     */
    public function testSearchNormal() {
        $SEARCHENGINE = new SEARCHENGINE('test');

        $this->assertEquals(
            'This is a <span class="hi">test</span> of the highlighting.',
            $SEARCHENGINE->highlight('This is a test of the highlighting.')
        );
    }

    /**
     * Test that search term highlighting skips tag attributes
     *
     * @group xapian
     */
    public function testSearchLink() {
        $SEARCHENGINE = new SEARCHENGINE('test');

        $this->assertEquals(
            '<a href="/mp/?m=40584" title="Our page on Mr Test - \'the Member for Birmingham (Mr Test)\'">Mr <span class="hi">Test</span></a>',
            $SEARCHENGINE->highlight('<a href="/mp/?m=40584" title="Our page on Mr Test - \'the Member for Birmingham (Mr Test)\'">Mr Test</a>')
        );
    }

    /**
     * Test fetching the search page
     *
     * @group xapian
     */
    public function testSearchPage() {
        $page = $this->fetch_page([ ]);
        $this->assertStringContainsString('Search', $page);
    }

    /**
     * Test searching for an MP
     *
     * @group xapian
     */
    public function testSearchPageMP() {
        $page = $this->fetch_page([ 'q' => 'Mary Smith' ]);
        $this->assertStringContainsString('Mary Smith', $page);
        $this->assertMatchesRegularExpression('/MP *for Amber Valley/', $page);
    }

    /**
     * Test that matches for multiple MPs are displayed
     *
     * @group xapian
     */
    public function testSearchPageMultipleMP() {
        $page = $this->fetch_page([ 'q' => 'Jones' ]);
        $this->assertStringContainsString('People matching <em class="current-search-term">Jones</em>', $page);
        $this->assertStringContainsString('Andrew Jones', $page);
        $this->assertStringContainsString('Simon Jones', $page);
    }

    /**
     * Test that matching a consituency name lists the MP
     *
     * @group xapian
     */
    public function testSearchPageCons() {
        $page = $this->fetch_page([ 'q' => 'Amber' ]);
        $this->assertStringContainsString('MP for <em class="current-search-term">Amber</em>', $page);
        $this->assertStringContainsString('Mary Smith', $page);
    }

    /**
     * Test that if the matching constituency does not have an MP the
     * exception is handled
     *
     * @group xapian
     */
    public function testSearchPageConsWithNoMp() {
        $page = $this->fetch_page([ 'q' => 'Alyn' ]);
        $this->assertStringNotContainsString('MP for <em class="current-search-term">Alyn</em>', $page);
        $this->assertStringNotContainsString('MPs in constituencies matching', $page);
    }

    /**
     * Test that if the search term matched multiple constituency names the
     * MPs for all of them are displayed
     *
     * @group xapian
     */
    public function testSearchPageMultipleCons() {
        $page = $this->fetch_page([ 'q' => 'Liverpool' ]);
        $this->assertStringContainsString('MPs in constituencies matching <em class="current-search-term">Liverpool</em>', $page);
        $this->assertStringContainsString('Susan Brown', $page);
        $this->assertMatchesRegularExpression('/MP *for Liverpool, Riverside/', $page);
        $this->assertStringContainsString('Andrew Jones', $page);
        $this->assertMatchesRegularExpression('/MP *for Liverpool, Walton/', $page);
    }

    /**
     * Test that glossary matches are displayed
     *
     * @group xapian
     */
    public function testSearchPageGlossary() {
        $page = $this->fetch_page([ 'q' => 'other place' ]);
        $this->assertStringContainsString('Glossary items matching', $page);
        $this->assertStringContainsString('<a href="/glossary/?gl=1">&ldquo;other place', $page);
    }

    /**
     * Test that spelling corrections are displayed
     *
     * @group xapian
     */
    public function testSearchPageSpellCorrect() {
        $page = $this->fetch_page([ 'q' => 'plice' ]);
        $this->assertStringContainsString('Did you mean <a href="/search/?q=place">place', $page);
    }

    /**
     * Test that grouping by speaker works
     *
     * @group xapian
     */
    public function testSearchBySpeakerNoResults() {
        $page = $this->fetch_page([ 'q' => 'splice', 'o' => 'p' ]);
        $this->assertStringContainsString('Who says splice the most', $page);
        $this->assertStringContainsString('No results', $page);
    }

    /**
     * Test that search highlighting with phrases skips words contained in link title attributes.
     *
     * @group xapian
     */
    public function testSearchPhraseHighlightingInTags() {
        $SEARCHENGINE = new SEARCHENGINE('"Shabana"');

        $expected_text = '<p pid="b.893.4/1">On a point of order, Mr <a href="/glossary/?gl=21" title="The Speaker is an MP who has been elected to act as Chairman during debates..." class="glossary">Speaker</a>. In yesterday’s Finance Bill debate, <a href="/mp/?m=40084" title="Our page on Shabana Mahmood - \'the hon. Member for Birmingham, Ladywood (Shabana Mahmood)\'"><span class="hi">Shabana</span> Mahmood</a> said that the tax gap was 32 billion when the previous Government left office and that it has now gone up to 35 billion. Official Her Majesty’s Revenue and Customs figures show the tax gap was actually 42 billion when Labour left office, so there has been a fall of 7 billion under this Government';
        $text = '<p pid="b.893.4/1">On a point of order, Mr <a href="/glossary/?gl=21" title="The Speaker is an MP who has been elected to act as Chairman during debates..." class="glossary">Speaker</a>. In yesterday&#8217;s Finance Bill debate, <a href="/mp/?m=40084" title="Our page on Shabana Mahmood - \'the hon. Member for Birmingham, Ladywood (Shabana Mahmood)\'">Shabana Mahmood</a> said that the tax gap was 32 billion when the previous Government left office and that it has now gone up to 35 billion. Official Her Majesty&#8217;s Revenue and Customs figures show the tax gap was actually 42 billion when Labour left office, so there has been a fall of 7 billion under this Government';
        $this->assertEquals(
            $expected_text,
            $SEARCHENGINE->highlight($text)
        );
    }

    /**
     * Test that search RSS links are displayed
     *
     * @group xapian
     */
    public function testSearchPageRSS() {
        $page = $this->fetch_page([ 'q' => 'test' ]);
        $this->assertStringContainsString('<a href="/search/rss/?s=test">get an RSS feed', $page);
    }

}
