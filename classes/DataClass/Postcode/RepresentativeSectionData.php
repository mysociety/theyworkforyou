<?php

namespace MySociety\TheyWorkForYou\DataClass\Postcode;

use MySociety\TheyWorkForYou\HouseType;

/**
 * Representative groups and their availability on the postcode results page.
 */
class RepresentativeSectionData extends SectionData {
    /**
     * House represented by this section
     */
    public HouseType $house;

    /**
     * Electoral groups in display order, such as constituency and regional MSPs
     * in Scotland. Each group can contain one or several representatives.
     *
     * @var list<RepresentativeGroupData>
     */
    public array $groups = [];

    /**
     * Explanation shown instead of representative groups when data is unavailable.
     */
    public ?string $empty_message = null;
    /**
     * Optional text below the representative groups, such as an MP's
     * standing-down notice. Null omits the footer.
     */
    public ?string $footer = null;
}
