<?php

/**
 * Testing for some functions in utility.php
 */

class UtilityTest extends PHPUnit\Framework\TestCase {
    public function setUp(): void {
        parent::setUp();
        include_once('www/includes/utility.php');
    }

    /**
     * Test the escaping of replacement strings for use with
     * preg_replace.
     */
    public function testPregReplacement() {
        $example = 'try \1 and $0, also backslash \ and dollar $ alone';
        $this->assertEquals(
            'try \\\\1 and \$0, also backslash \ and dollar $ alone',
            preg_replacement_quote($example)
        );
    }

    public function testDonateLinkWithoutOptions() {
        $this->assertSame('/support-us/', donate_link());
    }

    public function testDonateLinkWithCampaign() {
        $this->assertSame(
            '/support-us/?utm_campaign=twfy_rep_page',
            donate_link(campaign: 'twfy_rep_page')
        );
    }

    public function testDonateLinkDefaultsPresetAmountToSingleDonation() {
        $this->assertSame(
            '/support-us/?utm_campaign=postcode&amp;bcn_donation_amount=5&amp;bcn_donation_frequency=single',
            donate_link(campaign: 'postcode', how_much: 5)
        );
    }

    public function testDonateLinkCanSelectRecurringDonation() {
        $this->assertSame(
            '/support-us/?utm_campaign=twfy_rep_page&amp;bcn_donation_amount=5&amp;bcn_donation_frequency=monthly',
            donate_link(campaign: 'twfy_rep_page', how_much: 5, how_often: 'monthly')
        );
    }

    public function testDonateLinkIncludesAttributionParameters() {
        $this->assertSame(
            '/support-us/?utm_campaign=postcode&amp;utm_source=theyworkforyou.com&amp;utm_content=postcode%20donate&amp;utm_medium=link&amp;bcn_donation_amount=5&amp;bcn_donation_frequency=single',
            donate_link(
                campaign: 'postcode',
                how_much: 5,
                source: 'theyworkforyou.com',
                content: 'postcode donate',
                medium: 'link'
            )
        );
    }

    public function testDonateLinkRejectsUnknownFrequency() {
        $this->expectException(InvalidArgumentException::class);
        donate_link(how_often: 'weekly');
    }
}
