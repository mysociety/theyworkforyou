<?php

$this_page = "comments_recent";
include_once "../../../includes/easyparliament/init.php";
$PAGE->page_start();
$PAGE->stripe_start();
$COMMENTLIST = new COMMENTLIST;
$args = array(
	'page' => get_http_var('p'),
	'pid' => get_http_var('pid')
);
$COMMENTLIST->display('recent', $args);
$PAGE->stripe_end();
$PAGE->page_end();

?>
