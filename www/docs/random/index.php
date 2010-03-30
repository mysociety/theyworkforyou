<?php

include_once "../../includes/easyparliament/init.php";
include_once INCLUDESPATH . "easyparliament/glossary.php";

$this_page = "debate";
$this_page = "debates";

$dates = file('alldates');
shuffle($dates);
$date = trim($dates[0]);

$db = new ParlDB();
$q = $db->query("select gid from hansard where htype in (10,11) and major=1 and hdate='$date' order by rand() limit 1");
$gid = $q->field(0, 'gid');

$args = array (
	'gid' => $gid,
	'glossarise' => 1,
	'sort' => 'regexp_replace',
);
	
$GLOSSARY = new GLOSSARY($args);
$LIST = new DEBATELIST;
$result = $LIST->display('gid', $args);
		
$args = array (
	'epobject_id' => $LIST->epobject_id()
);

$PAGE->page_end();

