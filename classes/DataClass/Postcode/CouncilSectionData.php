<?php

namespace MySociety\TheyWorkForYou\DataClass\Postcode;

/**
 * Local council names and the councillor lookup link for a postcode.
 */
class CouncilSectionData extends SectionData {
    /** @var list<string> */
    public array $council_names = [];

    public string $writetothem_url;
}
