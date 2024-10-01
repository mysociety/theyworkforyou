<?php

namespace MySociety\TheyWorkForYou;

class Redis extends \Predis\Client {
    public function __construct() {
        if (REDIS_SENTINELS) {
            $sentinels = [];
            $sentinel_port = REDIS_SENTINEL_PORT;
            foreach (explode(",", REDIS_SENTINELS) as $sentinel) {
                // Wrap IPv6 addresses in square brackets
                if (filter_var($sentinel, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    $sentinel = "[${sentinel}]";
                }
                $sentinels[] = "tcp://${sentinel}:{$sentinel_port}?timeout=0.100";
            }
            $options = [
                'replication' => 'sentinel',
                'service' => REDIS_SERVICE_NAME,
                'parameters' => [
                    'database' => REDIS_DB_NUMBER,
                ],
            ];
            if (REDIS_DB_PASSWORD) {
                $options['parameters']['password'] = REDIS_DB_PASSWORD;
            }
            parent::__construct($sentinels, $options);
        } else {
            $redis_args = [
                'host' => REDIS_DB_HOST,
                'port' => REDIS_DB_PORT,
                'db' => REDIS_DB_NUMBER,
            ];
            if (REDIS_DB_PASSWORD) {
                $redis_args['password'] = REDIS_DB_PASSWORD;
            }
            parent::__construct($redis_args);
        }
    }
}
