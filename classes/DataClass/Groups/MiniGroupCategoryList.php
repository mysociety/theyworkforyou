<?php

declare(strict_types=1);

namespace MySociety\TheyWorkForYou\DataClass\Groups;

use MySociety\TheyWorkForYou\DataClass\BaseCollection;

/**
 * @extends BaseCollection<string>
 */
class MiniGroupCategoryList extends BaseCollection {
    public function __construct(string ...$categories) {
        $this->items = $categories;
    }
}
