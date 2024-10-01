<?php

include_once '../../includes/easyparliament/init.php';
include_once(INCLUDESPATH . "easyparliament/commentreportlist.php");

$this_page = "admin_comments";

$PAGE->page_start();

$PAGE->stripe_start();

// Most recent comments.
$COMMENTLIST = new COMMENTLIST();

$COMMENTLIST->display('recent', ['num' => 50]);

$menu = $PAGE->admin_menu();

$PAGE->stripe_end([
    [
        'type'		=> 'html',
        'content'	=> $menu,
    ],
]);

$PAGE->page_end();
