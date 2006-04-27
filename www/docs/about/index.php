<?php

$this_page = "about";

include_once "../../includes/easyparliament/init.php";

$PAGE->page_start();

$PAGE->stripe_start();

include INCLUDESPATH . 'easyparliament/staticpages/about.php';

$PAGE->stripe_end();

$PAGE->page_end();

?>