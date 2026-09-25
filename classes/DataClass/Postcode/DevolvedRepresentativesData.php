<?php

namespace MySociety\TheyWorkForYou\DataClass\Postcode;

use MySociety\TheyWorkForYou\HouseType;

/**
 * The representatives found for a devolved parliament and their display label.
 */
class DevolvedRepresentativesData {
    /**
     * Representatives found for the devolved chamber, including former members
     * returned by the dissolution fallback. Empty when none could be loaded.
     *
     * @var list<RepresentativeData>
     */
    public array $members = [];

    /**
     * Devolved chamber.
     */
    public HouseType $house;

    /**
     * False when the lookup used the dissolution fallback for former members.
     */
    public bool $current = true;
    /**
     * House-specific plural label, such as MSPs, MSs or MLAs, used in headings
     * and unavailable-data messages.
     */
    public string $member_name_plural;
}
