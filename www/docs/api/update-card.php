<?php

include_once '../../includes/easyparliament/init.php';

if (!$THEUSER->loggedin()) {
    redirect('/api/');
}

$subscription = new MySociety\TheyWorkForYou\Subscription($THEUSER);
if (!$subscription->stripe) {
    redirect('/api/key');
}

MySociety\TheyWorkForYou\Utility\Session::start();
if (!Volnix\CSRF\CSRF::validate($_POST)) {
    print 'CSRF validation failure!';
    exit;
}

$token = get_http_var('stripeToken');
$sub = $subscription->stripe;
$sub->customer->source = $token;
$sub->customer->save();
redirect('/api/key?updated=1');
