<?php

include_once '../../includes/easyparliament/init.php';

$token = $_POST['token'];
if ($token != SLACK_POST_TOKEN) {
    header('HTTP/1.0 400 Bad Request');
    exit;
}

$root = '/data/vhost/www.theyworkforyou.com';
$script = "php $root/theyworkforyou/scripts/alertmailer.php";
$lock = "$root/email-alerts.lock";
$log = "$root/applogs/email-alerts.log";
system("run-with-lockfile -n $lock '$script' >$log 2>&1 &");

header('Content-Type: application/json');
print '{"text":"Alerts started"}';
