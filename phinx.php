<?php

if (
    isset($_SERVER['TWFY_TEST_DB_HOST']) AND
    isset($_SERVER['TWFY_TEST_DB_USER']) AND
    isset($_SERVER['TWFY_TEST_DB_PASS']) AND
    isset($_SERVER['TWFY_TEST_DB_NAME'])
) {

    // Define the DB constants based on the test environment
    define('OPTION_TWFY_DB_HOST', $_SERVER['TWFY_TEST_DB_HOST']);
    define('OPTION_TWFY_DB_USER', $_SERVER['TWFY_TEST_DB_USER']);
    define('OPTION_TWFY_DB_PASS', $_SERVER['TWFY_TEST_DB_PASS']);
    define('OPTION_TWFY_DB_NAME', $_SERVER['TWFY_TEST_DB_NAME']);

} else {
    include_once('conf/general');
}



return [
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/db/migrations',
    ],
    'environments' =>
        [
            'default_migration_table' => 'migrations',
            'default_database' => 'default',
            'default' => [
                'adapter' => 'mysql',
                'host' => OPTION_TWFY_DB_HOST,
                'name' => OPTION_TWFY_DB_NAME,
                'user' => OPTION_TWFY_DB_USER,
                'pass' => OPTION_TWFY_DB_PASS,
                'port' => 3306,
                'charset' => 'utf8'
            ]
        ]
    ];
