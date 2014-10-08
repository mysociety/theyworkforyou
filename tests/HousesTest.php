<?php

/**
 * Provides test methods to ensure constants are available and as expected for all houses.
 */
class HousesTest extends PHPUnit_Framework_TestCase
{

    /**
     * Test that the Royal edge-case house is correctly defined.
     */
	public function testRoyalHouseDefined()
    {
        $this->assertEquals(0, HOUSE_TYPE_ROYAL);
    }

    /**
     * Test that the House of Commons is correctly defined.
     */
	public function testCommonsHouseDefined()
    {
        $this->assertEquals(1, HOUSE_TYPE_COMMONS);
    }

    /**
     * Test that the House of Lords is correctly defined.
     */
	public function testLordsHouseDefined()
    {
        $this->assertEquals(2, HOUSE_TYPE_LORDS);
    }

    /**
     * Test that the Northern Ireland Assembly is correctly defined.
     */
	public function testNIHouseDefined()
    {
        $this->assertEquals(3, HOUSE_TYPE_NI);
    }

    /**
     * Test that the Scottish Parliament is correctly defined.
     */
	public function testScotlandHouseDefined()
    {
        $this->assertEquals(4, HOUSE_TYPE_SCOTLAND);
    }

    /**
     * Test that the Assembly for Wales is correctly defined.
     */
	public function testWalesHouseDefined()
    {
        $this->assertEquals(5, HOUSE_TYPE_WALES);
    }
}
