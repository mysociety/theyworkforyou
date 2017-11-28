<?php

/**
 * Testing for LibFilter
 */

class LibFilterTest extends PHPUnit_Framework_TestCase
{

    private $libFilter;

    public function setUp()
    {
        parent::setUp();
        $this->libFilter = new \MySociety\TheyWorkForYou\Utility\LibFilter;
    }

    /**
     * Test that HTML inside comment tags is correctly escaped.
     */
    public function testEscapeComments()
    {
        $this->assertEquals(
            'Outside <b>bold</b> <!-- Inside &lt;b&gt;bold&lt;/b&gt; -->',
            $this->libFilter->escape_comments('Outside <b>bold</b> <!-- Inside <b>bold</b> -->')
        );
    }

    /**
     * Test that tags are properly balanced, uneven pairs escaped etc
     */
    public function testBalanceHTML()
    {
        $this->assertEquals(
            '<p>Hello World!</p>',
            $this->libFilter->balance_html('<<<p>Hello World!</p>>')
        );
    }

    /**
     * Test that we allow allowed tags, and remove prohibited ones.
     */
    public function testCheckTags()
    {
        $this->assertEquals(
            'bad_code();',
            $this->libFilter->check_tags('<script>bad_code();</script>')
        );
        $this->assertEquals(
            '<b>Hello World!</b>',
            $this->libFilter->check_tags('<b>Hello World!</b>')
        );
        $this->assertEquals(
            'Hello <a href="#">World!</a>',
            $this->libFilter->check_tags('Hello <a href="#" onclick="bad_code();">World!</a>')
        );
    }

    /**
     * Test that tags are removed as expected
     */
    public function testRemoveBlanks()
    {
        $this->assertEquals(
            'Hello World!',
            $this->libFilter->process_remove_blanks('Hello <b></b>World!')
        );
    }

    /**
     * Test that comments are properly stripped.
     */
    public function testStripComments()
    {
        $this->assertEquals(
            'Outside ',
            $this->libFilter->check_tags('Outside <!-- Inside -->')
        );
    }

}
