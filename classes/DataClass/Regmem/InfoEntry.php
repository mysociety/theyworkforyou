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
