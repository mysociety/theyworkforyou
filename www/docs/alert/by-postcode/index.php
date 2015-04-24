<?php

$new_style_template = TRUE;

include_once '../../../includes/easyparliament/init.php';
include_once INCLUDESPATH . 'easyparliament/member.php';

global $THEUSER;

$alert = new MySociety\TheyWorkForYou\AlertView($THEUSER);
$data = $alert->display();

MySociety\TheyWorkForYou\Renderer::output('alert/postcode', $data);

