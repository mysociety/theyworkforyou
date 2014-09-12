<?php
# For displaying any debate calendar, day, debate, speech page or related.

include_once '../includes/easyparliament/init.php';
include_once INCLUDESPATH . "easyparliament/glossary.php";

# Redirect shortcut.
# We no longer want to use the gid parameter, it's all id.
if (get_http_var('gid')) {
    $url = str_replace('gid', 'id', $_SERVER['REQUEST_URI']);
    redirect($url);
}

if ($type = ucfirst(get_http_var('type'))) {
    $class_name = "MySociety\TheyWorkForYou\SectionView\\${type}View";
    $view = new $class_name();
    $data = $view->display();
    if ($data) {
        MySociety\TheyWorkForYou\Renderer::output('section/section', $data);
    }
} else {
    $PAGE->error_message("No type specified", true);
}
