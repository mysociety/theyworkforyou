<?php

include_once "../../includes/easyparliament/init.php";

$action = get_http_var('action');
$pid = intval(get_http_var('pid'));

if ($action == 'next') {
	$gid = get_http_var('gid');
	$time = intval(get_http_var('time'));
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
} elseif ($action == 'random' && $pid) {
	$db = new ParlDB;
	$q = $db->query("select gid from hansard, member
		where video_status in (1,3) and major=1
		and (htype=12 or htype=13)
		and hansard.speaker_id = member.member_id and person_id=$pid
		ORDER BY RAND() LIMIT 1");
	$new_gid = fix_gid_from_db($q->field(0, 'gid'));
	header('Location: /video/?from=random&pid=' . $pid . '&gid=' . $new_gid);
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
