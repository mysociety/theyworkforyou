<?php

include_once '../../includes/easyparliament/init.php';
include_once './api_functions.php';
include_once INCLUDESPATH . '../../commonlib/phplib/auth.php';

MySociety\TheyWorkForYou\Utility\Session::start();

$this_page = 'api_key';
$PAGE->page_start();
$PAGE->stripe_start();

if ($THEUSER->loggedin()) {
    if (get_http_var('create_key')) {
        if (!Volnix\CSRF\CSRF::validate($_POST)) {
            print 'CSRF validation failure!';
            exit;
        }
        create_key($THEUSER);
    }

    if (get_http_var('updated')) {
        print '<p class="alert-box">Thanks very much!</p>';
        if (has_no_keys($THEUSER)) {
            create_key($THEUSER);
        }
    }
    if (get_http_var('cancelled')) {
        print '<p class="alert-box">Your subscription has been cancelled.</p>';
    }

    $subscription = new MySociety\TheyWorkForYou\Subscription($THEUSER);
    $errors = [];

    if ($subscription->stripe) {
        include_once INCLUDESPATH . 'easyparliament/templates/html/api/subscription_detail.php';
    } else {
        include_once INCLUDESPATH . 'easyparliament/templates/html/api/update.php';
    }

    $keys = get_keys($THEUSER);
    if ($keys) {
        list_keys($keys);
    }
    if ($subscription->stripe) {
        include_once INCLUDESPATH . 'easyparliament/templates/html/api/key-form.php';
    }
} else {
    logged_out();
}

$sidebar = api_sidebar();
$PAGE->stripe_end(array($sidebar));
$PAGE->page_end();

# ---

function logged_out() {
    echo 'Your plan and key are tied to your TheyWorkForYou account,
so if you don’t yet have one, please <a href="/user/?pg=join&amp;ret=/api/key">sign up</a>, then
return here to get a key.</p>';
    echo '<p style="font-size:200%"><strong><a href="/user/login/?ret=/api/key">Sign in</a></strong> (or <a href="/user/?pg=join&amp;ret=/api/key">sign up</a>) to get an API key.</p>';
}

function has_no_keys($user) {
    $db = new ParlDB;
    $q = $db->query('SELECT COUNT(*) as count FROM api_key WHERE user_id=' . $user->user_id());
    return $q->field(0, 'count') ? false : true;
}

function get_keys($user) {
    $db = new ParlDB;
    $q = $db->query('SELECT api_key, created, reason FROM api_key WHERE user_id=' . $user->user_id());
    $keys = [];
    foreach ($q as $row) {
        $keys[] = [$row['api_key'], $row['created'], $row['reason']];
    }
    return $keys;
}

function list_keys($keys) {
    $db = new ParlDB;
    echo '<h2>Your keys</h2> <ul>';
    foreach ($keys as $keyarr) {
        list($key, $created, $reason) = $keyarr;
        echo '<li><span style="font-size:200%">' . $key . '</span><br><span style="color: #666666;">';
        echo 'Key created ', $created, '; ', $reason;
        echo '</span><br><em>Usage statistics</em>: ';
        $q = $db->query('SELECT count(*) as count FROM api_stats WHERE api_key="' . $key . '" AND query_time > NOW() - interval 1 day');
        $c = $q->field(0, 'count');
        echo "last 24 hours: $c, ";
        $q = $db->query('SELECT count(*) as count FROM api_stats WHERE api_key="' . $key . '" AND query_time > NOW() - interval 1 week');
        $c = $q->field(0, 'count');
        echo "last week: $c, ";
        $q = $db->query('SELECT count(*) as count FROM api_stats WHERE api_key="' . $key . '" AND query_time > NOW() - interval 1 month');
        $c = $q->field(0, 'count');
        echo "last month: $c";
        echo '</p>';
    }
    echo '</ul>';
}

function create_key($user) {
    $key = auth_ab64_encode(urandom_bytes(16));
    $db = new ParlDB;
    $db->query('INSERT INTO api_key (user_id, api_key, commercial, created, reason, estimated_usage) VALUES
        (:user_id, :key, -1, NOW(), :reason, -1)', [
        ':user_id' => $user->user_id(),
        ':key' => $key,
        ':reason' => '',
        ]);
    $r = new \MySociety\TheyWorkForYou\Redis();
    $r->set("key:$key:api:" . REDIS_API_NAME, $user->user_id());
}
