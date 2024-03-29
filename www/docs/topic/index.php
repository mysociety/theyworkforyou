<?php

/**
 * List Topic Pages
 */

namespace MySociety\TheyWorkForYou;

// Disable the old PAGE class.
$new_style_template = true;
global $this_page;
$this_page = 'topics';

// Include all the things this page needs.
include_once '../../includes/easyparliament/init.php';

// Array of topic page names (must exist in metadata.php) and titles to display.
$topics = new Topics();
$data['topics'] = $topics->getTopics();

// Send for rendering!
Renderer::output('topic/list', $data);
