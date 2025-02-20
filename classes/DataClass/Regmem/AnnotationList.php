<?php

declare(strict_types=1);

namespace MySociety\TheyWorkForYou\DataClass\Regmem;

use MySociety\TheyWorkForYou\DataClass\BaseCollection;

/**
 * @extends BaseCollection<Annotation>
 */
class AnnotationList extends BaseCollection {
    public function __construct(Annotation ...$annotations) {
        $this->items = $annotations;
    }
}
