<?php

$this_page = "houserules";

include_once '../../includes/easyparliament/init.php';

$PAGE->page_start();

$PAGE->stripe_start();

readfile(INCLUDESPATH . 'easyparliament/staticpages/houserules.html');

$PAGE->stripe_end();

$PAGE->page_end();
