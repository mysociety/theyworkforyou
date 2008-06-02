<?php

include_once "../../includes/easyparliament/init.php";
include_once INCLUDESPATH . 'easyparliament/video.php';

$from = get_http_var('from');
$gid = get_http_var('gid');
$start = intval(get_http_var('start'));
$gid_safe = $gid;

if (!$gid) {
	$this_page = 'video_front';
	$PAGE->page_start();
	$PAGE->stripe_start();
?>

<style type="text/css">
#attract {
	background-color: #ccccff;
	text-align: center;
	padding: 10px;
	margin-left: 2em;
	border: solid 2px #9999ff;
	font-size: 150%;
	line-height: 1.4;
}
</style>

<p style="margin-top:1em"><big>TheyWorkForYou has video of the House of Commons from the BBC, and
the text of Hansard from Parliament. Now we need <strong>your</strong>
help to match up the two.</big></p>

<p>We've written a little Flash app where you can (hopefully) match up the written speech being displayed to
what's playing on the video. We'll then store your results and use them to put the video,
timestamped to the right location, on the relevant page of TheyWorkForYou.</p>

<p>If you're a registered user and logged in,
your timestampings will appear in our chart below &ndash; there may be prizes for best timestampers&hellip; :)
Registration is not needed to timestamp videos, but you can <a href="/user/?pg=join&ret=/video/">register here</a> if you want.</p>

<p id="attract"><a href="next.php?action=random">Give me a random speech that needs timestamping</a></p>

<div id="top" style="float: left; width: 45%;">
<?

function display_league($q = '') {
	$db = new ParlDB;
	$q = $db->query('select firstname,lastname,video_timestamps.user_id,count(*) as c from video_timestamps left join users on video_timestamps.user_id=users.user_id where video_timestamps.deleted=0 ' . $q . ' group by user_id order by c desc');
	$out = '';
	for ($i=0; $i<$q->rows(); $i++) {
		$name = $q->field($i, 'firstname') . ' ' . $q->field($i, 'lastname');
		$user_id = $q->field($i, 'user_id');
		if ($user_id == -1) continue; # $name = 'CaptionerBot';
		if ($user_id == 0) $name = 'Anonymous';
		$count = $q->field($i, 'c');
		$out .= "<li>$name : $count";
		#if ($user_id == -1) {
		#	echo ' <small>(initial run program that tries to guess timestamp from captions, wildly variable)</small>';
		#}
	}
	return $out;
}

$out = display_league('and date(whenstamped)=current_date');
if ($out) echo "<h3>Top timestampers (today)</h3> <ol>$out</ol>";
$out = display_league('and date(whenstamped)>current_date-interval 7 day');
if ($out) echo "<h3>Top timestampers (last week)</h3> <ol>$out</ol>";
$out = display_league();
if ($out) echo "<h3>Top timestampers (overall)</h3> <ol>$out</ol>";
echo '</div> <div style="float: right; width: 50%"><h3>Totaliser</h3> <ul>';

