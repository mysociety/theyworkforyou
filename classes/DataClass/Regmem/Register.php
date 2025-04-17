<?php

declare(strict_types=1);

namespace MySociety\TheyWorkForYou\DataClass\Regmem;

use MySociety\TheyWorkForYou\DataClass\BaseModel;

class Register extends BaseModel {
    use HasChamber;
    public string $chamber;
    public string $language = "en";
    public string $published_date;
    public ?AnnotationList $annotations = null;
    public ?EntryList $summaries = null;
    public PersonList $persons;


    private function checkChamberSlug($chamber) {
        if ($chamber && preg_match('[^a-z0-9\-\.]', $chamber)) {
            throw new \Exception("No register found for $chamber");
        }
    }

    public function getPersonFromId(string $personId): ?Person {
        foreach ($this->persons as $person) {
            if ($person->person_id === $personId) {
                return $person;
            }
        }
        return null;
    }

    public static function getDate(string $chamber, string $date): Register {
        self::checkChamberSlug($chamber);

        $file_dir = RAWDATA . "scrapedjson/universal_format_regmem/" . $chamber . "/";
        $file_end = $date . ".json";
        // see if there's a file that matches this - but might have a bit in the middle
        $files = glob($file_dir . "*" . $file_end);
        if (count($files) === 0) {
            throw new \Exception("No register found for $chamber on $date");
        }
        if (count($files) > 1) {
            throw new \Exception("Multiple registers found for $chamber on $date");
        }
        return self::fromFile($files[0]);
    }

    public static function latestAsOfDate(string $chamber, string $date): Register {
        self::checkChamberSlug($chamber);

        $file_dir = RAWDATA . "scrapedjson/universal_format_regmem/" . $chamber . "/";
        $files = glob($file_dir . "*.json");
        if (count($files) === 0) {
            throw new \Exception("No register found for $chamber");
        }
        # sort most recent to least, find the first one before the date
        rsort($files);
        foreach ($files as $file) {
            $file_date = basename($file, ".json");
            if ($file_date <= $date) {
                return self::fromFile($file);
            }
        }
        throw new \Exception("No register found for $chamber on or before $date");
    }

    public static function getLatest(string $chamber): Register {
        self::checkChamberSlug($chamber);

        $file_dir = RAWDATA . "scrapedjson/universal_format_regmem/" . $chamber . "/";
        if ($chamber == "senedd") {
            $file_dir .= LANGUAGE . "/";
        }
        $files = glob($file_dir . "*.json");
        if (count($files) === 0) {
            throw new \Exception("No register found for $chamber");
        }
        $latest = max($files);
        // raise exception with name of $latest
        return self::fromFile($latest);
    }

    public static function getMisc(string $file): Register {
        self::checkChamberSlug($chamber);

        $file_path = RAWDATA . "scrapedjson/universal_format_regmem/misc/" . $file;
        return self::fromFile($file_path);
    }
}
