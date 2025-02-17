<?php

declare(strict_types=1);

namespace MySociety\TheyWorkForYou\DataClass\Regmem;

use MySociety\TheyWorkForYou\DataClass\BaseCollection;

/**
 * @extends BaseCollection<Detail>
 */
class DetailGroup extends BaseCollection {
    public function __construct(Detail ...$names) {
        $this->items = $names;
    }
}
