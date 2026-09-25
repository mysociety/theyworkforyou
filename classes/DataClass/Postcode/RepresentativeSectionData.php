<?php

namespace MySociety\TheyWorkForYou\DataClass\Postcode;

use MySociety\TheyWorkForYou\HouseType;

/**
 * Representative groups and their availability on the postcode results page.
 */
class RepresentativeSectionData extends SectionData {
    public bool $data_available = true;
    public HouseType $house;

    /**
     * Electoral groups, such as constituency and regional MSPs in Scotland.
     * Each group can contain one or several representatives.
     *
     * @var list<RepresentativeGroupData>
     */
    public array $groups = [];

    public ?string $empty_message = null;
    public ?string $footer = null;
}
