<?php
/**
 * Mirrors pydantic model for deseralisation in a PHP context.
 * For adding display related helper functions.
 * @package TheyWorkForYou
 */

declare(strict_types=1);

namespace MySociety\TheyWorkForYou\DataClass\Regmem;

use MySociety\TheyWorkForYou\DataClass\BaseModel;

use InvalidArgumentException;

class Person extends BaseModel {
    public string $chamber;
    public string $language;
    public string $person_id;
    public string $person_name;
    public string $published_date;
    public CategoryList $categories;

    public function displayChamber(): string {
        switch ($this->chamber) {
            case 'house-of-commons':
                return 'House of Commons';
            case 'welsh-parliament':
                return 'Senedd';
            case 'scottish-parliament':
                return 'Scottish Parliament';
            case 'northern-ireland-assembly':
                return 'Northern Ireland Assembly';
            default:
                return 'Unknown Chamber';
        }
    }

    public function getCategoryFromId(string $categoryId): Category {
        foreach ($this->categories as $category) {
            if ($category->category_id === $categoryId) {
                return $category;
            }
        }
        throw new InvalidArgumentException("Category $categoryId not found in register");
    }
}
