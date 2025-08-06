<?php

declare(strict_types=1);

namespace MySociety\TheyWorkForYou\DataClass\Statements;

use MySociety\TheyWorkForYou\DataClass\BaseCollection;

/**
 * @extends BaseCollection<Signature>
 */
class SignatureList extends BaseCollection {
    public function __construct(Signature ...$signatures) {
        $this->items = $signatures;
    }

    public function count() {
        return count($this->items);
    }
}
