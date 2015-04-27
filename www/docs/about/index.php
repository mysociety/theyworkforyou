<?php

include_once '../../includes/easyparliament/init.php';

$data = array();
$this_page = "about";
MySociety\TheyWorkForYou\Renderer::output('static\about', $data);
