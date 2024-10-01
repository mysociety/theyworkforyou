<?php

# For displaying any debate calendar, day, debate, speech page or related.

include_once '../includes/easyparliament/init.php';
include_once INCLUDESPATH . "easyparliament/glossary.php";
include_once INCLUDESPATH . "easyparliament/recess.php";

# Redirect shortcut.
# We no longer want to use the gid parameter, it's all id.
if (get_http_var('gid')) {
    $url = str_replace('gid', 'id', $_SERVER['REQUEST_URI']);
    redirect($url, 301);
}

$type = ucfirst(get_http_var('type'));
$class_name = "MySociety\TheyWorkForYou\SectionView\\${type}View";
if (!$type || !class_exists($class_name)) {
    $PAGE->error_message("No type specified", true);
    exit;
}

$view = new $class_name();

// use this for generating member vote data
global $THEUSER, $MEMBER;
if (isset($THEUSER) && $THEUSER->postcode_is_set()) {
    try {
        $MEMBER = new MySociety\TheyWorkForYou\Member(['postcode' => $THEUSER->postcode(), 'house' => \MySociety\TheyWorkForYou\Utility\House::majorToHouse($view->major)[0]]);
    } catch (MySociety\TheyWorkForYou\MemberException $e) {
        $MEMBER = null;
    }
}

$data = $view->display();
if ($data) {
    if (!empty($data['template'])) {
        $template = $data['template'];
    } else {
        $template = 'section/section';
    }
    MySociety\TheyWorkForYou\Renderer::output($template, $data);
}
