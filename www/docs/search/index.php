<?php

$new_style_template = TRUE;

include_once '../../includes/easyparliament/init.php';
include_once INCLUDESPATH . "easyparliament/glossary.php";
include_once INCLUDESPATH . 'easyparliament/member.php';

if (!DEVSITE) {
    header('Cache-Control: max-age=900');
}

if (get_http_var('pid') == 16407) {
    header('Location: /search/?pid=10133');
    exit;
}


$search = new MySociety\TheyWorkForYou\Search();
$data = $search->display();

MySociety\TheyWorkForYou\Renderer::output($data['template'], $data);
