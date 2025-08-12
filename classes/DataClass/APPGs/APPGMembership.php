<?php

declare(strict_types=1);

namespace MySociety\TheyWorkForYou\DataClass\APPGs;

use MySociety\TheyWorkForYou\DataClass\BaseModel;

/**
 * @extends BaseModel<Category>
 */
class APPGMembership extends BaseModel {
    public string $role;
    public APPGDetails $appg;
}
