<?php

include_once "../../../includes/easyparliament/init.php";

$this_page = "linktous";

$PAGE->page_start();

$PAGE->stripe_start();

include INCLUDESPATH . 'easyparliament/staticpages/linktous.php';

$PAGE->stripe_end();

$PAGE->page_end();

?>
