<?php

include_once "../../includes/easyparliament/init.php";

$action = get_http_var('action');
$pid = intval(get_http_var('pid'));

if ($action == 'next' || $action=='nextneeded') {
	$gid = get_http_var('gid');
	$file = intval(get_http_var('file'));
	$time = intval(get_http_var('time'));
	$db = new ParlDB;
	$gid = "uk.org.publicwhip/debate/$gid";
	$q_gid = mysql_escape_string($gid);
	$q = $db->query("select hdate,hpos from hansard where gid='$q_gid'");
	$hdate = $q->field(0, 'hdate');
	$hpos = $q->field(0, 'hpos');
	$q = $db->query("select gid from hansard
		where hpos>$hpos and hdate='$hdate' and major=1
		and (htype=12 or htype=13) "
		. ($action=='nextneeded'?'and video_status<4':'') . "
		ORDER BY hpos LIMIT 1");
	if (!$q->rows()) {
		$PAGE->page_start();
		$PAGE->stripe_start();
		print '<p>You appear to have reached the end of the day! Congratulations for getting this far, now
<a href="/video/">get stuck in somewhere else</a>! :-)</p>';
		$PAGE->stripe_end();
		$PAGE->page_end();
	} else {
		$new_gid = fix_gid_from_db($q->field(0, 'gid'));
		header('Location: /video/?from=next&file=' . $file . '&gid=' . $new_gid . '&start=' . $time);
	}
} elseif ($action == 'random' && $pid) {
	$db = new ParlDB;
	$q = $db->query("select gid from hansard, member
		where video_status in (1,3) and major=1
		and (htype=12 or htype=13)
		and hansard.speaker_id = member.member_id and person_id=$pid
		ORDER BY RAND() LIMIT 1");
	$new_gid = fix_gid_from_db($q->field(0, 'gid'));
	header('Location: /video/?from=random&pid=' . $pid . '&gid=' . $new_gid);
} elseif ($action == 'random') {
	$db = new ParlDB;
	$q = $db->query("select gid from hansard
		where video_status in (1,3) and major=1
		and (htype=12 or htype=13)
		ORDER BY RAND() LIMIT 1");
	$new_gid = fix_gid_from_db($q->field(0, 'gid'));
	header('Location: /video/?from=random&gid=' . $new_gid);
} else {
    # Illegal action
}
