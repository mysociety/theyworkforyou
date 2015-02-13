<?php

/**
 * Testing for some functions in utility.php
 *
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */

class UtilityTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
        parent::setUp();
        include_once('www/includes/utility.php');
    }

    /**
     * Test the escaping of replacement strings for use with
     * preg_replace.
     */
	public function testPregReplacement()
    {
        $example = 'try \1 and $0, also backslash \ and dollar $ alone';
        $this->assertEquals(
            'try \\\\1 and \$0, also backslash \ and dollar $ alone',
            preg_replacement_quote($example)
        );
    }
}
