<?php

include_once '../../includes/easyparliament/init.php';

use MySociety\TheyWorkForYou\Renderer\Markdown;

function beacon_form_placeholder(string $form_id): string {
    return '<div class="beacon-form" data-account="mysociety" data-form="' . htmlspecialchars($form_id) . '"></div>';
}

$markdown = new Markdown();
$markdown->markdown_document('support-us', true, [
    'beacon_form' => beacon_form_placeholder(form_id: '9a459b1b'),
    '_page_title' => 'Support Us - TheyWorkForYou',
    '_social_image_title' => 'Support Our Work']);
