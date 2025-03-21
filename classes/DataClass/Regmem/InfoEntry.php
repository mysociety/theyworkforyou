<?php

declare(strict_types=1);

namespace MySociety\TheyWorkForYou\DataClass\Regmem;

use MySociety\TheyWorkForYou\DataClass\BaseModel;

class InfoEntry extends BaseModel {
    public ?string $id = null;
    public ?string $comparable_id = null;
    public string $item_hash;
    public string $content = "";
    public string $content_format = "string";
    public string $info_type = "entry";
    public bool $null_entry = false;
    public ?string $date_registered = null;
    public ?string $date_published = null;
    public ?string $date_updated = null;
    public ?string $date_received = null;
    public ?AnnotationList $annotations = null;
    public ?DetailGroup $details = null;
    public ?EntryList $sub_entries = null;

    public function isNew(string $register_date): ?bool {
        // This is for flagging new entries in a given register
        // if we can't know, return None

        // if there are sub_entries, check them first and return a true if we find one
        if ($this->sub_entries !== null) {
            foreach ($this->sub_entries as $sub_entry) {
                if ($sub_entry->isNew($register_date)) {
                    return true;
                }
            }
        }

        $latest_date = $this->date_published ?? $this->date_updated;
        if ($latest_date === null) {
            return null;
        }
        $latest_date = new \DateTime($latest_date);
        $register_date = new \DateTime($register_date);
        $diff = $register_date->diff($latest_date);
        return $diff->days <= 14;
    }

    public function hasEntries(): bool {
        return $this->sub_entries !== null && ($this->sub_entries->isEmpty() === false);
    }

    public function hasDetails(): bool {
        return $this->details !== null && ($this->details->isEmpty() === false);
    }

    public function hasEntryOrDetail(): bool {
        return $this->hasEntries() || $this->hasDetails();
    }

    public function get_detail(string $slug): ?Detail {
        // given a slug, return the detail object
        foreach ($this->details as $detail) {
            if ($detail->slug === $slug) {
                return $detail;
            }
        }
        return null;
    }

}
