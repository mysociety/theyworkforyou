<?php

$new_style_template = TRUE;

include_once '../../../includes/easyparliament/init.php';

$data = array();
$data['recent_election'] = True;

// Example of passing a logged-in user’s details into the form
// $data['email'] = 'foo@example.com';

// Example of triggering a new message after
// sign up details have been submitted
if (isset($_POST['postcode']) && isset($_POST['email'])) {

    // Example of form validation errors
    if ($_POST['postcode'] && $_POST['email']) {
        $data['confirmation_sent'] = True;
    } else {
        $data['invalid-postcode-or-email'] = True;
    }
}

// Example of triggering a new message if user
// has come via a link in a confirmation email
// or submitted the form as a logged-in user
if (isset($_GET['confirmed'])) {
    $data['confirmation_received'] = True;
}

MySociety\TheyWorkForYou\Renderer::output('alert/postcode', $data);

