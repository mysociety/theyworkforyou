<?php

// TWFY Test Bootstrapper

// Test to make sure we have the test DB environment variables. If not, this isn't testing, so abort.
// Define the DB connection constants before we do anything else.
if (
    getenv('TWFY_TEST_DB_HOST') AND
    getenv('TWFY_TEST_DB_USER') AND
    getenv('TWFY_TEST_DB_PASS') AND
    getenv('TWFY_TEST_DB_NAME')
) {

    // Define the DB variables before init.php tries
    define('OPTION_TWFY_DB_HOST', getenv('TWFY_TEST_DB_HOST'));
    define('OPTION_TWFY_DB_USER', getenv('TWFY_TEST_DB_USER'));
    define('OPTION_TWFY_DB_PASS', getenv('TWFY_TEST_DB_PASS'));
    define('OPTION_TWFY_DB_NAME', getenv('TWFY_TEST_DB_NAME'));

} else {
    echo 'Testing environment variables not set. This will cause bad things to happen if testing happens on production. Aborting.';
    exit(1);
}

// Load up the init script (handles the rest of the config, DB connection etc)
include_once('www/includes/easyparliament/init.php');

// Go!