<?php

include_once dirname(__FILE__) . '/../www/includes/easyparliament/init.php';


// create the cohorts table
MySociety\TheyWorkForYou\PartyCohort::populateCohorts();
MySociety\TheyWorkForYou\PartyCohort::calculatePositions();


print "cached positions for $cohort_count party cohorts\n";