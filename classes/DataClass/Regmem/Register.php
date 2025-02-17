<?php

declare(strict_types=1);

namespace MySociety\TheyWorkForYou\DataClass\Regmem;

use MySociety\TheyWorkForYou\DataClass\BaseModel;

class Register extends BaseModel {
    public string $chamber;
    public string $language;
    public string $published_date;
    public AnnotationList $annotations;
    public EntryList $summaries;
    public PersonList $persons;
}
