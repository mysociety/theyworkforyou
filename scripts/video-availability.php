<?php

include_once "../www/includes/easyparliament/init.php";

$db = new \MySociety\TheyWorkForYou\ParlDb;
$videodb = \MySociety\TheyWorkForYou\Utility\Video::dbConnect();
$q = pg_query($videodb, "
    SELECT id, broadcast_start, broadcast_end
    FROM programmes
    WHERE channel_id = 'BBCParl' AND status = 'available'
    ORDER BY id
");
while ($row = pg_fetch_array($q)) {
    date_default_timezone_set('GMT');
    $start = strtotime($row['broadcast_start']);
    $end = strtotime($row['broadcast_end']);
    date_default_timezone_set('Europe/London');
    $start_date = date('Y-m-d', $start);
    $start_time = date('H:i:s', $start);
    $end_date = date('Y-m-d', $end);
    $end_time = date('H:i:s', $end);
    if ($start_date == $end_date) {
        $qq = $db->query("update hansard set video_status = video_status | 1
	    where hdate='$start_date' and htime>='$start_time' and htime<'$end_time' and major=1");
    } else {
        $qq = $db->query("update hansard set video_status = video_status | 1
	    where ((hdate='$start_date' and htime>='$start_time')
	       or (hdate='$end_date' and htime<'$end_time')) and major=1");
    }
    #print "$start - $end : " . $qq->affected_rows() . "\n";
}

