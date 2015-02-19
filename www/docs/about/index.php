<?php

$this_page = "about";

include_once '../../includes/easyparliament/init.php';

$PAGE->page_start();

$PAGE->stripe_start();

readfile(INCLUDESPATH . 'easyparliament/staticpages/about.html');

$PAGE->stripe_end();

$PAGE->page_end();
