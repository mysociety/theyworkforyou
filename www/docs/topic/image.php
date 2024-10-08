<?php

include_once '../../includes/easyparliament/init.php';

$topics = new \MySociety\TheyWorkForYou\Topics();
$slug = get_http_var('id');
if ($slug) {
    $topic = $topics->getTopic($slug);
}

if (!$slug || !isset($topic) || $topic === null) {
    header('HTTP/1.0 404 Not Found');
    exit();
}

$path = $topic->image_path();

if (!is_readable($path)) {
    header('HTTP/1.0 404 Not Found');
    exit();
}

$finfo = new finfo(FILEINFO_MIME);
$mime_info = $finfo->file($path);
$filesize = filesize($path);

header("Content-type: $mime_info");
header("Content-length: $filesize");
readfile($path);
