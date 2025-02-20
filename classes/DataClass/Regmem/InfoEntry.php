<?php

declare(strict_types=1);

namespace MySociety\TheyWorkForYou\DataClass\Regmem;

use MySociety\TheyWorkForYou\DataClass\BaseModel;

class InfoEntry extends BaseModel {
    public ?string $id = null;
    public ?string $comparable_id = null;
    public string $item_hash;
    public string $content;
    public string $content_format;
    public string $info_type;
    public bool $null_entry;
    public ?string $date_registered = null;
    public ?string $date_published = null;
    public ?string $date_updated = null;
    public ?string $date_received = null;
    public AnnotationList $annotations;
    public DetailGroup $details;
    public EntryList $sub_entries;

}
