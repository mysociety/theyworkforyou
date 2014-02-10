<?php

function video_db_connect() {
    $connstr = 'host='.OPTION_BBC_DB_HOST.' port='.OPTION_BBC_DB_PORT.' dbname='.OPTION_BBC_DB_NAME.' user='.OPTION_BBC_DB_USER.' password='.OPTION_BBC_DB_PASS;
    $videodb = pg_connect($connstr);

    return $videodb;
}

function video_from_timestamp($videodb, $date, $time) {
    if (!$videodb) return null;
    date_default_timezone_set('Europe/London');
    $epoch = strtotime("$date $time");
    $timestamp = gmdate('c', $epoch);
    $q = pg_query($videodb, "
    SELECT id, title, synopsis, broadcast_start, broadcast_end,
    extract('epoch' from '$timestamp' - broadcast_start) as offset
    FROM programmes
    WHERE broadcast_start <= '$timestamp' AND broadcast_end > '$timestamp'
        AND channel_id = 'BBCParl'
        AND status = 'available'
");
    $video = pg_fetch_array($q);

    return $video;
}

function video_object($video_id, $start, $gid, $stamping = '', $pid = 0) {
    $flashvars = "gid=$gid&amp;file=$video_id&amp;start=$start";
    if ($stamping) $flashvars .= '&amp;stamping=1';
    if ($pid) $flashvars .= '&amp;pid=' . $pid;
/*
<object width='360' height='300'
    classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000'
    codebase='http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0'>
<param name='movie' value='http://parlvid.mysociety.org/FLVScrubber3.swf'>
<param name='flashVars' value='file=<?=$video_id ?>&amp;previewImage=http://parlvid.mysociety.org/bbcparl-logo2.jpg&amp;secondsToHide=0&amp;startAt=0'>
<param name='allowFullScreen' value='true'>
<param name='allowScriptAccess' value='always'>
<embed src='http://parlvid.mysociety.org/FLVScrubber3.swf' width='360' height='300' allowfullscreen='true' allowscriptaccess='always' flashvars='file=<?=$video_id ?>&amp;previewImage=http://parlvid.mysociety.org/bbcparl-logo2.jpg&amp;secondsToHide=0&amp;startAt=221' type='application/x-shockwave-flash' pluginspage='http://www.adobe.com/go/getflashplayer'>
</object>
*/
    $out = "<div align='center'>
<object width='330' height='230' id='video'
    classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000'
    codebase='http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0'>
<param name='movie' value='/video/parlvid.swf'>
<param name='flashVars' value='$flashvars'>
<param name='allowFullScreen' value='true'>
<param name='allowScriptAccess' value='always'>
<embed name='video' swliveconnect='true' src='/video/parlvid.swf' width='330' height='230' allowfullscreen='true' allowscriptaccess='always' flashvars='$flashvars' type='application/x-shockwave-flash' pluginspage='http://www.adobe.com/go/getflashplayer'></embed>
</object>
</div>";

    return $out;
}
