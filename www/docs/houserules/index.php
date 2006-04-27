<?php

$this_page = "houserules";

include_once "../../includes/easyparliament/init.php";

$PAGE->page_start();

$PAGE->stripe_start();

include INCLUDESPATH . 'easyparliament/staticpages/houserules.php';

$PAGE->stripe_end();

$PAGE->page_end();

?>