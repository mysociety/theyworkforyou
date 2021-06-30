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
            if (class_exists('\Memcached')) {
                self::$memcache = new \Memcached;
                self::$memcache->addServer(OPTION_TWFY_MEMCACHED_HOST, OPTION_TWFY_MEMCACHED_PORT);
            } else {
                self::$memcache = new \Memcache;
                self::$memcache->connect(OPTION_TWFY_MEMCACHED_HOST, OPTION_TWFY_MEMCACHED_PORT);
            }
        }
    }

    public function set($key, $value, $timeout = 3600) {
        if (class_exists('\Memcached')) {
            self::$memcache->set(OPTION_TWFY_DB_NAME.':'.$key, $value, $timeout);
        } else {
            self::$memcache->set(OPTION_TWFY_DB_NAME.':'.$key, $value, MEMCACHE_COMPRESSED, $timeout);
        }
    }

    public function get($key) {
        // see http://php.net/manual/en/memcache.get.php#112056 for explanation of this
        $was_found = false;
        if (class_exists('\Memcached')) {
            $value = self::$memcache->get(OPTION_TWFY_DB_NAME.':'.$key, null, $was_found);
        } else {
            $value = self::$memcache->get(OPTION_TWFY_DB_NAME.':'.$key, $was_found);
        }
        if ($was_found === false) {
            return false; // mmmmm
        } else {
            return $value;
        }
    }
}
