<?php

include_once '../../includes/easyparliament/init.php';

$this_page = "admin_trackbacks";

$PAGE->page_start();

$PAGE->stripe_start();

$TRACKBACK = new \MySociety\TheyWorkForYou\Trackback();
$TRACKBACK->display('recent', array('num'=>30));

$menu = $PAGE->admin_menu();

$PAGE->stripe_end(array(
    array(
        'type'		=> 'html',
        'content'	=> $menu
    )
));

$PAGE->page_end();
