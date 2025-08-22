<?php

declare(strict_types=1);

namespace MySociety\TheyWorkForYou\DataClass\Groups;

use MySociety\TheyWorkForYou\DataClass\BaseCollection;

/**
 * @extends BaseCollection<MiniGroup>
 */
class MiniGroupList extends BaseCollection {
    public function __construct(MiniGroup ...$members) {
        $this->items = $members;
    }

    public function findByName(string $name): ?MiniGroup {
        foreach ($this->items as $member) {
            if ($member->name === $name) {
                return $member;
            }
        }
        return null;
    }

    public function findBySlug(string $slug): ?MiniGroup {
        foreach ($this->items as $member) {
            if ($member->slug === $slug) {
                return $member;
            }
        }
        return null;
    }

    public static function uk_committees(): self {
        $file_dir = RAWDATA . "scrapedjson/committees/uk_committees_groups.json";

        $result = self::fromCachedFile($file_dir);
        // if $result is null the file isn't present, return an empty list
        if ($result === null) {
            return new self();
        }
        return $result;
    }
}
