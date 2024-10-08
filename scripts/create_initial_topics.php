#!/usr/bin/php

<?php

include_once dirname(__FILE__) . '/../www/includes/easyparliament/init.php';

$topics = new MySociety\TheyWorkForYou\Topics();

$all_topics = [
    'benefits' => [
        'title'       => 'Benefits',
        'page'        => 'topicbenefits',
        'blurb'       => 'Benefits are a major political issue right now - they
            are mentioned a lot in Parliament, so it can be hard to know exactly
            where to find the important debates.',
        'policyset'   => 'welfare',
        'policytitle' => 'Welfare and Benefits',
        'actions'     => [

            [
                'title' => 'Universal Credit Regulations',
                'icon'  => 'debate',
                'href'  => 'https://www.theyworkforyou.com/lords/?id=2013-02-13a.664.3',
                'blurb' => 'Lords debate, and approve, the consolidation of all benefits into the
                             Universal Credit system.',
            ],

            [
                'title' => 'Welfare Benefits Up-rating Bill',
                'icon'  => 'bill',
                'href'  => 'https://www.theyworkforyou.com/lords/?id=2013-02-11a.457.8',
                'blurb' => 'Lords debate a cap on annual increases to working-age benefits.',
            ],

            [
                'title' => 'Search the whole site',
                'icon'  => 'search',
                'href'  => 'https://www.theyworkforyou.com/search/?s=%22benefits%22',
                'blurb' => 'Search TheyWorkForYou to find mentions of benefits. You may also filter your results by time, speaker and section.',
            ],

            [
                'title' => 'Sign up for email alerts',
                'icon'  => 'alert',
                'href'  => 'https://www.theyworkforyou.com/alert/?alertsearch=%22benefits%22',
                'blurb' => 'We&rsquo;ll let you know every time benefits are mentioned in Parliament.',
            ],

        ],

    ],

    'crime-stats' => [
        'title' => 'Crime Statistics',
        'page'  => 'topiccrimestats',
        'blurb' => 'MPs and Lords often talk about Crime Statistics, because
            they&rsquo;re a major political issue.',
        'actions' => [

            [
                'title' => 'Anti-social Behaviour Crime and Policing Bill (second reading)',
                'icon'  => 'bill',
                'href'  => 'https://www.theyworkforyou.com/lords/?id=2013-10-29a.1482.5',
                'blurb' => 'The House of Lords debate a proposed law, making many references to crime statistics.',
            ],

            [
                'title' => 'Police and Public trust',
                'icon'  => 'debate',
                'href'  => 'https://www.theyworkforyou.com/lords/?id=2013-11-28a.1576.0',
                'blurb' => 'A debate on police misconduct and how much the general public trust the police not to cover up crime statistics, mistakes and misbehaviour.',
            ],

            [
                'title' => 'Search the whole site',
                'icon'  => 'search',
                'href'  => 'https://www.theyworkforyou.com/search/?s=%22crime+statistics%22',
                'blurb' => 'Search TheyWorkForYou to find mentions of crime statistics. You may also filter your results by time, speaker and section.',
            ],

            [
                'title' => 'Sign up for email alerts',
                'icon'  => 'alert',
                'href'  => 'https://www.theyworkforyou.com/alert/?alertsearch=%22crime+statistics%22',
                'blurb' => 'We&rsquo;ll let you know every time crime statistics are mentioned in Parliament.',
            ],

        ],

    ],

    'nhs' => [
        'title'       => 'The NHS',
        'page'        => 'topicnhs',
        'blurb'       => 'The NHS is a major political issue right now &mdash;
            it&rsquo;s mentioned a lot in Parliament, so it can be hard to know
            exactly where to find the important debates.',
        'policyset'   => 'health',
        'policytitle' => 'Healthcare',
        'actions'     => [

            [
                'title' => 'Health and Social Care Bill',
                'icon'  => 'debate',
                'href'  => 'https://www.theyworkforyou.com/debates/?id=2011-01-31b.605.0',
                'blurb' => 'Andrew Lansley, Secretary of State for Health, sets out plans for a reorganisation of the NHS, which MPs then debate and vote on.',
            ],

            [
                'title' => 'NHS (Private Sector)',
                'icon'  => 'debate',
                'href'  => 'https://www.theyworkforyou.com/debates/?id=2012-01-16a.536.0',
                'blurb' => 'A year later, the opposition puts forward its concerns with the model, ending in a further vote.',
            ],

            [
                'title' => 'Search the whole site',
                'icon'  => 'search',
                'href'  => 'https://www.theyworkforyou.com/search/?s=%22nhs%22',
                'blurb' => 'Search TheyWorkForYou to find mentions of the NHS. You may also filter your results by time, speaker and section.',
            ],

            [
                'title' => 'Sign up for email alerts',
                'icon'  => 'alert',
                'href'  => 'https://www.theyworkforyou.com/alert/?alertsearch=%nhs%22',
                'blurb' => 'We&rsquo;ll let you know every time the NHS is mentioned in Parliament.',
            ],

        ],

    ],
];

foreach ($all_topics as $name => $topic) {
    $existing = $topics->getTopic($name);
    if ($existing) {
        print "$name already exists\n";
    } else {
        $topic['slug'] = $name;
        $topic['description'] = $topic['blurb'];
        $topic = new MySociety\TheyWorkForYou\Topic($topic);
        $topic->save();
        print "created $name\n";
    }
}
