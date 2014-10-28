<?php

include_once '../../includes/easyparliament/init.php';

$this_page = "debate";
$this_page = "debates";

$dates = file('alldates');
shuffle($dates);
$date = trim($dates[0]);

$db = new \MySociety\TheyWorkForYou\ParlDb();
$q = $db->query("select gid from hansard where htype in (10,11) and major=1 and hdate='$date' order by rand() limit 1");
$gid = $q->field(0, 'gid');

$args = array (
    'gid' => fix_gid_from_db($gid),
    'glossarise' => 1,
    'sort' => 'regexp_replace',
);

$GLOSSARY = new \MySociety\TheyWorkForYou\Glossary($args);
$LIST = new \MySociety\TheyWorkForYou\HansardList\DebateList;
$result = $LIST->display('gid', $args);

$PAGE->page_end();
