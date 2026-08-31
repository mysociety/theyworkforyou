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

    public function shortTitle(): string {
        $short = preg_replace('/^(All-Party Parliamentary Group (on |for )?|Cross-Party Group (on |for )?)/i', '', $this->title);
        return ucfirst($short);
    }
}
