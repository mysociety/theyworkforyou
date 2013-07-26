<?php

// TWFY Test Bootstrapper

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Go get the Composer autoloader to make sure we've got the right PHPUnit extensions.
require_once(dirname(__FILE__) . '/../vendor/autoload.php');

// Test to make sure we have the test DB environment variables. If not, this isn't testing, so abort.
// Define the DB connection constants before we do anything else.
if (
    isset($_SERVER['TWFY_TEST_DB_HOST']) AND
    isset($_SERVER['TWFY_TEST_DB_USER']) AND
    isset($_SERVER['TWFY_TEST_DB_PASS']) AND
    isset($_SERVER['TWFY_TEST_DB_NAME'])
) {

    // If there isn't a config file (most likely this is running an automated test) copy one in.
    if ( ! file_exists(dirname(__FILE__) . '/../conf/general')) {
        $conf = file_get_contents(dirname(__FILE__) . '/../conf/general-example');
        foreach(array('HOST', 'USER', 'PASS', 'NAME') as $key) {
            $conf = preg_replace(
                '/"OPTION_TWFY_DB_' . $key . '", *"[^"]*"/',
                 '"OPTION_TWFY_DB_' . $key . '", "' . $_SERVER["TWFY_TEST_DB_$key"] . '"',
                $conf
            );
        }
        $basedir = dirname(__FILE__) . '/../www/docs';
        $conf = preg_replace('/"BASEDIR", *"[^"]*"/', '"BASEDIR", "' . $basedir . '"', $conf); 
        file_put_contents(dirname(__FILE__) . '/../conf/general', $conf);
    }

} else {
    echo 'Testing environment variables not set. This will cause bad things to happen if testing happens on production. Aborting.';
    exit(1);
}

// Explicitly declare we're in testing (avoids trying deploy-only things)
define('TESTING', TRUE);

// Load up the init script (handles the rest of the config, DB connection etc)
include_once('www/includes/easyparliament/init.php');

// Go!
