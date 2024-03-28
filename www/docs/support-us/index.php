<?php
include_once '../../includes/easyparliament/init.php';
include_once '../../includes/easyparliament/helper-donate.php';

// Run the stripe session if the stripe parameter is set
check_for_stripe_submission(
    "https://www.theyworkforyou.com/support-us/thanks",
    "https://www.theyworkforyou.com/support-us/failed"
);

use MySociety\TheyWorkForYou\Renderer\Markdown;

$markdown = new Markdown;
$markdown->markdown_document('support-us');
?>
