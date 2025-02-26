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

    /**
     * @return array<string, string>
     */
    public function summary_details(?string $summary_id = null): array {
        // return an array of slug => value for the summary details.
        // containers will be converted to html tables
        // By default smashes all summaries together, but if a summary_id is provided,
        // only that summary is used.
        $details_array = [];
        foreach ($this->summaries as $summary) {
            if ($summary_id && $summary->id != $summary_id) {
                continue;
            }
            foreach ($summary->details as $detail) {
                if ($detail->type == "container") {
                    $df = $detail->as_df();
                    $details_array[$detail->slug] = $df->toHTML('url');
                } else {
                    $details_array[$detail->slug] = $detail->value;
                }
            }
        }
        return $details_array;
    }

    public function only_null_entries(): bool {
        foreach ($this->entries as $entry) {
            if (!$entry->null_entry) {
                return false;
            }
        }
        return true;
    }
}