$statuses = array(
	0 => 'Unchecked video',
	4 => 'Timestamped by users',
);
$db = new ParlDB;
$q = $db->query('select video_status&4 as checked,count(*) as c from hansard
	where major=1 and video_status>0 and video_status!=2 and htype in (12,13) group by video_status&4');
for ($i=0; $i<$q->rows(); $i++) {
	$status = $q->field($i, 'checked');
	$count = $q->field($i, 'c');
	echo '<li>';
	echo $statuses[$status] . ' : ' . $count;
}

?>
</ul>

<h3 style="margin-top:1em">Latest stamped</h3>
<ul>
<?

$q = $db->query('select hansard.gid, body from video_timestamps, hansard, epobject
	where (user_id != -1 or user_id is null) and video_timestamps.deleted=0
		and video_timestamps.gid = hansard.gid and hansard.subsection_id = epobject.epobject_id
	order by whenstamped desc limit 10');
for ($i=0; $i<$q->rows(); $i++) {
	$gid = $q->field($i, 'gid');
	$body = $q->field($i, 'body');
	echo '<li><a href="/debate/?id=' . fix_gid_from_db($gid) . '">' . $body . '</a>';
}

?>
</ul>

</div>

<?
	$PAGE->stripe_end();
	$PAGE->page_end();
	exit;
}

$this_page = 'video_main';

$surrounding_speeches = 3;
if ($from == 'next') $surrounding_speeches = 2;

$gid = "uk.org.publicwhip/debate/$gid";

$q_gid = mysql_escape_string($gid);
$db = new ParlDB;
$q = $db->query("select hdate, htime, atime, hpos, video_status, (select h.gid from hansard as h where h.epobject_id=hansard.subsection_id) as parent_gid
    from hansard
    left join video_timestamps on hansard.gid = video_timestamps.gid and user_id = -1 and video_timestamps.deleted = 0
    where hansard.gid='$q_gid'");
if (!$q->rows()) {
	$PAGE->error_message('That GID does not appear to exist.', true);
	exit;
}
$video_status = $q->field(0, 'video_status');
$hpos = $q->field(0, 'hpos');
$hdate = $q->field(0, 'hdate');
$htime = $q->field(0, 'htime');
$atime = $q->field(0, 'atime');
if ($atime) $htime = $atime;
$parent_gid = str_replace('uk.org.publicwhip/debate/', '/debates/?id=', $q->field(0, 'parent_gid'));

if (!($video_status&1)) {
	$PAGE->error_message('That GID does not appear to have any video. Please visit the <a href="/video/">video front page</a>.', true);
	exit;
}

$q = $db->query("select hansard.gid, body, htype, htime, atime, hpos, first_name, last_name, video_status
	from hansard
		inner join epobject on hansard.epobject_id=epobject.epobject_id
		left join member on hansard.speaker_id=member.member_id
                left join video_timestamps on hansard.gid = video_timestamps.gid and user_id = -1 and video_timestamps.deleted = 0
	where hpos>=$hpos-$surrounding_speeches and hpos<=$hpos+$surrounding_speeches and hdate='$hdate' and major=1
	ORDER BY hpos
");
$gids_previous = array();
$gids_following = array();
$gid_actual = array();
for ($i=0; $i<$q->rows(); $i++) {
	$row = $q->row($i);
	if ($row['atime']) $row['htime'] = $row['atime'];
	if ($row['hpos'] < $hpos) {
		$gids_previous[] = $row;
	} elseif ($row['hpos'] > $hpos) {
		$gids_following[] = $row;
		
	} else {
		$gid_actual = $row;
	}
}

#if (strlen(strip_tags($gid_actual['body'])) > 500) {
#	$gid_actual['body_first'] = '<p>' . substr(strip_tags($gid_actual['body']), 0, 500) . '...';
#} else {
	#$gid_actual['body_first'] = $gid_actual['body'];
$gid_actual['body_first'] = preg_replace('#^(<p[^>]*>)([^<]{1,50}[^<\s]*)#s', '$1<strong><big>$2</big></strong>',
	preg_replace('#</?phrase[^>]*>#', '', $gid_actual['body']));
#}

$videodb = video_db_connect();
$video = video_from_timestamp($videodb, $hdate, $htime);

if (!$start)
    $start = $video['offset'] - 10;

$PAGE->page_start();
?>

<style type="text/css">
ul.otherspeeches {
    margin-top: 1em;
    font-size: 93%;
}
.unspoken {
    font-style: italic;
}
.heading {
    font-weight: bold;
}
h2,h3,h4 {
margin-left:0em;
}
#quote {
    margin: 1em;
    border-left: solid 5px #009900;
    padding-left: 0.5em;
}
</style>

<?

echo '<table style="margin-left: 1em;margin-top: 1em" border="0" cellspacing="0" cellpadding="5"><tr valign="top"><td width="50%">';

if (get_http_var('barcamp'))
	$video['id'] -= 4000;

print video_object($video['id'], $start, $gid_safe, 1);

#echo '<h4>Press &ldquo;Play&rdquo;, then click &ldquo;Now!&rdquo; when you hear:</h4>';
echo '<div id="quote">';
echo '<span style="border-bottom: solid 1px #666666;">' . $gid_actual['first_name'] . ' ' . $gid_actual['last_name'] . '</span> ';
echo $gid_actual['body_first'];
echo '</div>';

$last_prev = end($gids_previous);
if ($last_prev['htime'] == $gid_actual['htime']) {
	#echo "<p><small><em>This speech has the same timestamp as the previous speech, so might well be inaccurate.</em></small></p>";
}

echo '<h3 style="margin-top:1em">The speeches/headings immediately before</h3> <ol class="otherspeeches" start="-' . $surrounding_speeches . '">';
foreach ($gids_previous as $row) {
	disp_speech($row);
}

echo '</ul>';
echo '<p><a href="' . $parent_gid . '">View the entire debate</a></p>';
echo '</td><td>';
?>
<ol style="font-size:150%; border-bottom: dotted 1px #666666; margin-bottom: 0.5em">
<li>Have a quick scan of the speech under the video, then press &ldquo;Play&rdquo;.
<li>When you hear the start of that speech, press &ldquo;Now!&rdquo;.
<li>The timestamped video will then appear on TheyWorkForYou &ndash; thanks from
everyone who uses the site :)
</ol>

<ul style="font-size:150%; border-bottom: dotted 1px #666666; margin-bottom: 0.5em">
<li>Some videos will be <strong>miles</strong> out &ndash; if you can't
find the right point, don't worry, <a href="/video/next.php?action=random">try another one</a>!
</ul>

<ul>
<?	if (!$THEUSER->loggedin()) { ?>
<li><a href="/user/login/?ret=/video/">Log in</a> if you want to get on the <a href="/video/#top">Top Timestampers league table</a>!
<?	} ?>
<li>If the video suddenly jumps a couple of hours, or otherwise appears broken, <a href="mailto:team&#64;theyworkforyou.com?subject=Video%20<?=$video['id']?>'%20for%20ID%20'<?=$gid_safe?>'%20broken">let us know</a>.
<li>Hansard is not a verbatim transcript, so <strong>spoken words might
differ</strong> slightly from the printed version. And a small note &ndash; if
the speech you are looking out for is an oral question (questions asked in the
first hour or so of Monday&ndash;Thursdays in the Commons), then all the MP
will actually say is their question number, e.g.  &ldquo;Number Two&rdquo;.
</ul>

<p align="right"><small><b>Credits:</b> Video from <a href='http://www.bbc.co.uk/parliament/'>BBC Parliament</a> and mySociety</small></p>

<h3>Hints and Tips</h3>

<ul>
<li>Feel free to use the 30 second skip buttons (you can go
back before the current start point), and you can access a slider by hovering
over the video.
<li>Use the date-restricted search facility below to try and locate the speech currently playing:
</ul>

<iframe frameborder=0 style="border: dotted 1px black; margin-top:0.5em" name="video_person_search" width="95%" height="400" src="distance.php?gid=<?=$gid_safe?>"></iframe>

<?

echo '<!-- <h3>The ';
if ($gid_actual['body'] != $gid_actual['body_first']) {
	echo' whole';
}
echo ' speech you\'re looking out for</h3>';
echo $gid_actual['body'];

echo ' --> <!-- ';
echo '<h3 style="margin-top:1em">Following speeches/headings</h3> <ol class="otherspeeches">';

foreach ($gids_following as $row) {
	disp_speech($row);
}

?>
</ul>
-->

<?
echo '</td>';
echo '</tr></table>';

$PAGE->page_end();

function disp_speech($row) {
	echo '<li';
	if ($row['htype']==13) echo ' class="unspoken"';
	elseif ($row['htype']<12) echo ' class="heading"';
	echo '>';
	if ($row['htype']==12)
		echo '<span style="border-bottom: solid 1px #666666;">' . $row['first_name'] . ' ' . $row['last_name'] . '</span> ';
	echo $row['body'];
	echo '</li>';
}
