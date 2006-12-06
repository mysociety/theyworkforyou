<?php

include_once "../../includes/easyparliament/init.php";
include_once INCLUDESPATH."easyparliament/people.php";

$this_page = 'mps';
if (get_http_var('c4')) $this_page = 'c4_mps';
elseif (get_http_var('c4x')) $this_page = 'c4x_mps';

if (get_http_var('f') != 'csv') {
	$PAGE->page_start();
	$PAGE->stripe_start();
	$format = 'html';
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
} elseif (get_http_var('o') == 's') {
	$args['order'] = 'safety';
}

$PEOPLE = new PEOPLE;
$PEOPLE->display('mps', $args, $format);

if (get_http_var('f') != 'csv') {
	$PAGE->stripe_end(array(array('type'=>'include', 'content'=>'mps')));
	$PAGE->page_end();
}

?>
