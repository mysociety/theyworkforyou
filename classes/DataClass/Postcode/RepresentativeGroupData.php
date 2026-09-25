<?php

namespace MySociety\TheyWorkForYou\DataClass\Postcode;

/**
 * Representative cards displayed together under an optional heading.
 */
class RepresentativeGroupData {
    /**
     * Optional heading above these cards, such as Regional MSPs.
     */
    public ?string $title = null;

    /**
     * Representative cards in display order within this electoral group.
     *
     * @var list<RepresentativeData>
     */
    public array $members = [];
}
