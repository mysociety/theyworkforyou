<?php

/**
 * List Topic Pages
 */

namespace MySociety\TheyWorkForYou;

// Disable the old PAGE or NEWPAGE classes.
$new_style_template = TRUE;

// Include all the things this page needs.
include_once '../../includes/easyparliament/init.php';

// Array of topic page names (must exist in metadata.php) and titles to display.
$data['topics'] = array(
    'topicbenefits'   => 'Benefits',
    'topiccrimestats' => 'Crime Statistics',
    'topicnhs'        => 'NHS'
);

// Send for rendering!
Renderer::output('topic/list', $data);
