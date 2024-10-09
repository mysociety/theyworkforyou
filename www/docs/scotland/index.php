<?php

include_once '../../includes/easyparliament/init.php';

$view = new MySociety\TheyWorkForYou\Homepage\Scotland();
$data = $view->display();

MySociety\TheyWorkForYou\Renderer::output('scotland/index', $data);
