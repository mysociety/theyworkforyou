<?php

// TWFY Test Bootstrapper

// Load up the config
include_once dirname(__FILE__) . '/../conf/general';

// Make sure we're running on a staging server or Travis, or else we probably shouldn't be testing (bad ju
if (DEVSITE !== 1) {
    echo 'This isn\'t a development site. Aborting in case you\'re accidentally running tests on production.';
    exit(1);
}

// Load up the init script (handles the rest of the config, DB connection etc)
include_once('www/includes/easyparliament/init.php');

// Go!