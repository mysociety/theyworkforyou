<?php

/**
 * Benefits Topic Page
 */

namespace MySociety\TheyWorkForYou;

// Disable the old PAGE or NEWPAGE classes.
$new_style_template = TRUE;

// Include all the things this page needs.
include_once '../../includes/easyparliament/init.php';

// Topic page information
$data = array(
    'title' => 'Benefits',
    'blurb' => 'Benefits are a major political issue right now - they are mentioned a lot
                in Parliament, so it can be hard to know exactly where to find the
                important debates.'
);

// Topic action blocks
$data['actions'] = array(

    array(
        'title' => 'Universal Credit Regulations',
        'icon'  => 'comment-quotes',
        'href'  => 'http://www.theyworkforyou.com/lords/?id=2013-02-13a.664.3',
        'blurb' => 'Lords debate, and approve, the consolidation of all benefits into the
                     Universal Credit system.'
    ),

    array(
        'title' => 'Welfare Benefits Up-rating Bill',
        'icon'  => 'page',
        'href'  => 'http://www.theyworkforyou.com/lords/?id=2013-02-11a.457.8',
        'blurb' => 'Lords debate a cap on annual increases to working-age benefits.'
    ),

    array(
        'title' => 'Search the whole site',
        'icon'  => 'magnifying-glass',
        'href'  => 'http://www.theyworkforyou.com/search/?s=%22benefits%22',
        'blurb' => 'Search TheyWorkForYou to find mentions of benefits from all areas of the UK parliament. You may also filter your results by time, speaker and section.'
    ),

    array(
        'title' => 'Sign up for email alerts',
        'icon'  => 'megaphone',
        'href'  => 'http://www.theyworkforyou.com/alert/?alertsearch=%22benefits%22',
        'blurb' => 'We&rsquo;ll let you know every time benefits are mentioned in Parliament.'
    )

);

// Send for rendering!
Renderer::output('topic/topic', $data);
