<?php

include_once "../../includes/easyparliament/init.php";
include_once INCLUDESPATH . 'easyparliament/video.php';

date_default_timezone_set('Europe/London');
$videodb = video_db_connect();

$gid = get_http_var('gid');
$q_gid = mysql_escape_string("uk.org.publicwhip/debate/$gid");

$db = new ParlDB;
$q = $db->query("select subsection_id,hdate,atime from hansard, video_timestamps
	where hansard.gid = video_timestamps.gid and hansard.gid='$q_gid'
		and deleted=0 and (user_id is null or user_id!=-1)");
$subsection_id = $q->field(0, 'subsection_id');
$hdate = $q->field(0, 'hdate');
$atime = $q->field(0, 'atime');
$video = video_from_timestamp($videodb, $hdate, $atime);
if (!$video) exit;

$start = date('H:i:s', strtotime($video['broadcast_start']. ' GMT'));
$end = date('H:i:s', strtotime($video['broadcast_end'] . ' GMT'));

$q = $db->query("select video_timestamps.gid, atime, time_to_sec(timediff(atime, '$start')) as timediff,
		time_to_sec(timediff(atime, '$end')) as timetoend
	from hansard, video_timestamps
	where hansard.gid = video_timestamps.gid and subsection_id=$subsection_id
	and (user_id is null or user_id!=-1) and deleted=0 order by hpos");

header('Content-Type: text/xml');

$gids = array();
$file_offset = 0;
print '<stamps>';
for ($i=0; $i<$q->rows(); $i++) {
	$gid = fix_gid_from_db($q->field($i, 'gid'));
	if (isset($gids[$gid])) continue;
	$timetoend = $q->field($i, 'timetoend') - $file_offset;
	if ($timetoend>0) {
		$video = video_from_timestamp($videodb, $hdate, $q->field($i, 'atime'));
		$new_start = date('H:i:s', strtotime($video['broadcast_start']. ' GMT'));
		$file_offset += timediff($new_start, $start);
		$start = $new_start;
		$end = date('H:i:s', strtotime($video['broadcast_end'] . ' GMT'));
	}
	$timediff = $q->field($i, 'timediff') - $file_offset;
	if ($timediff>=0)
		print "<stamp><gid>$gid</gid><file>$video[id]</file><time>$timediff</time></stamp>\n";
	$gids[$gid] = true;
}
print '</stamps>';

function timediff($a, $b) {
    return substr($a, 0, 2)*3600 + substr($a, 3, 2)*60 + substr($a, 6, 2)
        - substr($b, 0, 2)*3600 - substr($b, 3, 2)*60 - substr($b, 6, 2);

}
