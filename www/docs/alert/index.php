<?php

$new_style_template = true;

include_once '../../includes/easyparliament/init.php';
include_once INCLUDESPATH . 'easyparliament/member.php';

$alert = new MySociety\TheyWorkForYou\AlertView\Standard($THEUSER);
$data = $alert->display();
$data["og_image"] = MySociety\TheyWorkForYou\Url::generateSocialImageUrl(
    gettext("Email Alerts"),
);
MySociety\TheyWorkForYou\Renderer::output('alert/index', $data);
