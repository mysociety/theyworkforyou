<?php

include_once '../../includes/easyparliament/init.php';
include_once INCLUDESPATH . "easyparliament/glossary.php";

$class = 'AtoZView';
if (get_http_var('gl')) {
    $class = 'TermView';
}

$class_path = 'MySociety\\TheyWorkForYou\\GlossaryView\\' . $class;
$view = new $class_path();
$data = $view->display();

$this_page = $data['this_page'];
$DATA->set_page_metadata($this_page, 'title', $data['page_title']);
$data['PAGE'] = $PAGE;

$template = "glossary/" . $data['template_name'];
MySociety\TheyWorkForYou\Renderer::output($template, $data);
