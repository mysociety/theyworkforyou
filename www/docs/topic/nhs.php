<?php

/**
 * NHS Topic Page
 */

namespace MySociety\TheyWorkForYou;

// Disable the old PAGE or NEWPAGE classes.
$new_style_template = TRUE;

// Include all the things this page needs.
include_once '../../includes/easyparliament/init.php';

// Topic page information
$data = array(
    'title' => 'NHS',
    'blurb' => 'The NHS is a major political issue right now &mdash; it&rsquo;s mentioned a lot
    in Parliament, so it can be hard to know exactly where to find the important
    debates.'
);

// Topic action blocks
$data['actions'] = array(

    array(
        'title' => 'Health and Social Care Bill',
        'icon'  => 'comment-quotes',
        'href'  => 'http://www.theyworkforyou.com/debates/?id=2011-01-31b.605.0',
        'blurb' => 'Andrew Lansley, Secretary of State for Health, sets out plans for a reorganisation of the NHS, which MPs then debate and vote on.'
    ),

    array(
        'title' => 'NHS (Private Sector)',
        'icon'  => 'comment-quotes',
        'href'  => 'http://www.theyworkforyou.com/debates/?id=2012-01-16a.536.0',
        'blurb' => 'A year later, the opposition puts forward its concerns with the model, ending in a further vote.'
    ),

    array(
        'title' => 'Search the whole site',
        'icon'  => 'magnifying-glass',
        'href'  => 'http://www.theyworkforyou.com/search/?s=%nhs%22',
        'blurb' => 'Search TheyWorkForYou to find mentions of the NHS from all areas of the UK parliament. You may also filter your results by time, speaker and section.'
    ),

    array(
        'title' => 'Sign up for email alerts',
        'icon'  => 'megaphone',
        'href'  => 'http://www.theyworkforyou.com/alert/?alertsearch=%nhs%22',
        'blurb' => 'We&rsquo;ll let you know every time the NHS is mentioned in Parliament.'
    )

);

// Send for rendering!
Renderer::output('topic/topic', $data);
