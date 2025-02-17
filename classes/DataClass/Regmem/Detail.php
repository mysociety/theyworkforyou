<?php

declare(strict_types=1);

namespace MySociety\TheyWorkForYou\DataClass\Regmem;

use MySociety\TheyWorkForYou\DataClass\BaseModel;

class Detail extends BaseModel {
    public string $source;
    public ?string $slug = null;
    public ?string $display_as = null;
    public ?string $common_key = null;
    public ?string $description = null;
    public ?string $type = null;
    public $value = null;
    public AnnotationList $annotations;


    public function has_value(): bool {
        // if not null or empty string when removing trailing whitespace

        // if string, trim and return false if empty
        if (is_string($this->value)) {
            return trim($this->value) !== '';
        }

        return $this->value !== null;
    }

    /**
     * @return \Iterator<Detail>|
     */
    public function sub_details(): \Iterator {
        $items = new \ArrayIterator();

        if (!$this->type === 'container') {
            return $items;
        }


        foreach ($this->value as $detail_group) {
            foreach ($detail_group as $detail) {
                $detail_obj = self::fromArray($detail);
                $items[] = $detail_obj;
            }
        }

        return $items;
    }

}
