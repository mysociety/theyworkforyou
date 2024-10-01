<?php

include_once '../../includes/easyparliament/init.php';
include_once(INCLUDESPATH . "easyparliament/searchlog.php");

$this_page = "admin_popularsearches";

$PAGE->page_start();

$PAGE->stripe_start();

global $SEARCHLOG;
$search_popular = $SEARCHLOG->admin_popular_searches(1000);

$rows = [];
foreach ($search_popular as $row) {
    $rows[] =  [
        '<a href="' . $row['url'] . '">' . _htmlentities($row['query']) . '</a>',
        $row['c'],
    ];
}

$tabledata =  [
    'header' =>  [
        'Query',
        'Count',
    ],
    'rows' => $rows,
];
$PAGE->display_table($tabledata);

$menu = $PAGE->admin_menu();

$PAGE->stripe_end([
    [
        'type'		=> 'html',
        'content'	=> $menu,
    ],
]);

$PAGE->page_end();
