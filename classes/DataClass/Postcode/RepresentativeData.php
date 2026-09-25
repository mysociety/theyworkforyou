<?php

namespace MySociety\TheyWorkForYou\DataClass\Postcode;

use MySociety\TheyWorkForYou\RepresentativeType;

/**
 * Display data for an MP or devolved representative's postcode card.
 */
class RepresentativeData {
    /**
     * Full display name.
     */
    public string $name;
    /**
     * Party display name.
     */
    public string $party;
    /**
     * Name of the constituency or electoral region represented.
     */
    public string $constituency;
    /**
     * TheyWorkForYou profile URL for this representative.
     */
    public string $mp_url;
    /**
     * TheyWorkForYou person identifier.
     */
    public int $person_id;
    /**
     * Portrait URL or fallback image supplied by the image lookup. Null omits
     * the image from the card.
     */
    public ?string $image = null;
    /**
     * Whether this card represents a former rather than current membership.
     */
    public bool $former = false;

    /**
     * Whether to show the standing-down notice beneath the MP section.
     */
    public bool $standing_down_upcoming_election = false;

    /**
     * Grouping category used to separate constituency and regional MSPs.
     * Defaults to constituency for MPs; the devolved lookup assigns its grouping.
     */
    public RepresentativeType $type = RepresentativeType::CONSTITUENCY;
}
