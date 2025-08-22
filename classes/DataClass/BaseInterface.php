<?php

namespace MySociety\TheyWorkForYou\DataClass;

use function Rutek\Dataclass\transform;

trait BaseInterface {
    /**
     * @return static
     */
    public static function fromJson(string $json) {
        $data = json_decode($json, true);
        try {
            return transform(static::class, $data);
        } catch (\Exception $transformException) {
            echo json_encode($transformException, JSON_PRETTY_PRINT);
            throw $transformException;
        }
    }

    /**
     * @return static
     */
    public static function fromFile(string $file) {
        $content = file_get_contents($file);
        return static::fromJson($content);
    }

    /**
     * @return static
     */
    public static function fromCachedFile(string $file) {
        $memcache = new \MySociety\TheyWorkForYou\Memcache();
        $data = $memcache->get($file);
        if (!$data) {
            $content = file_get_contents($file);
            $memcache->set($file, $content, 60 * 60 * 1); // Cache for 1 hour
            $data = static::fromJson($content);
        }
        return static::fromJson($data);
    }

    /**
     * @return static
     */
    public static function fromArray(array $data) {
        try {
            return transform(static::class, $data);
        } catch (\Exception $transformException) {
            echo json_encode($transformException, JSON_PRETTY_PRINT);
            throw $transformException;
        }
    }
}
