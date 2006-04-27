<?php

$this_page = "disclaimer";

include_once "../../includes/easyparliament/init.php";

$PAGE->page_start();

$PAGE->stripe_start();

include INCLUDESPATH . 'easyparliament/staticpages/disclaimer.php';

$PAGE->stripe_end();

$PAGE->page_end();

?>