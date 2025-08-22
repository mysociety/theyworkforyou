<?php
/**
 * Mirrors pydantic model for deseralisation in a PHP context.
 * For adding display related helper functions.
 * @package TheyWorkForYou
 */

declare(strict_types=1);

namespace MySociety\TheyWorkForYou\DataClass\Groups;

use MySociety\TheyWorkForYou\DataClass\BaseModel;

class MiniMember extends BaseModel {
    public string $name;
    public ?string $twfy_id = null;
    public ?string $officer_role = null;
    public bool $external_member = false;
    public bool $is_current = false;
}
