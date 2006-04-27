<?php

include_once "../../includes/easyparliament/init.php";
include_once (INCLUDESPATH."easyparliament/searchlog.php");

$this_page = "admin_failedsearches";



$PAGE->page_start();

$PAGE->stripe_start();

global $SEARCHLOG;
$search_recent = $SEARCHLOG->admin_failed_searches();

$rows = array();
foreach ($search_recent as $row) {
	$host = gethostbyaddr($row['ip_address']);
	$host = trim_characters($host, strlen($host) - 25, 100);
	$min_time = relative_time($row['min_time']);
	$max_time = relative_time($row['max_time']);
	$min_time = str_replace(" ago", "", $min_time);
	$max_time = str_replace(" ago", "", $max_time);
	$rows[] = array (
        $row['count_ips'],
        $row['group_count'],
		'<a href="'.$row['url'].'">'.htmlentities($row['query']).'</a>',
        $max_time,
        $min_time,
	);
}

$tabledata = array (
	'header' => array (
        'Distinct IPs',
        'Number of times',
		'Query',
        'Most recent ago',
        'First ago',
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
