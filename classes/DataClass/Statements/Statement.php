<?php
/**
 * Mirrors pydantic model for deseralisation in a PHP context.
 * For adding display related helper functions.
 * @package TheyWorkForYou
 */

declare(strict_types=1);

namespace MySociety\TheyWorkForYou\DataClass\Statements;

use MySociety\TheyWorkForYou\DataClass\BaseModel;

class Statement extends BaseModel {
    public string $title;
    public string $info_source;
    public string $chamber_slug;
    public string $type;
    public int $id;
    public string $slug;
    public string $date;

    public function link() {
        return "https://votes.theyworkforyou.com/statement/" . $this->chamber_slug . '/' . $this->date . '/' . $this->slug;
    }
}
