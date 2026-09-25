<?php

namespace MySociety\TheyWorkForYou\DataClass\Postcode;

use MySociety\TheyWorkForYou\RepresentativeType;

/**
 * Display data for an MP or devolved representative's postcode card.
 */
class RepresentativeData {
    public string $name;
    public string $party;
    public string $constituency;
    public string $mp_url;
    public int $person_id;
    public ?string $image = null;
    public bool $former = false;

    public bool $standing_down_upcoming_election = false;

    /** Constituency or regional grouping for the representative. */
    public RepresentativeType $type = RepresentativeType::CONSTITUENCY;
}
