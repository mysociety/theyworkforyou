<?php

include_once "../../includes/easyparliament/init.php";
include_once INCLUDESPATH."easyparliament/people.php";

$this_page = 'peers';

if (get_http_var('f') != 'csv') {
	$PAGE->page_start();
	$PAGE->stripe_start();
	$format = 'html';
} else {
	$format = 'csv';
}

$args = array('order'=>'name');

if (get_http_var('o') == 'n') {
	$args['order'] = 'name';
} elseif (get_http_var('o') == 'p') {
	$args['order'] = 'party';
} elseif (get_http_var('o') == 'c') {
	$args['order'] = 'constituency';
}

$PEOPLE = new PEOPLE;
$PEOPLE->display('peers', $args, $format);

if (get_http_var('f') != 'csv') {
	$PAGE->stripe_end(array(array('type'=>'include', 'content'=>'peers')));
	$PAGE->page_end();
}

?>
