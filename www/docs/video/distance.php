<?php

include_once "../../includes/easyparliament/init.php";
include_once INCLUDESPATH . 'easyparliament/video.php';

$file = intval(get_http_var('file'));
$gid = get_http_var('gid');
preg_match('#^(\d\d\d\d)-(\d\d)-(\d\d)#', $gid, $m);
$gid_date = "$m[1]$m[2]$m[3]";

global $gid_want, $file_want; # It's in the template, yucky, sorry
$gid_want = $gid;
$file_want = $file;

if ($at = get_http_var('at')) {
	$dist = speeches_distance($gid, $at);
	echo '<p>You are watching a speech <strong>';
	if ($dist > 0) {
		echo $dist . ' speeches/headings ahead</strong> of the speech you want.';
	} else {
		echo (-$dist) . ' speeches/headings behind</strong> the speech you want.';
	} 
	echo ' <a target="_top" onclick="t = parent.document[\'video\'].currentTime(); this.href += t;" href="/video/?gid=' . $at . '&amp;file=' . $file . '&amp;start=">Match this speech instead</a></p>';

	echo search_box($file);
} elseif ($search = get_http_var('s')) {
	search_box($file);
	day_speeches($search, $gid_date);
} else { ?>
<p>If the playing speech appears to be in completely the wrong place,
enter the speaking MP's name or something they're saying in this search box
to search the day's speeches. Pick the correct speech and we'll tell you how
many speeches out the video is &ndash; you can then either reposition the video
or follow the link to switch to matching that speech instead.</p>
<?
	search_box($file);
}

function search_box($file) {
	global $gid_want;
?>
<form action="distance.php" method="get" target="video_person_search">
<label for="vid_search">MP name or spoken word(s):</label>
<input id="vid_search" type="text" name="s" value="">
<input type="hidden" name="gid" value="<?=$gid_want?>">
<input type="hidden" name="file" value="<?=$file?>">
<input type="submit" value="Search">
</form>
<?
}

function speeches_distance($want, $at) {
	$want = "uk.org.publicwhip/debate/$want";
	$at = "uk.org.publicwhip/debate/$at";
	$q_want = mysql_escape_string($want);
	$q_at = mysql_escape_string($at);
	$db = new ParlDB;
	$q = $db->query("select gid, hdate, hpos from hansard where gid='$q_at' or gid='$q_want'");
	for ($i=0; $i<$q->rows(); $i++) {
		$r_gid = $q->field($i, 'gid');
		$hdate = $q->field($i, 'hdate');
		$hpos = $q->field($i, 'hpos');
		if ($r_gid == $want) {
			$want = array($r_gid, $hdate, $hpos);
		} else {
			$at = array($r_gid, $hdate, $hpos);
		}
	}

	if ($want[1] != $at[1])
		print 'ERROR: Different days';

	return $at[2]-$want[2];
}

function day_speeches($search, $date) {
	$search = "$search date:$date section:debates groupby:speech";

	global $SEARCHENGINE;
	$SEARCHENGINE = new SEARCHENGINE($search);

	$LIST = new DEBATELIST;
    	$args = array (
    		's' => $search,
    		'p' => 1,
    		'num' => 200,
		'pop' => 1,
		'o' => 'd',
    	);
	$LIST->display('search_video', $args, 'html');
}

