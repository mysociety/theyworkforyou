<?php
include_once '../../includes/easyparliament/init.php';

use MySociety\TheyWorkForYou\Renderer\Markdown;

$markdown = new Markdown;
$markdown->markdown_document('support-us-thanks', false);
?>
