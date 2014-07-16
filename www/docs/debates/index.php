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

    if (preg_match('/speaker:(\d+)/', get_http_var('s'), $mmm))
        $args['person_id'] = $mmm[1];

    // Glossary can be turned off in the url
    if (get_http_var('ug') == 1) {
        $args['glossarise'] = 0;
    }
    else {
        $args['sort'] = "regexp_replace";
        $GLOSSARY = new GLOSSARY($args);
    }

    $SPEECHES = new DEBATELIST;
    $data['speeches'] = $SPEECHES->_get_data_by_gid($args);
    MySociety\TheyWorkForYou\Renderer::output('debate/debate', $data);

}

?>
