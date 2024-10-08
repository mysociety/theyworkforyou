<?php

ini_set('error_reporting', E_ALL);
include_once '../../includes/easyparliament/init.php';
include_once './api_functions.php';

MySociety\TheyWorkForYou\Utility\Session::start();

if ($THEUSER->loggedin()) {
    $subscription = new MySociety\TheyWorkForYou\Subscription($THEUSER);
    if ($subscription->stripe) {
        $this_page = 'api_invoices';
        $PAGE->page_start();
        $PAGE->stripe_start();
        include_once INCLUDESPATH . 'easyparliament/templates/html/api/invoices.php';
        $sidebar = api_sidebar($subscription);
        $PAGE->stripe_end([$sidebar]);
        $PAGE->page_end();
        exit;
    }
}

# Otherwise, redirect to key page
header('Location: /api/key');
