<?php

/*
 * debates/index.php
 *
 * Displays a debate or a list of debates.
 *
 * We use the DEBATELIST class (a subclass of HANSARDLIST)
 * essentially as a model, without calling its display() method,
 * because we handle rendering via the newer Renderer class.
 *
 */



// This script doesn't currently handle *all* of the /debates routes.
//
// This temporary bit of code does a check, and passes the request
// off to the existing index-old.php if we can't handle it yet.
//
// We have to do the check before easyparliament/init.php is included,
// because init.php does different things for "new style" and "old style"
// pages (see $new_style_template).

include_once dirname(__FILE__) . '/../../../conf/general';
include_once INCLUDESPATH . 'utility.php';
if (get_http_var('id') == '') {
    return include 'index-old.php';
}

// If we've got this far, we know we can handle
// the request with a "new style" page.



// Disable the old PAGE class.
$new_style_template = TRUE;

// Include all the things this page needs.
include_once '../../includes/easyparliament/init.php';
include_once INCLUDESPATH . 'easyparliament/glossary.php';
include_once INCLUDESPATH . 'easyparliament/member.php';

// Highlights search terms and creates glossary links in a string.
// $glossarise should be truthy if you want to create glossary links.
function annotate_speech($string, $glossarise){
    global $SEARCHENGINE;
    global $GLOSSARY;
    if (isset($SEARCHENGINE)) {
        // No need to define a search term here, since $SEARCHENGINE
        // has already been created with the search term from $args['s']
        $string = $SEARCHENGINE->highlight($string);
    }
    if (isset($GLOSSARY) && isset($glossarise) && $glossarise) {
        $GLOSSARY->glossarise($string);
    }
    return $string;
}

if (get_http_var('id') != '') {
    // We have an id so show that item.
    // Could be a section id (so we get a list of all the subsections in it),
    // or a subsection id (so we'd get the whole debate),
    // or an item id within a debate in which case we just get that item and some headings.

    $this_page = "debates";

    $args = array (
        'gid' => get_http_var('id'),
        's'	=> get_http_var('s'),	// Search terms to be highlighted.
        'member_id' => get_http_var('m'),	// Member's speeches to be highlighted.
        'glossarise' => 1	// Glossary is on by default
    );

    if (preg_match('/speaker:(\d+)/', get_http_var('s'), $mmm)) {
        $args['person_id'] = $mmm[1];
    }

    if (isset($args['s']) && $args['s'] != '') {
        global $SEARCHENGINE;
        $SEARCHENGINE = new SEARCHENGINE($args['s']);
    }

    // Glossary can be turned off in the url
    if (get_http_var('ug') == 1) {
        $args['glossarise'] = 0;
    } else {
        global $GLOSSARY;
        $GLOSSARY = new GLOSSARY(array(
            's' => $args['s'],
            'sort' => 'regexp_replace'
        ));
    }

    $SPEECHES = new DEBATELIST;
    $data['speeches'] = $SPEECHES->_get_data_by_gid($args);

    // See dptypes.php for a summary of what
    // these "major" types actually are.
    if ($data['speeches']['info']['major'] == 1) {
        $data['location'] = 'in the House of Commons';
    } elseif ($data['speeches']['info']['major'] == 2) {
        $data['location'] = 'in Westminster Hall';
    } elseif ($data['speeches']['info']['major'] == 3) {
        $data['location'] = 'in the House of Lords';
    } else {
        $data['location'] = '';
    }

    if (array_key_exists('text_heading', $data['speeches']['info'])) {
        // The user has requested a full debate
        $data['heading'] = 'Debate: ' . $data['speeches']['info']['text_heading'];
        $data['intro'] = 'This debate took place';
        $data['email_alert_text'] = $data['speeches']['info']['text_heading'];
    } else {
        // The user has requested only part of a debate, so find a suitable title
        foreach ($data['speeches']['rows'] as $row) {
            if ($row['htype'] == '11') {
                $data['heading'] = 'Debate: ' . $row['body'];
                $data['email_alert_text'] = $row['body'];
                $data['full_debate_url'] = $row['listurl'];
                break;
            }
        }
        if (!isset($data['heading'])) {
            // If we've not found a title, use the GID (better than nothing)
            $data['heading'] = 'Debate: ID ' . get_http_var('id');
            $data['email_alert_text'] = '';
        }
        $data['intro'] = 'This is part of a debate that took place';
    }

    $first_speech = $data['speeches']['rows'][0];
    foreach ($data['speeches']['rows'] as $row) {
        if ($row['htype'] == '12') {
            $first_speech = $row;
            break;
        }
    }

    $data['debate_time_human'] = format_time($first_speech['htime'], 'g:i a');
    $data['debate_day_human'] = format_date($first_speech['hdate'], 'jS F Y');
    $data['debate_day_link'] = '/debates/?d=' . $first_speech['hdate'];

    MySociety\TheyWorkForYou\Renderer::output('debate/debate', $data);

}

?>
