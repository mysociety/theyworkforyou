<?php

include_once '../../includes/easyparliament/init.php';
include_once INCLUDESPATH . "easyparliament/glossary.php";
include_once INCLUDESPATH . "easyparliament/glossarylist.php";

$view = new MySociety\TheyWorkForYou\GlossaryView\AddTermView();
$data = $view->display();
$data['PAGE'] = $PAGE;

MySociety\TheyWorkForYou\Renderer::output("glossary/addterm", $data);
