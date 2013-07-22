<?php

// TWFY Test Bootstrapper

// Load up the config
include_once dirname(__FILE__) . '/../conf/general';

// Make sure we're running on a staging server, or else don't do *any* of this.
if (DEVSITE !== 1) {
    die('This isn\'t a development site. Aborting in case you\'re accidentally running tests on production.');
}

// Load up the init script (handles the rest of the config, DB connection etc)
include_once('www/includes/easyparliament/init.php');

// Go!