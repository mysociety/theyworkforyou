<?php

// TWFY Test Bootstrapper

// Test to make sure we have the test DB environment variables. If not, this isn't testing, so abort.
// Define the DB connection constants before we do anything else.
if (
    isset($_SERVER['TWFY_TEST_DB_HOST']) AND
    isset($_SERVER['TWFY_TEST_DB_USER']) AND
    isset($_SERVER['TWFY_TEST_DB_PASS']) AND
    isset($_SERVER['TWFY_TEST_DB_NAME'])
) {

    // Define the DB variables before init.php tries
    define('OPTION_TWFY_DB_HOST', $_SERVER['TWFY_TEST_DB_HOST']);
    define('OPTION_TWFY_DB_USER', $_SERVER['TWFY_TEST_DB_USER']);
    define('OPTION_TWFY_DB_PASS', $_SERVER['TWFY_TEST_DB_PASS']);
    define('OPTION_TWFY_DB_NAME', $_SERVER['TWFY_TEST_DB_NAME']);

} else {
    echo 'Testing environment variables not set. This will cause bad things to happen if testing happens on production. Aborting.';
    exit(1);
}

// Explicitly declare we're in testing (avoids trying deploy-only things)
define('TESTING', TRUE);

// Specify bits of configuration that would normally be dealt with by deployment
define ("WEBPATH", "/");
define ("DEVSITE", 1);
define ("DEBUGTAG", 'twfy_debug');
define ("TIMEZONE", "Europe/London");
define ("BASEDIR", dirname(__FILE__) . '/../www/docs');
define ("INCLUDESPATH", BASEDIR . "/../includes/");
define ("IMAGEPATH", WEBPATH . "images/");
define ("METADATAPATH", BASEDIR . "/../includes/easyparliament/metadata.php");

// Load up the init script (handles the rest of the config, DB connection etc)
include_once('www/includes/easyparliament/init.php');

// Go!