<?php

$new_style_template = TRUE;

include_once '../../../includes/easyparliament/init.php';

$data = array();
$data['recent_election'] = True;

MySociety\TheyWorkForYou\Renderer::output('alert/postcode', $data);

