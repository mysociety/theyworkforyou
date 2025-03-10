<?php

declare(strict_types=1);

namespace MySociety\TheyWorkForYou\DataClass\Regmem;

use MySociety\TheyWorkForYou\DataClass\BaseModel;

class Annotation extends BaseModel {
    public string $author;
    public string $type = "note";
    public string $content;
    public string $date_added;
    public string $content_format = "string";
}
