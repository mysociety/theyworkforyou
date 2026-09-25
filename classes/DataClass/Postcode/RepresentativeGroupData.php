<?php

namespace MySociety\TheyWorkForYou\DataClass\Postcode;

/**
 * Representative cards displayed together under an optional heading.
 */
class RepresentativeGroupData {
    public ?string $title = null;

    /** @var list<RepresentativeData> */
    public array $members = [];
}
