<?php

// The login form page.

/*
    If the form hasn't been submitted, display_page() is called and the form shown.
    If the form has been submitted we check the input.
    If the input is OK, the user is logged in and taken to wherever they were before.
    If the input is not OK, the form is displayed again with error messages.
*/

$new_style_template = true;

include_once '../../../includes/easyparliament/init.php';
# need to include this as login code uses error_message
include_once '../../../includes/easyparliament/page.php';
$login = new \MySociety\TheyWorkForYou\FacebookLogin();

# used by the facebook login code for CSRF tokens
MySociety\TheyWorkForYou\Utility\Session::start();

$this_page = 'topic';

$data = $login->handleFacebookRedirect();

$data['fb_login_url'] = $login->getLoginURL();
if (isset($data['token'])) {
    $success = $login->loginUser($data['token']);
    if (!$success) {
        $data['error'] = 'Could not login using Facebook token';
        \MySociety\TheyWorkForYou\Renderer::output('login/facebook', $data);
    }
} else {
    \MySociety\TheyWorkForYou\Renderer::output('login/facebook', $data);
}
