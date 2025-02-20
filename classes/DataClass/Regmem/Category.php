<?php

declare(strict_types=1);

namespace MySociety\TheyWorkForYou\DataClass\Regmem;

use MySociety\TheyWorkForYou\DataClass\BaseModel;

class Category extends BaseModel {
    public string $category_id;
    public string $category_name;
    public ?string $category_description;
    public ?string $legislation_or_rule_name;
    public ?string $legislation_or_rule_url;
    public EntryList $summaries;
    public EntryList $entries;

    public function only_null_entries(): bool {
        foreach ($this->entries as $entry) {
            if (!$entry->null_entry) {
                return false;
            }
        }
        return true;
    }
}
