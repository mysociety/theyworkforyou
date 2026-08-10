<?php

/**
 * Mirrors pydantic model for deseralisation in a PHP context.
 * For adding display related helper functions.
 * @package TheyWorkForYou
 */

declare(strict_types=1);

namespace MySociety\TheyWorkForYou\DataClass\APPGs;

use MySociety\TheyWorkForYou\DataClass\BaseModel;

class APPGDetails extends BaseModel {
    public string $slug;
    public string $parliament = "uk";
    public string $title;
    public string $purpose;
    public ?string $website;
    public string $source_url;
    // public Array $categories;

    /**
     * Remove the repeated group-type prefix so cards emphasize the unique name.
     */
    public function shortTitle(): string {
        $prefixes = [
            '/^All-Party Parliamentary Group (on |for )?/i',
            '/^Cross-Party Group (on |for )?/i',
            '/^All-Party Group (on |for )?/i',
        ];
        foreach ($prefixes as $prefix) {
            $short = preg_replace($prefix, '', $this->title, count: $count);
            if ($count) {
                return ucfirst($short);
            }
        }
        return ucfirst($this->title);
    }
}
