<?php

/**
 * Provides test methods for search functionality.
 * Currently only the highlighting and constituency search.
 */

class SearchTest extends PHPUnit_Extensions_Database_TestCase
{

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
        return $this->createMySQLXMLDataSet(dirname(__FILE__) . '/_fixtures/search.xml');
    }

    public function testConstituencySearch()
    {
        $this->assertEquals(
            array( array( 'Alyn and Deeside' ), false ),
            \MySociety\TheyWorkForYou\Utility\SearchEngine::searchConstituenciesByQuery('Alyn')
        );
        $this->assertEquals(
            array( array( 'Alyn and Deeside' ), false ),
            \MySociety\TheyWorkForYou\Utility\SearchEngine::searchConstituenciesByQuery('Alyn and Deeside')
        );
    }

    /**
     * Test looking up a person by a single name works as expected.
     */

    public function testSearchMemberDbLookupSingleName()
    {
        // Test a single (first) name.
        $results = \MySociety\TheyWorkForYou\Utility\SearchEngine::SearchMemberDbLookup('Joseph');

        $this->assertEquals(1, $results->rows());
        $this->assertEquals(1, $results->field(0, 'person_id'));

        // Test a single (last) name.
        $results = \MySociety\TheyWorkForYou\Utility\SearchEngine::SearchMemberDbLookup('Bloggs');

        $this->assertEquals(1, $results->rows());
        $this->assertEquals(1, $results->field(0, 'person_id'));

    }

    /**
     * Test looking up a person by full name works as expected.
     */

    public function testSearchMemberDbLookupFullName()
    {

        // Test a full name.
        $results = \MySociety\TheyWorkForYou\Utility\SearchEngine::SearchMemberDbLookup('Mary Smith');

        $this->assertEquals(1, $results->rows());
        $this->assertEquals(2, $results->field(0, 'person_id'));

        // Test an inverse full name.
        $results = \MySociety\TheyWorkForYou\Utility\SearchEngine::SearchMemberDbLookup('Smith Mary');

        $this->assertEquals(1, $results->rows());
        $this->assertEquals(2, $results->field(0, 'person_id'));

        // Test a name with title.
        $results = \MySociety\TheyWorkForYou\Utility\SearchEngine::SearchMemberDbLookup('Mrs Smith');

        $this->assertEquals(1, $results->rows());
        $this->assertEquals(2, $results->field(0, 'person_id'));

    }

    /**
     * Test that glossarising a single word works as expected.
     *
     * @group xapian
     */
	public function testSearchNormal()
    {
        $SEARCHENGINE = new \MySociety\TheyWorkForYou\SearchEngine('test');

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
        $SEARCHENGINE = new \MySociety\TheyWorkForYou\SearchEngine('test');

        $this->assertEquals(
            '<a href="/mp/?m=40584" title="Our page on Mr Test - \'the Member for Birmingham (Mr Test)\'">Mr <span class="hi">Test</span></a>',
            $SEARCHENGINE->highlight('<a href="/mp/?m=40584" title="Our page on Mr Test - \'the Member for Birmingham (Mr Test)\'">Mr Test</a>')
        );
    }

}
