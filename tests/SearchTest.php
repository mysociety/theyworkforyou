<?php

/**
 * Provides test methods for search functionality.
 * Currently only the highlighting and constituency search.
 */

class SearchTest extends PHPUnit_Extensions_Database_TestCase
{
	public function setUp()
	{
        parent::setUp();
        include_once('www/includes/easyparliament/searchengine.php');
    }

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

}
