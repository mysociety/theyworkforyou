<?php

declare(strict_types=1);

namespace MySociety\TheyWorkForYou\DataClass;

use Rutek\Dataclass\Collection;

/**
 * @template T
 * @extends Collection<T>
 */
class BaseCollection extends Collection {
    use BaseInterface;

    public function isEmpty(): bool {
        return empty($this->items);
    }
}
