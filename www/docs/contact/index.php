<?php

$this_page = "contact";

include_once "../../includes/easyparliament/init.php";

$PAGE->page_start();

$PAGE->stripe_start();

include INCLUDESPATH . 'easyparliament/staticpages/contact.php';

$PAGE->stripe_end();

$PAGE->page_end();

?>
