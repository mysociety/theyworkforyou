<?php

declare(strict_types=1);

namespace MySociety\TheyWorkForYou\DataClass\APPGs;

use MySociety\TheyWorkForYou\DataClass\BaseCollection;

/**
 * @extends BaseCollection<APPGMembership>
 */
class APPGMembershipList extends BaseCollection {
    public function __construct(APPGMembership ...$memberships) {
        $this->items = $memberships;
    }

    public function count() {
        return count($this->items);
    }
}
