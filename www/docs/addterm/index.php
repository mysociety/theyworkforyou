<?php

include_once '../../includes/easyparliament/init.php';
include_once INCLUDESPATH . "easyparliament/glossary.php";
include_once INCLUDESPATH . "easyparliament/glossarylist.php";

$view = new MySociety\TheyWorkForYou\GlossaryView\AddTermView();
$data = $view->display();
$data['PAGE'] = $PAGE;

$template = "glossary/" . $data['template_name'];
MySociety\TheyWorkForYou\Renderer::output($template, $data);
