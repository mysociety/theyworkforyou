<?php

$new_style_template = TRUE;

include_once '../../../includes/easyparliament/init.php';

$data = array();
$data['recent_election'] = True;

// Example of passing a logged-in user’s details into the form
// $data['email'] = 'foo@example.com';

MySociety\TheyWorkForYou\Renderer::output('alert/postcode', $data);

