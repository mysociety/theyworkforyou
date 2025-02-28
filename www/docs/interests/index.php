<?php

$this_page = "interests_home";

include_once '../../includes/easyparliament/init.php';

use MySociety\TheyWorkForYou\Renderer\Markdown;

$markdown = new Markdown();
$markdown->markdown_document('interests_home');
