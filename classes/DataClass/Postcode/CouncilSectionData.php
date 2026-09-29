<?php

namespace MySociety\TheyWorkForYou\DataClass\Postcode;

/**
 * Local council names and the councillor lookup link for a postcode.
 */
class CouncilSectionData extends SectionData {
    /**
     * Council names for the postcode
     *
     * @var list<string>
     */
    public array $council_names = [];

    /**
     * Postcode-specific WriteToThem URL.
     */
    public string $writetothem_url;
}
