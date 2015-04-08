<?php

/**
 * Memcache wrapper
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou;

class Memcache {

    static $memcache;

    public function __construct() {
        if (!self::$memcache) {
            self::$memcache = new \Memcache;
            self::$memcache->connect('localhost', 11211);
        }
    }

    public function set($key, $value, $timeout = 3600) {
        self::$memcache->set(OPTION_TWFY_DB_NAME . ':' . $key, $value, MEMCACHE_COMPRESSED, $timeout);
    }

    public function get($key) {
        // see http://php.net/manual/en/memcache.get.php#112056 for explanation of this
        $was_found = false;
        $value = self::$memcache->get(OPTION_TWFY_DB_NAME . ':' . $key, $was_found);
        if ( $was_found === false ) {
            return false; // mmmmm
        } else {
            return $value;
        }
    }
}
