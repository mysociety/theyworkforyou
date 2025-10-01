<?php

include_once '../../includes/easyparliament/init.php';

$this_page = "getinvolved";

$PAGE->page_start();

$PAGE->stripe_start();

readfile(INCLUDESPATH . 'easyparliament/staticpages/getinvolved.html');

$PAGE->stripe_end();

$PAGE->page_end();
