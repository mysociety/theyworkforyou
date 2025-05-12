<?php

$this_page = "interests_home";

include_once '../../includes/easyparliament/init.php';

use MySociety\TheyWorkForYou\Renderer\Markdown;

$markdown = new Markdown();
$markdown->markdown_document('interests_home', true, [
    '_page_title' => 'Registers of Interest',
    '_social_image_title' => 'Registers of Interest']);
