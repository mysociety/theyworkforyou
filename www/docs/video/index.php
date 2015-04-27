<?php

include_once '../../includes/easyparliament/init.php';
include_once INCLUDESPATH . 'easyparliament/video.php';

$offset = 10;

$from = get_http_var('from');
$gid = get_http_var('gid');
$start = intval(get_http_var('start'));
$file = intval(get_http_var('file'));
$pid = intval(get_http_var('pid'));
$gid_safe = _htmlspecialchars($gid);
if (!$gid) {
    $this_page = 'video_front';
    $PAGE->page_start();
    $PAGE->stripe_start();
    video_front_page();
    $PAGE->stripe_end();
    $PAGE->page_end();
    exit;
}

$this_page = 'video_main';
$surrounding_speeches = 3;
# if ($from == 'next') $surrounding_speeches = 2;

$gid = "uk.org.publicwhip/$gid";

# Fetch this GID from the database, and captioner bot time if there is one
$db = new ParlDB;
$q = $db->query("select hdate, htime, adate, atime, hpos, video_status, subsection_id, major,
    (select h.gid from hansard as h where h.epobject_id=hansard.subsection_id) as parent_gid,
    (select body from epobject as e where e.epobject_id=hansard.subsection_id) as parent_body
    from hansard
    left join video_timestamps on hansard.gid = video_timestamps.gid and user_id = -1 and video_timestamps.deleted = 0
    where hansard.gid = :gid", array(
        ':gid' => $gid
        ));
if (!$q->rows()) {
    $PAGE->error_message('That GID does not appear to exist.', true, 404);
    exit;
}
$video_status = $q->field(0, 'video_status');
$major = $q->field(0, 'major');
$hpos = $q->field(0, 'hpos');
$hdate = $q->field(0, 'hdate');
$htime = $q->field(0, 'htime');
$atime = $q->field(0, 'atime');
$adate = $q->field(0, 'adate');
if ($atime) $htime = $atime;
if ($adate) $hdate = $adate;
$parent_gid = str_replace('uk.org.publicwhip/debate/', '/debates/?id=', $q->field(0, 'parent_gid'));
$parent_gid = str_replace('uk.org.publicwhip/lords/', '/lords/?gid=', $parent_gid);
$parent_body = $q->field(0, 'parent_body');
$parent_epid = $q->field(0, 'subsection_id');

if (!($video_status&1) || ($video_status&8)) {
    $PAGE->error_message('That GID does not appear to have any video. Please visit the <a href="/video/">video front page</a>.', true);
    exit;
}

