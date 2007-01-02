<?php

include_once "../../includes/easyparliament/init.php";
include_once (INCLUDESPATH."easyparliament/searchlog.php");

$this_page = "admin_popularsearches";



$PAGE->page_start();

$PAGE->stripe_start();

global $SEARCHLOG;
$search_popular = $SEARCHLOG->popular_recent(50);

$rows = array();
foreach ($search_popular as $row) {
	$host = gethostbyaddr($row['ip_address']);
	$host = trim_characters($host, strlen($host) - 25, 100);
	$time = relative_time($row['query_time']);
	$time = str_replace(" ago", "", $time);
	$rows[] = array (
		'<a href="'.$row['url'].'">'.htmlentities($row['query']).'</a>',
		$row['page_number'],
		$row['count_hits'],
		$host,
		$time,
	);
}

$tabledata = array (
	'header' => array (
		'Query',
		'Page',
		'Hits',
		'Host',
		'Time ago'
	),
	'rows' => $rows
);
$PAGE->display_table($tabledata);


$menu = $PAGE->admin_menu();

$PAGE->stripe_end(array(
	array(
		'type'		=> 'html',
		'content'	=> $menu
	)
));



$PAGE->page_end();

?>
