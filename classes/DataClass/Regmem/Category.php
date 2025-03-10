<?php

declare(strict_types=1);

namespace MySociety\TheyWorkForYou\DataClass\Regmem;

use MySociety\TheyWorkForYou\DataClass\BaseModel;

class Category extends BaseModel {
    public string $category_id;
    public string $category_name;
    public ?string $category_description = null;
    public ?string $legislation_or_rule_name = null;
    public ?string $legislation_or_rule_url = null;
    public ?EntryList $summaries = null;
    public ?EntryList $entries = null;

    public static function emojiLookup(string $value): string {
        $emoji_lookup = [
            "Remuneration or other material benefit" => "💼",
            "Membership/Chairmanship of bodies in receipt of Senedd funds" => "👥",
            "Record of the employment of family members" => "👪",
            "Directorships" => "👔",
            "Gifts, hospitality, material benefit or advantage" => "🎁",
            "Remunerated employment, office, profession etc" => "💼",
            "Financial sponsorship" => "💳",
            "Overseas visits" => "🌍",
            "Land and property" => "🏠",
            "Record of membership of societies" => "🤝",
            "Donations and other support" => "💳",
            "Gifts, benefits and hospitality" => "🎁",
            "Visits" => "🌍",
            "Shareholdings" => "📈",
            "Land and Property" => "🏠",
            "Miscellaneous" => "🏷️",
            "Unremunerated interests" => "🤝",
            "Family members who benefit from Office Cost Expenditure" => "👪",
            "Voluntary" => "🤝",
            "Controlled transactions" => "📊",
            "Gifts" => "🎁",
            "Heritable property" => "🏠",
            "Interest in shares" => "📈",
            "Overseas visits" => "🌍",
            "Remuneration and related undertaking" => "💼",
            "Donations and other support (including loans) for activities as an MP" => "💳",
            "Gifts, benefits and hospitality from UK sources" => "🎁",
            "Employment and earnings - Ad hoc payments" => "💼",
            "Employment and earnings - Ongoing paid employment" => "💼",
            "Miscellaneous" => "🏷️",
            "Employment and earnings" => "💼",
            "Shareholdings" => "📈",
            "Land and property (within or outside the UK)" => "🏠",
            "Visits outside the UK" => "🌍",
            "Family members engaged in third-party lobbying" => "👪",
            "Gifts and benefits from sources outside the UK" => "🌐",
        ];
        return $emoji_lookup[$value] ?? "";
    }

    public function emoji() {
        return $this->emojiLookup($this->category_name);
    }

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
