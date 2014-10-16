<?php

include_once '../../includes/easyparliament/init.php';

$this_page = "admin_commentreports";

$PAGE->page_start();

$PAGE->stripe_start();

// Get the most recent comment reports.
$LIST = new \MySociety\TheyWorkForYou\CommentReportList;
$LIST->display();

$menu = $PAGE->admin_menu();

$PAGE->stripe_end(array(
    array(
        'type'		=> 'html',
        'content'	=> $menu
    )
));

$PAGE->page_end();
