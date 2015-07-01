<?php

include_once '../../includes/easyparliament/init.php';

if ($party = get_http_var('party')) {
    $party = ucwords(str_replace('_', ' ', $party));
    $party = str_replace(':', '/', $party);
    $party = new MySociety\TheyWorkForYou\Party($party);
    $template = 'party/index';

    if ( $policy = get_http_var('policy') ) {
        $template = 'party/member_votes';
    }

    $data = $party->display($policy);
    if ($data) {
        MySociety\TheyWorkForYou\Renderer::output($template, $data);
    }
} else {
    $PAGE->error_message("No party specified", true);
}
