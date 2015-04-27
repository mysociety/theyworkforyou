<?php

include_once '../../includes/easyparliament/init.php';

$data = array();
$this_page = "help";
MySociety\TheyWorkForYou\Renderer::output('static\help', $data);
