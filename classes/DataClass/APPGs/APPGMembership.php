<?php

declare(strict_types=1);

namespace MySociety\TheyWorkForYou\DataClass\APPGs;

use MySociety\TheyWorkForYou\DataClass\BaseModel;

class APPGMembership extends BaseModel {
    public string $role;
    public string $membership_source_url;
    public APPGDetails $appg;
}
