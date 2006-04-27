<?php

include_once "../../includes/easyparliament/init.php";

$this_page = "getinvolved";

$PAGE->page_start();

$PAGE->stripe_start();

include INCLUDESPATH . 'easyparliament/staticpages/getinvolved.php';

$PAGE->stripe_end();

$PAGE->page_end();

?>
