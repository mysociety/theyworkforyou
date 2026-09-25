<?php

namespace MySociety\TheyWorkForYou\DataClass\Postcode;

use MySociety\TheyWorkForYou\PostcodeSection;

/**
 * A representative or council section on the postcode results page.
 */
abstract class SectionData {
    /**
     * Section anchor used by the jump links and expand/collapse controls.
     */
    public PostcodeSection $id;
    /**
     * Display heading used both above the section and in its navigation link.
     */
    public string $title;
}
