<?php
# For displaying any debate calendar, day, debate, speech page or related.

include_once '../includes/easyparliament/init.php';
include_once INCLUDESPATH . "easyparliament/glossary.php";
include_once INCLUDESPATH . "easyparliament/recess.php";

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
        if ( !empty($data['template']) ) {
            $template = $data['template'];
        } else {
            $template = 'section/section';
        }
        MySociety\TheyWorkForYou\Renderer::output($template, $data);
    }
} else {
    $PAGE->error_message("No type specified", true);
}
