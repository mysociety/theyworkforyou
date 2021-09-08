<?php

include_once dirname(__FILE__) . '/../www/includes/easyparliament/init.php';

// create the cohorts table
MySociety\TheyWorkForYou\PartyCohort::populateCohorts();

// get current hashes available
$cohorts = MySociety\TheyWorkForYou\PartyCohort::getCohorts();
$policies = new MySociety\TheyWorkForYou\Policies;
$n_cohorts = count($cohorts);

// iterate through all hashes and create policy positions
$cohort_count = 0;
foreach ( $cohorts as $cohort ) {

    $cohort = new MySociety\TheyWorkForYou\PartyCohort($cohort, TRUE);

    $positions = $cohort->calculateAllPolicyPositions($policies);

    $cohort_count++;

    foreach ( $positions as $position ) {
        $cohort->cache_position( $position );
    }

    print("$cohort_count/$n_cohorts\n");
}

print "cached positions for $cohort_count party cohorts\n";