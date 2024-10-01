<?php

include_once '../../includes/easyparliament/init.php';
include_once './api_functions.php';

if (!$THEUSER->loggedin()) {
    redirect('/api/');
}

$subscription = new MySociety\TheyWorkForYou\Subscription($THEUSER);
$errors = [];

MySociety\TheyWorkForYou\Utility\Session::start();
if (get_http_var('plan')) {
    if (!Volnix\CSRF\CSRF::validate($_POST)) {
        print 'CSRF validation failure!';
        exit;
    }

    $errors = $subscription->checkForErrors();
    if (!$errors) {
        $errors = $subscription->createOrUpdateFromForm();
        if (!$errors) {
            redirect('/api/key?updated=1');
        }
    }
}

$this_page = 'api_key';
$PAGE->page_start();
$PAGE->stripe_start();

include_once INCLUDESPATH . 'easyparliament/templates/html/api/update.php';

$sidebar = api_sidebar($subscription);
$PAGE->stripe_end([$sidebar]);
$PAGE->page_end();
