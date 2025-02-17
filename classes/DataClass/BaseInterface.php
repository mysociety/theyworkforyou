<?php

namespace MySociety\TheyWorkForYou\DataClass;

use function Rutek\Dataclass\transform;

trait BaseInterface {
    public static function fromJson(string $json): self {
        $data = json_decode($json, true);
        try {
            return transform(static::class, $data);
        } catch (\Exception $transformException) {
            echo json_encode($transformException, JSON_PRETTY_PRINT);
            throw $transformException;
        }
    }

    public static function fromArray(array $data): self {
        try {
            return transform(static::class, $data);
        } catch (\Exception $transformException) {
            echo json_encode($transformException, JSON_PRETTY_PRINT);
            throw $transformException;
        }
    }
}
