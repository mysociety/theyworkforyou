<?php

include_once "../../includes/easyparliament/init.php";
include_once INCLUDESPATH . 'easyparliament/video.php';

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
	if (!$q->rows()) {
		# Shouldn't happen, but means a bot has got the URL somehow or similar
		header('Location: http://www.theyworkforyou.com/video/');
		exit;
	}
	$hdate = $q->field(0, 'hdate');
	$hpos = $q->field(0, 'hpos');
	$q = $db->query("select gid, hpos from hansard
		where hpos>$hpos and hdate='$hdate' and major=1
		and (htype=12 or htype=13) "
		. ($action=='nextneeded'?'and video_status in (1,3)':'') . "
		ORDER BY hpos LIMIT 1");
	if (!$q->rows()) {
		$PAGE->page_start();
		$PAGE->stripe_start();
		echo '<p>You appear to have reached the end of the day (or
everything after where you have just done has already been stamped).
Congratulations, now <a href="/video/">get stuck in somewhere else</a>!
;-)</p>';
		$PAGE->stripe_end();
		$PAGE->page_end();
	} else {
		$new_gid = $q->field(0, 'gid');
		$new_hpos = $q->field(0, 'hpos');
		if ($action=='nextneeded') {
			$q = $db->query("select atime from hansard, video_timestamps
				where hansard.gid = video_timestamps.gid and deleted=0
					and hpos<$new_hpos and hdate='$hdate' and major=1
					and (htype=12 or htype=13) and (user_id is null or user_id!=-1)
				order by hpos desc limit 1");
			$atime = $q->field(0, 'atime');
			$videodb = video_db_connect();
			$video = video_from_timestamp($videodb, $hdate, $atime);
			$file = $video['id'];
			$time = $video['offset'];
		}
		$new_gid = fix_gid_from_db($new_gid);
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
	$q = $db->query("select gid, hpos, hdate from hansard
		where video_status in (1,3) and major=1
		and (htype=12 or htype=13)
		ORDER BY RAND() LIMIT 1");
	$gid = $q->field(0, 'gid');
	/*
	$hpos = $q->field(0, 'hpos');
	$hdate = $q->field(0, 'hdate');
	# Look a few speeches back to see if any have been matched
	# Harder as need to check all since are /not/ done
	$q = $db->query("select gid from hansard
		where hpos<$hpos and hpos>=$hpos-5 and major=1 and hdate='$hdate'
		and htype in (12,13) and video_status in (5,7)
		order by hpos desc limit 1");
	if ($q->rows()) {
		# Take the next speech, presumably needed
		$hpos = $q->field(0, 'hpos');
		$q = $db->query("select gid from hansard
			where hpos>$hpos and major=1 and hdate='$hdate'
			and htype in (12,13)
			order by hpos limit 1");
		$gid = $q->field(0, 'gid');
	}
	*/
	$gid = fix_gid_from_db($gid);
	header('Location: /video/?from=random&gid=' . $gid);
} else {
    # Illegal action
}
