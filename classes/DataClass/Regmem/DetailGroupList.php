<?php

declare(strict_types=1);

namespace MySociety\TheyWorkForYou\DataClass\Regmem;

use MySociety\TheyWorkForYou\DataClass\BaseCollection;

/**
 * @extends BaseCollection<DetailGroup>
 */
class DetailGroupList extends BaseCollection {
    public function __construct(DetailGroup ...$groups) {
        $this->items = $groups;
    }
}
