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
    use HasChamber;

    public string $chamber;
    public string $language;
    public string $person_id;
    public string $person_name;
    public string $published_date;
    public CategoryList $categories;


    public function intId(): int {
        // extract the last part of the person_id, which is the integer id
        $parts = explode('/', $this->person_id);
        return (int) end($parts);
    }

    public function allEntryIds(): array {
        $entryIds = [];
        foreach ($this->categories as $category) {
            foreach ($category->entries as $entry) {
                $entryIds[] = $entry->id;
                foreach ($entry->sub_entries as $subEntry) {
                    $entryIds[] = $subEntry->id;
                }
            }
        }
        return $entryIds;
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
