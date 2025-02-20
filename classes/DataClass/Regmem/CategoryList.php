<?php

declare(strict_types=1);

namespace MySociety\TheyWorkForYou\DataClass\Regmem;

use MySociety\TheyWorkForYou\DataClass\BaseCollection;

/**
 * @extends BaseCollection<Category>
 */
class CategoryList extends BaseCollection {
    public function __construct(Category ...$categories) {
        $this->items = $categories;
    }
}
