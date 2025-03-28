<?php

include_once '../../includes/easyparliament/init.php';

if (!$THEUSER->loggedin()) {
    redirect('/api/');
}

$subscription = new MySociety\TheyWorkForYou\Subscription($THEUSER);
if (!$subscription->stripe) {
    redirect('/api/key');
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $setup_intent = $subscription->api->client->setupIntents->create([
        'automatic_payment_methods' => ["enabled" => true, "allow_redirects" => "never"],
    ]);
    header('Content-Type: application/json');
    print json_encode([
        'secret' => $setup_intent->client_secret,
    ]);
    exit;
}

MySociety\TheyWorkForYou\Utility\Session::start();
if (!Volnix\CSRF\CSRF::validate($_POST)) {
    print 'CSRF validation failure!';
    exit;
}

$payment_method = get_http_var('payment_method');
$subscription->update_payment_method($payment_method);
redirect('/api/key?updated=1');
