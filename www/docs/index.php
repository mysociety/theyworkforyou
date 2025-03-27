<?php

include_once '../includes/easyparliament/init.php';
include_once '../includes/easyparliament/recess.php';

$view = new MySociety\TheyWorkForYou\Homepage\UK();
$data = $view->display();

MySociety\TheyWorkForYou\Renderer::output('index', $data);
