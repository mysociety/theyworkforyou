<?php

include_once "../www/includes/easyparliament/init.php";

$db = new \MySociety\TheyWorkForYou\ParlDb;
$q = $db->query('select hansard.gid,time_to_sec(atime) as atime,hdate,hpos
	from hansard, video_timestamps
	where hansard.gid = video_timestamps.gid
		and (user_id is null or user_id != -1) and deleted=0
	order by hdate, hpos');
$last = array('hdate'=>'');
for ($i=0; $i<$q->rows(); $i++) {
	$row = array(
		'atime' => $q->field($i, 'atime'),
		'hdate' => $q->field($i, 'hdate'),
		'gid' => $q->field($i, 'gid'),
		'hpos' => $q->field($i, 'hpos')
	);
	if ($row['hdate'] != $last['hdate'])
		$last = array();
	if ($last && $row['atime'] < $last['atime']-5) {
		print "PROBLEM; current row is $row[gid] $row[hdate] $row[atime]\n";
		print "  Last was $last[gid] $last[hdate] $last[atime]\n";
	}
	$last = $row;
}

