<?php

namespace MySociety\TheyWorkForYou\DataClass\Postcode;

use MySociety\TheyWorkForYou\HouseType;

/**
 * The representatives found for a devolved parliament and their display label.
 */
class DevolvedRepresentativesData {
    /** @var list<RepresentativeData> */
    public array $members = [];

    public HouseType $house;

    public bool $current = true;
    public string $member_name_plural;
}
