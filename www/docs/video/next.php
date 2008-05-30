<?php

include_once "../../includes/easyparliament/init.php";

$gid = get_http_var('gid');
$time = intval(get_http_var('time'));
$action = get_http_var('action');

if ($action == 'next') {
	$db = new ParlDB;
	$gid = "uk.org.publicwhip/debate/$gid";
	$q_gid = mysql_escape_string($gid);
	$q = $db->query("select hdate,hpos from hansard where gid='$q_gid'");
	$hdate = $q->field(0, 'hdate');
	$hpos = $q->field(0, 'hpos');
	$q = $db->query("select gid from hansard
		where hpos>$hpos and hdate='$hdate' and major=1
		and (htype=12 or htype=13)
		ORDER BY hpos LIMIT 1");
	$new_gid = fix_gid_from_db($q->field(0, 'gid'));
	header('Location: /video/?from=next&gid=' . $new_gid . '&start=' . $time);
	exit;
} elseif ($action == 'random') {
	$db = new ParlDB;
	$q = $db->query("select gid from hansard
		where video_status in (1,3) and major=1
		and (htype=12 or htype=13)
		ORDER BY RAND() LIMIT 1");
	$new_gid = fix_gid_from_db($q->field(0, 'gid'));
	header('Location: /video/?from=random&gid=' . $new_gid);
	exit;
} else {
    # Illegal action
}
