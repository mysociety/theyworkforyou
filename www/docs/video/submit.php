<?php

include_once "../../includes/easyparliament/init.php";
include_once INCLUDESPATH . 'easyparliament/video.php';

$gid = get_http_var('gid');
$time = intval(get_http_var('time'));

$gid = "uk.org.publicwhip/debate/$gid";
$q_gid = mysql_escape_string($gid);

$db = new ParlDB;
$q = $db->query("select hdate, htime, atime from hansard left join video_timestamps on hansard.gid=video_timestamps.gid and user_id=-1 where hansard.gid='$q_gid'");
$hdate = $q->field(0, 'hdate');
$htime = $q->field(0, 'htime');
$atime = $q->field(0, 'atime');
if ($atime) $htime = $atime;

if (!$hdate || !$htime || !$time)
    exit;

$videodb = video_db_connect();
$video = video_from_timestamp($videodb, $hdate, $htime);

$time -= 3; # Let's start a few seconds earlier

$q = pg_query($videodb, "
    SELECT (broadcast_start + '$time seconds'::interval)::time AS new_time
    FROM programmes WHERE id=$video[id]
");
$b_end = pg_fetch_array($q);
$new_time = $b_end['new_time'];

# Adjust for timezone
date_default_timezone_set('Europe/London');
$epoch = strtotime("$hdate $new_time GMT");
$new_time = date('H:i:s', $epoch);

if ($THEUSER->isloggedin()) {
	$user_id = $THEUSER->user_id();
	$q = $db->query("replace into video_timestamps (gid, user_id, atime) values ('$q_gid', $user_id, '$new_time')");
} else {
	$q = $db->query("insert into video_timestamps (gid, atime) values ('$q_gid', '$new_time')");
}
$new_id = $q->insert_id();

$db->query("update hansard set video_status = video_status | 4 where gid='$q_gid'");

print "<id>$new_id</id>";

