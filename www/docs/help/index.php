<?php

include_once "../../includes/easyparliament/init.php";

$this_page = "help";

$PAGE->page_start();

$PAGE->stripe_start();

include INCLUDESPATH . 'easyparliament/staticpages/help.php';

$PAGE->stripe_end();

$PAGE->page_end();

?>