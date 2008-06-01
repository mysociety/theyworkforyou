<?php

include_once "../www/includes/easyparliament/init.php";
include_once INCLUDESPATH . 'easyparliament/video.php';

date_default_timezone_set('Europe/London');

$db = new ParlDB;
$videodb = video_db_connect();
$q = pg_query($videodb, "
    SELECT id, extract(epoch from broadcast_start) as start,
    extract(epoch from broadcast_end) as end
    FROM programmes
    WHERE channel_id = 'BBCParl' AND location = 'commons' AND status = 'available' 
    ORDER BY id
");
while ($row = pg_fetch_array($q)) {
    $start = $row['start'];
    $end = $row['end'];
    $start_date = date('Y-m-d', $start);
    $start_time = date('H:i:s', $start);
    $end_date = date('Y-m-d', $end);
    $end_time = date('H:i:s', $end);
    if ($start_date == $end_date) {
        $db->query("update hansard set video_status = video_status | 1
	    where hdate='$start_date' and htime>='$start_time' and htime<'$end_time' and major=1");
    } else {
        $db->query("update hansard set video_status = video_status | 1
	    where ((hdate='$start_date' and htime>='$start_time')
	       or (hdate='$end_date' and htime<'$end_time')) and major=1");
    }
}

