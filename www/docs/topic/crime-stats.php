<?php

/**
 * Crime Statistics Topic Page
 */

namespace MySociety\TheyWorkForYou;

// Disable the old PAGE or NEWPAGE classes.
$new_style_template = TRUE;

// Include all the things this page needs.
include_once '../../includes/easyparliament/init.php';

// Topic page information
$data = array(
    'title' => 'Crime Statistics',
    'blurb' => 'MPs and Lords often talk about Crime Statistics, because they&rsquo;re a major
        political issue.'
);

// Topic action blocks
$data['actions'] = array(

    array(
        'title' => 'Anti-social Behaviour Crime and Policing Bill (second reading)',
        'icon'  => 'page',
        'href'  => 'http://www.theyworkforyou.com/lords/?id=2013-10-29a.1482.5',
        'blurb' => 'The House of Lords debate a proposed law, making many references to crime statistics.'
    ),

    array(
        'title' => 'Police and Public trust',
        'icon'  => 'comment-quotes',
        'href'  => 'http://www.theyworkforyou.com/lords/?id=2013-11-28a.1576.0',
        'blurb' => 'A debate on police misconduct and how much the general public trust the police not to cover up crime statistics, mistakes and misbehaviour.'
    ),

    array(
        'title' => 'Search the whole site',
        'icon'  => 'magnifying-glass',
        'href'  => 'http://www.theyworkforyou.com/search/?s=%22crime+statistics%22',
        'blurb' => 'Search TheyWorkForYou to find mentions of crime statistics from all areas of the UK parliament. You may also filter your results by time, speaker and section.'
    ),

    array(
        'title' => 'Sign up for email alerts',
        'icon'  => 'megaphone',
        'href'  => 'http://www.theyworkforyou.com/alert/?alertsearch=%22crime+statistics%22',
        'blurb' => 'We&rsquo;ll let you know every time crime statistics are mentioned in Parliament.'
    )

);

// Send for rendering!
Renderer::output('topic/topic', $data);
