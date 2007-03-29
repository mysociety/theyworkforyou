<?php

include_once "../../includes/easyparliament/init.php";
include_once INCLUDESPATH."easyparliament/people.php";

$this_page = 'mlas';

if (get_http_var('f') != 'csv') {
	$PAGE->page_start();
	$PAGE->stripe_start();
	$format = 'html';
?>
<p>This list has been updated with the results of the
2007 election.</p>
<?
} else {
	$format = 'csv';
}

$args = array();

if (get_http_var('o') == 'f') {
	$args['order'] = 'first_name';
} elseif (get_http_var('o') == 'l') {
	$args['order'] = 'last_name';
} elseif (get_http_var('o') == 'c') {
	$args['order'] = 'constituency';
} elseif (get_http_var('o') == 'p') {
	$args['order'] = 'party';
} elseif (get_http_var('o') == 'e') {
	$args['order'] = 'expenses';
} elseif (get_http_var('o') == 'd') {
	$args['order'] = 'debates';
}

$PEOPLE = new PEOPLE;
$PEOPLE->display('mlas', $args, $format);

if (get_http_var('f') != 'csv') {
	$PAGE->stripe_end(array(
		array('type'=>'include', 'content'=>'peers'),
		array('type'=>'include', 'content'=>'donate')
	));
	$PAGE->page_end();
}

?>
