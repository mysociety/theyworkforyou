<?php

/**
 * Provides test methods for search functionality.
 * Currently only the highlighting.
 */

class SearchTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
        parent::setUp();
        include_once('www/includes/easyparliament/searchengine.php');
    }

    /**
     * Test that glossarising a single word works as expected.
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
