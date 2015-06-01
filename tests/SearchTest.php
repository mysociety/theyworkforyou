<?php

/**
 * Provides test methods for search functionality.
 * Currently only the highlighting and constituency search.
 */

class SearchTest extends FetchPageTestCase
{
	public function setUp()
	{
        parent::setUp();
        include_once('www/includes/easyparliament/searchengine.php');
    }

    public function getDataSet()
    {
        return $this->createMySQLXMLDataSet(dirname(__FILE__) . '/_fixtures/search.xml');
    }

    private function fetch_page($vars)
    {
        return $this->base_fetch_page('', $vars, 'www/docs/search');
    }

    public function testConstituencySearch()
    {
        $this->assertEquals(
            array( array( 'Alyn and Deeside' ), false ),
            search_constituencies_by_query('Alyn')
        );
        $this->assertEquals(
            array( array( 'Alyn and Deeside' ), false ),
            search_constituencies_by_query('Alyn and Deeside')
        );
    }

    /**
     * Test looking up a person by a single name works as expected.
     */

    public function testSearchMemberDbLookupSingleName()
    {
        // Test a single (first) name.
        $results = search_member_db_lookup('Joseph');

        $this->assertEquals(1, $results->rows());
        $this->assertEquals(2, $results->field(0, 'person_id'));

        // Test a single (last) name.
        $results = search_member_db_lookup('Bloggs');

        $this->assertEquals(1, $results->rows());
        $this->assertEquals(2, $results->field(0, 'person_id'));

    }

    /**
     * Test looking up a person by full name works as expected.
     */

    public function testSearchMemberDbLookupFullName()
    {

        // Test a full name.
        $results = search_member_db_lookup('Mary Smith');

        $this->assertEquals(1, $results->rows());
        $this->assertEquals(3, $results->field(0, 'person_id'));

        // Test an inverse full name.
        $results = search_member_db_lookup('Smith Mary');

        $this->assertEquals(1, $results->rows());
        $this->assertEquals(3, $results->field(0, 'person_id'));

        // Test a name with title.
        $results = search_member_db_lookup('Mrs Smith');

        $this->assertEquals(1, $results->rows());
        $this->assertEquals(3, $results->field(0, 'person_id'));

    }

    /**
     * Test that glossarising a single word works as expected.
     *
     * @group xapian
     */
	public function testSearchNormal()
    {
        $SEARCHENGINE = new SEARCHENGINE('test');

        $this->assertEquals(
            'This is a <span class="hi">test</span> of the highlighting.',
            $SEARCHENGINE->highlight('This is a test of the highlighting.')
        );
    }

    /**
     * Test that glossarising a single word works as expected.
     *
     * @group xapian
     */
	public function testSearchLink()
    {
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
    public function testSearchPage()
    {
        $page = $this->fetch_page( array( ) );
        $this->assertContains('Search', $page);
    }

    /**
     * Test searching for an MP
     *
     * @group xapian
     */
    public function testSearchPageMP()
    {
        $page = $this->fetch_page( array( 's' => 'Mary Smith' ) );
        $this->assertContains('Mary Smith', $page);
        $this->assertContains('MP, Amber Valley', $page);
    }

    /**
     * Test that matches for multiple MPs are displayed
     *
     * @group xapian
     */
    public function testSearchPageMultipleMP()
    {
        $page = $this->fetch_page( array( 's' => 'Jones' ) );
        $this->assertContains('People matching <em class="current-search-term">Jones</em>', $page);
        $this->assertContains('Andrew Jones', $page);
        $this->assertContains('Simon Jones', $page);
    }

    /**
     * Test that matching a consituency name lists the MP
     *
     * @group xapian
     */
    public function testSearchPageCons() {
        $page = $this->fetch_page( array( 's' => 'Amber' ) );
        $this->assertContains('MP for <em class="current-search-term">Amber</em>', $page);
        $this->assertContains('Mary Smith', $page);
    }

    /**
     * Test that if the matching constituency does not have an MP the
     * exception is handled
     *
     * @group xapian
     */
    public function testSearchPageConsWithNoMp() {
        $page = $this->fetch_page( array( 's' => 'Alyn' ) );
        $this->assertNotContains('MP for <em class="current-search-term">Alyn</em>', $page);
        $this->assertNotContains('MPs in constituencies matching', $page);
    }

    /**
     * Test that if the search term matched multiple constituency names the
     * MPs for all of them are displayed
     *
     * @group xapian
     */
    public function testSearchPageMultipleCons() {
        $page = $this->fetch_page( array( 's' => 'Liverpool' ) );
        $this->assertContains('MPs in constituencies matching <em class="current-search-term">Liverpool</em>', $page);
        $this->assertContains('Susan Brown', $page);
        $this->assertContains('MP, Liverpool, Riverside', $page);
        $this->assertContains('Andrew Jones', $page);
        $this->assertContains('MP, Liverpool, Walton', $page);
    }

    /**
     * Test that glossary matches are displayed
     *
     * @group xapian
     */
    public function testSearchPageGlossary() {
        $page = $this->fetch_page( array( 's' => 'other place' ) );
        $this->assertContains('Matching glossary items', $page);
        $this->assertContains('<a href="/glossary/?gl=1">other place', $page);
    }

    /**
     * Test that spelling corrections are displayed
     *
     * @group xapian
     */
    public function testSearchPageSpellCorrect() {
        $page = $this->fetch_page( array( 's' => 'plice' ) );
        $this->assertContains('Did you mean <a href="/search/?q=place">place', $page);
    }
}
