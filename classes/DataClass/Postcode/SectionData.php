<?php

namespace MySociety\TheyWorkForYou\DataClass\Postcode;

use MySociety\TheyWorkForYou\PostcodeSection;

/**
 * A representative or council section on the postcode results page.
 */
abstract class SectionData {
    public PostcodeSection $id;
    public string $title;
}
