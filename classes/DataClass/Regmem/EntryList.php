<?php

declare(strict_types=1);

namespace MySociety\TheyWorkForYou\DataClass\Regmem;

use MySociety\TheyWorkForYou\DataClass\BaseCollection;

/**
 * @extends BaseCollection<InfoEntry>
 */
class EntryList extends BaseCollection {
    public function __construct(InfoEntry ...$entries) {
        $this->items = $entries;
    }
}