# See if we can get a more accurate timestamp
$q = $db->query("select adate,atime from video_timestamps, hansard
    where video_timestamps.gid = hansard.gid
        and (user_id is null or user_id != -1) and deleted = 0
        and hdate='$hdate' and hpos<$hpos and major=$major
    order by hpos desc limit 1");
if ($q->rows()) {
    $adate = $q->field(0, 'adate');
    $atime = $q->field(0, 'atime');
    if ($atime > $htime) {
        $htime = $atime;
        $offset = 0;
    }
}

# Fetch preceding/following speeches data *
$q = $db->query("select hansard.gid, body, htype, htime, adate, atime, hpos, first_name, last_name, video_status
    from hansard
        inner join epobject on hansard.epobject_id=epobject.epobject_id
        left join member on hansard.person_id=member.person_id AND member.entered_house <= hansard.hdate AND hansard.hdate <= member.left_house AND member.house = 1
                left join video_timestamps on hansard.gid = video_timestamps.gid and user_id = -1 and video_timestamps.deleted = 0
    where hpos>=$hpos-$surrounding_speeches and hpos<=$hpos+$surrounding_speeches and hdate='$hdate' and major=$major
    ORDER BY hpos desc
");
$gids_previous = array();
$gids_following = array();
$gid_actual = array();
for ($i=0; $i<$q->rows(); $i++) {
    $row = $q->row($i);
    if ($row['adate']) $row['hdate'] = $row['adate'];
    if ($row['atime']) $row['htime'] = $row['atime'];
    if ($row['hpos'] < $hpos) {
        $gids_previous[] = $row;
    } elseif ($row['hpos'] > $hpos) {
        $gids_following[] = $row;

    } else {
        $gid_actual = $row;
    }
}

# Summary of debate
/*
$q = $db->query("select hansard.gid, body, htype, hpos, first_name, last_name
    from hansard
        inner join epobject on hansard.epobject_id=epobject.epobject_id
        left join member on hansard.person_id=member.person_id XXX
    where subsection_id = $parent_epid
    ORDER BY hpos
");
$summary = '';
for ($i=0; $i<$q->rows(); $i++) {
    $row = $q->row($i);
    $count = count(explode(' ', $row['body']));
    $summary .= '<li>';
$row[first_name] $row[last_name] : $count words";
}
*/

#if (strlen(strip_tags($gid_actual['body'])) > 500) {
#	$gid_actual['body_first'] = '<p>' . substr(strip_tags($gid_actual['body']), 0, 500) . '...';
#} else {
    #$gid_actual['body_first'] = $gid_actual['body'];
$gid_actual['body_first'] = preg_replace('#^(<p[^>]*>)([^<]{1,50}[^<\s]*)#s', '$1<strong><big>$2</big></strong>',
    preg_replace('#</?phrase[^>]*>#', '', $gid_actual['body']));
#}

# Work out what video we want, and where in it
$videodb = video_db_connect();
if (!$videodb) {
    $PAGE->error_message('We appear to be having problems connecting to the database of video timings. Sorry, and please try again later.', true);
    exit;
}
$video = video_from_timestamp($videodb, $hdate, $htime);

if (!$start)
    $start = $video['offset'] - $offset;
if ($start < 0) $start = 0;

if (get_http_var('barcamp'))
    $video['id'] -= 4000;

if (!$file) $file = $video['id'];

# Start displaying

/*
if (get_http_var('adv')) {
    $PAGE->page_start();
    echo '<table id="video_table" border="0" cellspacing="0" cellpadding="5"><tr valign="top"><td width="50%">';
    print video_object($file, $start, $gid_safe, 1, $pid);
    video_quote($gid_actual, $parent_gid, $parent_body);
    iframe_search($gid_safe, $file);
    echo '</td><td>';
    advanced_hints($gid_safe, $file, $pid);
    previous_speeches($surrounding_speeches, $gids_previous);
    echo '</td>';
    echo '</tr></table>';
    $PAGE->page_end();
} else {
*/
$PAGE->page_start();
?>
<script type="text/javascript">
function hideInstructions() {
    document.getElementById('advanced_hints').style.display='block';
    document.getElementById('basic_hints').style.display='none';
    document.cookie = 'hideVideoInt=1';
    return false;
}
function showInstructions() {
    document.getElementById('basic_hints').style.display='block';
    document.getElementById('advanced_hints').style.display='none';
    document.cookie = 'hideVideoInt=0';
    return false;
}
</script>
<?php
    $hidden_int = (isset($_COOKIE['hideVideoInt']) && $_COOKIE['hideVideoInt']);
    echo '<table id="video_table" border="0" cellspacing="0" cellpadding="5"><tr valign="top"><td width="50%">';
    if ($gid_actual['video_status']&4) {
        $q = $db->query("select timediff(current_timestamp,max(whenstamped)) as ws from video_timestamps where gid='$q_gid' and (user_id is null or user_id != -1) and deleted=0");
        $max = $q->field(0, 'ws');
        echo '<p class="informational">Thanks, but this speech has <strong>already been stamped</strong>';
        if ($max < '00:15:00') {
            echo ' <strong>within the last 15 minutes</strong>, so it\'s possible you and someone
else are timestamping the same debate at the same time';
        } elseif ($from == 'next') {
            echo ' (probably by someone coming by at random when you\'ve been clicking Next)';
        }
        echo '. You can <a href="/video/next.php?action=nextneeded&amp;gid=',
$gid_safe, '&amp;file=', $file, '&amp;time=', $start,
'">go to the next unstamped speech on this day</a>,
or <a href="/video/next.php?action=random">get a new unstamped speech at random</a>.</p>';
    }
    print video_object($file, $start, $gid_safe, 1, $pid);
    video_quote($gid_actual, $parent_gid, $parent_body);
    if (get_http_var('from') != 'next' || !$hidden_int) {
        previous_speeches($surrounding_speeches, $gids_previous);
    }
    # print $summary;
    echo '</td><td>';
    echo '<div id="basic_hints"';
    if ($hidden_int) {
        echo ' style="display:none"';
    }
    echo '>';
    echo '<p style="float: right; border: solid 1px #666666; padding:3px;"><a onclick="return hideInstructions();" href=""><small>Hide instructions</small></a></p>';
    basic_instructions($pid);
    basic_hints($gid_safe, $file, $pid);
    echo '</div>';
    echo '<div id="advanced_hints"';
    if (!$hidden_int) {
        echo ' style="display:none"';
    }
    echo '>';
    advanced_hints($gid_safe, $file, $pid);
    echo '</div>';
    if (get_http_var('from') == 'next' && $hidden_int) {
        previous_speeches($surrounding_speeches, $gids_previous);
    } else {
        iframe_search($gid_safe, $file);
    }
    echo '</td>';
    echo '</tr></table>';
    $PAGE->page_end();
/*
}
*/

# ---

function video_front_page() {
    $db = new ParlDB;
    $statuses = array(
        0 => 'Unstamped',
        4 => 'Timestamped by users',
    );
    $q = $db->query('select video_status&4 as checked,count(*) as c from hansard
    where major=1 and video_status>0 and video_status<8 and video_status!=2 and htype in (12,13) group by video_status&4');
    $totaliser = array(0=>0, 4=>0);
    for ($i=0; $i<$q->rows(); $i++) {
        $status = $q->field($i, 'checked');
        $count = $q->field($i, 'c');
        $totaliser[$status] = $count;
    }
    $percentage = round($totaliser[4] / ($totaliser[0]+$totaliser[4]) * 10000) / 100;
    $out = "$totaliser[4] timestamped out of " . ($totaliser[0] + $totaliser[4]) . " ($percentage%)"
?>

<p style="margin-top:1em"><big>TheyWorkForYou has video of the House of Commons from the BBC, and
the text of Hansard from Parliament. Now we need <strong>your</strong>
help to match up the two.</big></p>

<p>We've written a little Flash app where you can (hopefully) match up the written speech being displayed to
what's playing on the video. We'll then store your results and use them to put the video,
timestamped to the right location, on the relevant page of TheyWorkForYou.</p>

<p>If you're a registered user and logged in,
your timestampings will appear in our chart below &ndash; there may be prizes for best timestampers&hellip; :)
Registration is not needed to timestamp videos, but you can <a href="/user/?pg=join&ret=/video/">register here</a> if you want.</p>

<p id="video_attract"><?php
    if ($totaliser[0]) {
        echo '<a href="next.php?action=random">Give me a random speech that needs timestamping</a>';
    } else {
        echo 'Wow, everything that can currently be timestamped appears to have been, thanks!';
    }
?></p>

<div id="top" style="float: left; width: 45%;">
<?php

    list($out_today, $rank_today) = display_league(20, 'and date(whenstamped)=current_date');
    list($out_week, $rank_week) = display_league(40, 'and date(whenstamped)>current_date-interval 28 day');
    list($out_overall, $rank_overall) = display_league(100);
    $out_overall = '';

    global $THEUSER;
    if ($THEUSER->user_id() && ($rank_today || $rank_week || $rank_overall)) {
        echo '<p align="center"><big>You are ';
        if ($rank_today) echo make_ranking($rank_today), ' today, ';
        if ($rank_week) echo make_ranking($rank_week), ' last 4 weeks, ';
        if ($rank_overall) echo make_ranking($rank_overall), ' overall';
        echo '</big></p>';
    }
    if ($out_today) echo "<h3>Top timestampers (today)</h3> <ol>$out_today</ol>";
    if ($out_week) echo "<h3>Top timestampers (last 4 weeks)</h3> <ol>$out_week</ol>";
    if ($out_overall) echo "<h3>Top timestampers (overall)</h3> <ol>$out_overall</ol>";
    echo '</div>';
?>
<div style="float: right; width: 50%">
<img align="right" width=200 height=100 src="http://chart.apis.google.com/chart?chs=200x100&cht=gom&chd=t:<?=$percentage?>" alt="<?=$percentage?>% of speeches have been timestamped">
<h3>Totaliser</h3>
<ul><?=$out?></ul>

<?php
    $q = $db->query('select video_status&4 as checked,count(*) as c from hansard
    where major=1 and video_status>0 and video_status<8 and video_status!=2 and htype in (12,13)
        and hdate=(select max(hdate) from hansard where major=1)
    group by video_status&4');
    $totaliser = array(0=>0, 4=>0);
    for ($i=0; $i<$q->rows(); $i++) {
        $status = $q->field($i, 'checked');
        $count = $q->field($i, 'c');
        $totaliser[$status] = $count;
    }
    $total_possible = $totaliser[0] + $totaliser[4];
    if ($total_possible == 0) {
        $percentage = 0;
        $out = 'Nothing possible to timestamp on most recent day';
    } else {
        $percentage = round($totaliser[4] / $total_possible * 10000) / 100;
        $out = "$totaliser[4] timestamped out of $total_possible ($percentage%)";
    }

?>
<h3 style="padding-top:0.5em;clear:right">Totaliser for most recent day</h3>
<img align="right" width=200 height=100 src="http://chart.apis.google.com/chart?chs=200x100&cht=gom&chd=t:<?=$percentage?>" alt="<?=$percentage?>% of speeches have been timestamped">
<ul><?=$out?></ul>

<h3 style="clear:both;margin-top:1em">Latest stamped</h3>
<ul>
<?php

    $q = $db->query('select hansard.gid, body, major from video_timestamps, hansard, epobject
    where (user_id != -1 or user_id is null) and video_timestamps.deleted=0
        and video_timestamps.gid = hansard.gid and hansard.subsection_id = epobject.epobject_id
    order by whenstamped desc limit 20');
    for ($i=0; $i<$q->rows(); $i++) {
        $gid = $q->field($i, 'gid');
        $body = $q->field($i, 'body');
        if ($q->field($i, 'major') == 101) {
            $url = '/lords/?gid=';
        } else {
            $url = '/debate/?id=';
        }
        echo '<li><a href="', $url, fix_gid_from_db($gid) . '">' . $body . '</a>';
    }

    echo '</ul></div>';
}

function display_league($limit, $q = '') {
    global $THEUSER;
    $db = new ParlDB;
    $q = $db->query('select firstname,lastname,video_timestamps.user_id,count(*) as c
        from video_timestamps left join users on video_timestamps.user_id=users.user_id
        where video_timestamps.deleted=0 and (video_timestamps.user_id is null or video_timestamps.user_id!=-1) '
        . $q . ' group by user_id order by c desc' . ($THEUSER->user_id() ? '' : " limit $limit") );
    $out = ''; $rank = 0;
    for ($i=0; $i<$q->rows(); $i++) {
        $name = $q->field($i, 'firstname') . ' ' . $q->field($i, 'lastname');
        $user_id = $q->field($i, 'user_id');
        #if ($user_id == -1) continue; # $name = 'CaptionerBot';
        if ($user_id == 0) $name = 'Anonymous';
        $count = $q->field($i, 'c');
        if ($THEUSER->user_id() == $user_id) {
            $rank = $i+1;
        }
        if ($i>=$limit) continue;
        $out .= '<li>';
        if ($THEUSER->user_id() == $user_id)
            $out .= '<strong>';
        $out .= "$name : $count";
        if ($THEUSER->user_id() == $user_id)
            $out .= '</strong>';
        #if ($user_id == -1) {
        #	echo ' <small>(initial run program that tries to guess timestamp from captions, wildly variable)</small>';
        #}
    }
    return array($out, $rank);
}

function video_quote($gid_actual, $parent_gid, $parent_body) {
    #echo '<h4>Press &ldquo;Play&rdquo;, then click &ldquo;Now!&rdquo; when you hear:</h4>';
    echo '<div id="video_quote">';
    echo '<span class="video_name">' . $gid_actual['first_name'] . ' ' . $gid_actual['last_name'] . '</span> ';
    echo $gid_actual['body_first'];
    echo '<p align="right">&mdash; from debate entitled &ldquo;<a title="View entire debate" href="' . $parent_gid . '">' . $parent_body . '</a>&rdquo;</p>';
    echo '</div>';
}

function basic_instructions($pid) {
    $pid_url = '';
    if ($pid) $pid_url = "&amp;pid=$pid";
?>
<ol style="font-size: 150%;">
<li>Have a quick scan of the speech under the video, then press &ldquo;Play&rdquo;.
<li>When you hear the start of that speech, press &ldquo;Now!&rdquo;.
<li>The timestamped video will then appear on TheyWorkForYou &ndash; thanks from
everyone who uses the site :)
</ol>

<p style="font-size: 125%; margin: 1em 0; background-color: #ffffcc; padding: 5px;">
Some videos will be miles out &ndash; if you can't
find the right point, don't worry, just <a href="/video/next.php?action=random<?=$pid_url?>"><strong>try another speech</strong></a>!
</p>
<?php
}

function basic_hints($gid_safe, $file, $pid) {
    global $THEUSER;
    $pid_url = '';
    if ($pid) $pid_url = "&amp;pid=$pid";
?>

<ul>
<?php	if (!$THEUSER->loggedin()) { ?>
<li><a href="/user/login/?ret=/video/"><strong>Sign in</strong></a> if you want to get on the <a href="/video/#top">Top Timestampers league table</a>!
<?php	} ?>
<li>If the video suddenly <strong>jumps</strong> a couple of hours, or otherwise appears broken, <a href="mailto:<?=str_replace('@', '&#64;', CONTACTEMAIL) ?>?subject=Video%20<?=$file?>%20for%20ID%20<?=$gid_safe?>%20broken">let us know</a>.
<li>If the speech you're looking for is <strong>beyond the end</strong> of the video,
<a href="/video/?gid=<?=$gid_safe?>&amp;file=<?=$file+1?>&amp;start=1<?=$pid_url?>">move on to the next video chunk</a>.
<li>If you're right at the start of a day, it's quite possible the start of the video
will be the end of the previous programme on BBC Parliament, skip ahead some minutes
to check :)
<li>Hansard is not a verbatim transcript, so <strong>spoken words might
differ</strong> slightly from the printed version. And a small note &ndash; if
the speech you are looking out for is an oral question (questions asked in the
first hour or so of Monday&ndash;Thursdays in the Commons), then all the MP
will actually say is their question number, e.g.  &ldquo;Number Two&rdquo;.
<li>The skip buttons move in 30 second increments (you can go
back before the start point), and you can access a slider by hovering
over the video.
</ul>

<p align="right"><small><b>Credits:</b> Video from <a href='http://www.bbc.co.uk/parliament/'>BBC Parliament</a> and mySociety</small></p>

<?php
}

function advanced_hints($gid_safe, $file, $pid) {
    global $THEUSER;
    $pid_url = '';
    if ($pid) $pid_url = "&amp;pid=$pid";
?>

<p align="center">Actions:
<a href="/video/next.php?action=random">Skip</a> |
<?php	if (!$THEUSER->loggedin()) { ?>
<a href="/user/login/?ret=/video/">Log in</a> |
<?php	} ?>
<a href="mailto:<?=str_replace('@', '&#64;', CONTACTEMAIL) ?>?subject=Video%20<?=$file?>%20for%20ID%20<?=$gid_safe?>%20broken">Broken video</a> |
<a href="/video/?gid=<?=$gid_safe?>&amp;file=<?=$file+1?>&amp;start=1<?=$pid_url?>" title="Loads the next video chunk">Speech past end of video</a>
</p>

<p align="right"><small><!-- <b>Credits:</b> Video from <a href='http://www.bbc.co.uk/parliament/'>BBC Parliament</a> and mySociety. -->
<a style=" border: solid 1px #666666; padding:3px;" onclick="return showInstructions();" href="">Show all instructions</a>

</small></p>
<?php
}

function iframe_search($gid_safe, $file) {
?>
<iframe frameborder=0 style="border: dotted 1px black; margin-top:0.5em" name="video_person_search" width="95%" height="800" src="distance.php?gid=<?=$gid_safe?>&amp;file=<?=$file?>"></iframe>
<?php
}

function previous_speeches($surrounding_speeches, $gids_previous) {
    echo '<h3 style="margin-top:1em">The three speeches/headings immediately before</h3> <ol class="otherspeeches">';
    $ccc = 1;
    foreach ($gids_previous as $row) {
        disp_speech($row, $ccc++);
    }
    echo '</ol>';
}

function disp_speech($row, $count) {
    echo '<li';
    if ($row['htype']==13) echo ' class="unspoken"';
    elseif ($row['htype']<12) echo ' class="heading"';
    if (!($count%2)) echo ' style="background-color: #F5FDEA;"';
    else echo ' style="background-color: #E8FDCB;"';
    echo '>';
    if ($count) echo "<em>$count earlier:</em> ";
    if ($row['htype']==12)
        echo '<span class="video_name">' . $row['first_name'] . ' ' . $row['last_name'] . '</span> ';
    echo $row['body'];
    echo '</li>';
}

/*
echo '<h3>The ';
if ($gid_actual['body'] != $gid_actual['body_first']) {
    echo' whole';
}
echo ' speech you\'re looking out for</h3>';
echo $gid_actual['body'];

echo '<h3 style="margin-top:1em">Following speeches/headings</h3> <ol class="otherspeeches">';

foreach ($gids_following as $row) {
    disp_speech($row, 0);
}
echo '</ul>';
*/

/*
$last_prev = end($gids_previous);
if ($last_prev['htime'] == $gid_actual['htime']) {
    echo "<p><small><em>This speech has the same timestamp as the previous speech, so might well be inaccurate.</em></small></p>";
}
*/
