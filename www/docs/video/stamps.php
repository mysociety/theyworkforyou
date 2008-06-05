<?php

include_once "../../includes/easyparliament/init.php";
include_once INCLUDESPATH . 'easyparliament/video.php';

date_default_timezone_set('Europe/London');
$videodb = video_db_connect();

$file = intval(get_http_var('file'));
$gid = get_http_var('gid');
$q_gid = mysql_escape_string("uk.org.publicwhip/debate/$gid");

$q = pg_query($videodb, "
	SELECT extract(epoch from broadcast_start) as bs
	FROM programmes
	WHERE id = $file");
$video = pg_fetch_array($q);
if (!$video) exit;
$start = date('H:i:s', strtotime(date('Y-m-d H:i:s \G\M\T', $video['bs'])));

$db = new ParlDB;
$q = $db->query("select subsection_id from hansard where gid='$q_gid'");
$subsection_id = $q->field(0, 'subsection_id');

$q = $db->query("select video_timestamps.gid,time_to_sec(timediff(atime, '$start')) as timediff
	from hansard, video_timestamps
	where hansard.gid = video_timestamps.gid and subsection_id=$subsection_id
	and (user_id is null or user_id!=-1) and deleted=0 order by hpos");

header('Content-Type: text/xml');

$gids = array();
print '<stamps>';
for ($i=0; $i<$q->rows(); $i++) {
	$gid = fix_gid_from_db($q->field($i, 'gid'));
	if (isset($gids[$gid])) continue;
	$timediff = $q->field($i, 'timediff');
	if ($timediff>=0)
		print "<stamp><gid>$gid</gid><time>$timediff</time></stamp>\n";
	$gids[$gid] = true;
}
print '</stamps>';
