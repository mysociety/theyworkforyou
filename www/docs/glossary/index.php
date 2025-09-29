<?php

include_once '../../includes/easyparliament/init.php';
include_once INCLUDESPATH . "easyparliament/glossary.php";

$view = new MySociety\TheyWorkForYou\GlossaryView\AtoZView();
$data = $view->display();

if (isset($data['term'])) {
    $this_page = 'glossary_item';
    $DATA->set_page_metadata($this_page, 'title', $data['title'] . ': Glossary Item');
} else {
    $this_page = "glossary";
    $DATA->set_page_metadata($this_page, 'title', $data['letter'] . ': Glossary Index');
}

$data['PAGE'] = $PAGE;

MySociety\TheyWorkForYou\Renderer::output("glossary/atoz", $data);
