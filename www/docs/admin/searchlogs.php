<?php

include_once '../../includes/easyparliament/init.php';
include_once(INCLUDESPATH . "easyparliament/searchlog.php");

$this_page = "admin_searchlogs";

$PAGE->page_start();

$PAGE->stripe_start();

global $SEARCHLOG;
$search_recent = $SEARCHLOG->admin_recent_searches(50);

$rows = [];
foreach ($search_recent as $row) {
    $host = gethostbyaddr($row['ip_address']);
    $host = trim_characters($host, strlen($host) - 25, 100);
    $time = relative_time($row['query_time']);
    $time = str_replace(" ago", "", $time);
    $rows[] =  [
        '<a href="' . $row['url'] . '">' . _htmlentities($row['query']) . '</a>',
        $row['page_number'],
        $row['count_hits'],
        $host,
        $time,
    ];
}

$tabledata =  [
    'header' =>  [
        'Query',
        'Page',
        'Hits',
        'Host',
        'Time ago',
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
