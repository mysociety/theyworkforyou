<?php

include_once '../../includes/easyparliament/init.php';

$gid = get_http_var('gid');
$time = intval(get_http_var('time'));
$file = intval(get_http_var('file'));

$gid = "uk.org.publicwhip/$gid";

$db = new ParlDB;
$q = $db->query("select hdate, htime, adate, atime from hansard
    left join video_timestamps on hansard.gid=video_timestamps.gid and user_id=-1 and video_timestamps.deleted=0
    where hansard.gid = :gid", array(
        ':gid' => $gid
        ));
$hdate = $q->field(0, 'hdate');
$htime = $q->field(0, 'htime');
$atime = $q->field(0, 'atime');
$adate = $q->field(0, 'adate');
if ($atime) $htime = $atime;
if ($adate) $hdate = $adate;

if (!$hdate || !$htime || !$time)
    exit;

$videodb = \MySociety\TheyWorkForYou\Utility\Video::dbConnect();
if (!$file) {
    $video = \MySociety\TheyWorkForYou\Utility\Video::fromTimestamp($videodb, $hdate, $htime);
    $file = $video['id'];
}

$time -= 3; # Let's start a few seconds earlier

$q = pg_query($videodb, "
    SELECT (broadcast_start + '$time seconds'::interval)::date AS new_date,
           (broadcast_start + '$time seconds'::interval)::time AS new_time
    FROM programmes WHERE id=$file
");
$b_end = pg_fetch_array($q);
$new_date = $b_end['new_date'];
$new_time = $b_end['new_time'];

# Adjust for timezone
date_default_timezone_set('Europe/London');
$epoch = strtotime("$new_date $new_time GMT");
$new_date = date('Y-m-d', $epoch);
$new_time = date('H:i:s', $epoch);

if ($THEUSER->isloggedin()) {
    $user_id = $THEUSER->user_id();
    $q = $db->query("insert into video_timestamps (gid, user_id, adate, atime) values ('$q_gid', $user_id, '$new_date', '$new_time') on duplicate key update adate=VALUES(adate),atime=VALUES(atime),deleted=0");
} else {
    $q = $db->query("insert into video_timestamps (gid, adate, atime) values ('$q_gid', '$new_date', '$new_time')");
}
$new_id = $q->insert_id();

$db->query("update hansard set video_status = video_status | 4 where gid='$q_gid'");

print "<id>$new_id</id>";
