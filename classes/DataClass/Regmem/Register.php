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

    public function getPersonFromId(string $personId): ?Person {
        foreach ($this->persons as $person) {
            if ($person->person_id === $personId) {
                return $person;
            }
        }
        return null;
    }


    public static function getMisc(string $file): Register {
        $file_path = RAWDATA . "scrapedjson/universal_format_regmem/misc/" . $file;
        return self::fromFile($file_path);
    }
}
