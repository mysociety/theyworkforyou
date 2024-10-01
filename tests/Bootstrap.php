<?php

// TWFY Test Bootstrapper

// Go get the Composer autoloader to make sure we've got the right PHPUnit extensions.
require_once(dirname(__FILE__) . '/../vendor/autoload.php');

// Test to make sure we have the test DB environment variables. If not, this isn't testing, so abort.
// Define the DB connection constants before we do anything else.
if (
    isset($_SERVER['TWFY_TEST_DB_HOST']) and
    isset($_SERVER['TWFY_TEST_DB_USER']) and
    isset($_SERVER['TWFY_TEST_DB_PASS']) and
    isset($_SERVER['TWFY_TEST_DB_NAME'])
) {

    // Define the DB constants before config does. This should happen regardless of the presence of a config file.
    define('OPTION_TWFY_DB_HOST', $_SERVER['TWFY_TEST_DB_HOST']);
    define('OPTION_TWFY_DB_USER', $_SERVER['TWFY_TEST_DB_USER']);
    define('OPTION_TWFY_DB_PASS', $_SERVER['TWFY_TEST_DB_PASS']);
    define('OPTION_TWFY_DB_NAME', $_SERVER['TWFY_TEST_DB_NAME']);

    // Define the base directory
    define("BASEDIR", dirname(__FILE__) . '/../www/docs');

    # point to the default mapit
    define('OPTION_MAPIT_URL', 'https://mapit.mysociety.org/');

    // If there isn't a config file (most likely this is running an automated test) copy one in.
    if (! file_exists(dirname(__FILE__) . '/../conf/general')) {
        copy(dirname(__FILE__) . '/../conf/general-example', dirname(__FILE__) . '/../conf/general');
    }

} else {
    echo "Testing environment variables not set. This will cause bad things to happen if testing happens on production. Aborting.\n";
    exit(1);
}

// Explicitly declare we're in testing (avoids trying deploy-only things)
define('TESTING', true);

// Load up the init script (handles the rest of the config, DB connection etc)
include_once('www/includes/easyparliament/init.php');
include_once 'TWFY_Database_TestCase.php';
include_once 'FetchPageTestCase.php';

// Go!
