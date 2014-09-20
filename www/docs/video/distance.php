<?php

include_once '../../includes/easyparliament/init.php';
include_once INCLUDESPATH . 'easyparliament/video.php';

$file = intval(get_http_var('file'));
$gid = get_http_var('gid');
preg_match('#^([a-z]+)/(\d\d\d\d)-(\d\d)-(\d\d)#', $gid, $m);
if (!$gid || !count($m)) {
    print '<p>Requires gid</p>';
    exit;
}
$gid_type = $m[1];
$gid_date = "$m[2]$m[3]$m[4]";

global $want; # It's in the template, yucky, sorry
$want['gid'] = $gid;
$want['file'] = $file;

if ($search = get_http_var('s')) {
    search_box($file);
    day_speeches($search, $gid_type, $gid_date);
} else { ?>
<p>If the playing speech appears to be in completely the wrong place,
enter the speaking MP's name and/or something they're saying in this search box
to search the day's speeches. Find the correct speech and it will say how
many speeches out the video is &ndash; you can then either reposition the video
or follow the link to switch to matching that speech instead.</p>
<?php
    search_box($file);
}

function search_box($file) {
    global $want;
?>
<form action="distance.php" method="get" target="video_person_search">
<label for="vid_search">MP name or spoken word(s):</label>
<input id="vid_search" type="text" name="s" value="">
<input type="hidden" name="gid" value="<?=$want['gid']?>">
<input type="hidden" name="file" value="<?=$file?>">
<input type="submit" value="Search">
</form>
<?php
}

function day_speeches($search, $type, $date) {
    $search = "$search date:$date section:$type groupby:speech";

    global $SEARCHENGINE, $want;
    $SEARCHENGINE = new SEARCHENGINE($search);

    $db = new ParlDB;
    $q = $db->query("select hpos from hansard where gid = :gid", array(
        ':gid' => 'uk.org.publicwhip/' . $want['gid']
        ));
    $want['hpos'] = $q->field(0, 'hpos');

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
