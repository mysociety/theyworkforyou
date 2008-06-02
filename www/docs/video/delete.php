<?php

include_once "../../includes/easyparliament/init.php";
include_once INCLUDESPATH . 'easyparliament/video.php';

$gid = get_http_var('gid');
$oops = get_http_var('oops');

if (!$oops || !$gid) exit;

$gid = "uk.org.publicwhip/debate/$gid";
$q_gid = mysql_escape_string($gid);
$q_oops = mysql_escape_string($oops);

$db = new ParlDB;
$q = $db->query("update video_timestamps set deleted=1 where id=$q_oops and gid='$q_gid' and current_timestamp<whenstamped+interval 30 second");
if ($q->affected_rows())
	$db->query("update hansard set video_status = video_status & 3  where gid='$q_gid'");

print "<status>OK</status>";

