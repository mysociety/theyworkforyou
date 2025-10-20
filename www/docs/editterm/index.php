<?php

include_once '../../includes/easyparliament/init.php';
include_once INCLUDESPATH . "easyparliament/glossary.php";
include_once INCLUDESPATH . "easyparliament/glossarylist.php";

$view = new MySociety\TheyWorkForYou\GlossaryView\EditTermView();
$data = $view->display();

// Check for permission errors
if (isset($data['error']) && !isset($data['template_name'])) {
    $PAGE->page_start();
    echo '<p>' . $data['error'] . '</p>';
    $PAGE->page_end();
    exit;
}

$data['PAGE'] = $PAGE;

$template = "glossary/" . $data['template_name'];
MySociety\TheyWorkForYou\Renderer::output($template, $data);
