<?php

declare(strict_types=1);

namespace MySociety\TheyWorkForYou\DataClass\Groups;

use MySociety\TheyWorkForYou\DataClass\BaseCollection;

/**
 * @extends BaseCollection<MiniMember>
 */
class MiniMemberList extends BaseCollection {
    public function __construct(MiniMember ...$members) {
        $this->items = $members;
    }
}
