<?php

include_once '../../includes/easyparliament/init.php';

use MySociety\TheyWorkForYou\Renderer\Markdown;

$markdown = new Markdown();
$markdown->markdown_document('support-us', true, [
    '_page_title' => 'Support Us - TheyWorkForYou',
    '_social_image_title' => 'Support Our Work']);
