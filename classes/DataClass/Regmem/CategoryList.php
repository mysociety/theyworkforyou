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

    public function limitToCategoryIds(array $categoryIds) {
        // Filter the categories to only include those with the given IDs
        $this->items = array_filter($this->items, function ($category) use ($categoryIds) {
            return in_array($category->category_id, $categoryIds);
        });
    }
}
