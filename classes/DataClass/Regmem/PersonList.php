<?php

declare(strict_types=1);

namespace MySociety\TheyWorkForYou\DataClass\Regmem;

use MySociety\TheyWorkForYou\DataClass\BaseCollection;

/**
 * @extends BaseCollection<Person>
 */
class PersonList extends BaseCollection {
    public function __construct(Person ...$persons) {
        $this->items = $persons;
    }
}
