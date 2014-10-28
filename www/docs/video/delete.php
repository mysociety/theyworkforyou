<?php

include_once '../../includes/easyparliament/init.php';

$gid = get_http_var('gid');
$oops = get_http_var('oops');

if (!$oops || !$gid) exit;

$gid = "uk.org.publicwhip/$gid";

$params = array(
    ':gid' => $gid,
    ':oops' => $oops
);

$db = new \MySociety\TheyWorkForYou\ParlDb;
$q = $db->query("update video_timestamps set deleted=1 where id = :oops and gid = :gid and current_timestamp<whenstamped+interval 30 second", $params);
if ($q->affected_rows())
    $db->query("update hansard set video_status = video_status & 11 where gid = :gid", $params);

print "<status>OK</status>";
