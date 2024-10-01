<?php

// This file will be included by data.php
// The path of the file should be set as METADATAPATH in config.php.

// What are session_vars ?
// When generating a URL to a page using the URL class (in url.php), any
// GET variables for the page whose keys are listed in its session_vars below
// will automatically be put in the URL.

// For example, in this metadata we might have:
// 'search' => array (
// 		'url' => 'search/',
//		'sidebar' => 'search',
//		'session_vars' => array ('s')
// ),

// If we are at the URL www.domain.org/search/?s=blair&page=2
// and we used the URL class to generate a link to the search page like this:
// 		$URL = new \MySociety\TheyWorkForYou\Url('search');
//		$newurl = $URL->generate();

// then $newurl would be: /search/?s=blair
//
// sidebar:
// If you have a 'sidebar' element for a page then that page will have its content
// set to a restricted width and a sidebar will be inserted. The contents of this
// will be include()d from a file in template/sidebars/ of the name of the 'sidebar'
// value ('search.php' in the example above).

/* Items a page might have:

    menu		An array of 'text' and 'title' which are used if the page
                appears in the site menu.
    title		Used for the <title> and the page's heading on the page.
    heading		If present *this* is used for the page's heading on the page, in
                in place of the title.
    url			The URL from the site webroot for this page.
    parent		What page is this page's parent (see below).
    session_vars		If present, whenever a URL is generated to this page using the
                        URL class, any POST/GET variables with matching names are
                        automatically appended to the url.
    track (deprecated)     	Do we want to include the Extreme Tracker javascript on this page?
    rss			Does the content of this page (or some of it) have an RSS version?
                    If so, 'rss' should be set to '/a/path/to/the/feed.rdf'.


    PARENTS
    The site's menu has a top menu and a bottom, sub-menu. What is displayed in the
    sub-menu depends on which page is selected in the top menu. This is worked out
    from the bottom up, by looking at pages' parents. Here's an example top and bottom
    menu, with the capitalised items hilited:

    Home	HANSARD		Glossary	Help

        DEBATES		Written Answers

    If we were viewing a particular debate, we would be on the 'debate' page. The parent
    of this is 'debatesfront', which is the DEBATES link in the bottom menu - hence its
    hilite. The parent of 'debatesfront' is 'hansard', hence its hilite in the top menu.

    This may, of course, make no sense at all...

    If a page has no parent it is either in the top menu or no menu items should be hilited.
    The actual contents of each menu is determined in $PAGE->menu().

*/

$page =  [

    // Things used on EVERY page, unless overridden for a page:
    'default' =>  [
        'parent'	=> '',
        'session_vars' => ['super_debug'],
        'sitetitle'		=> 'TheyWorkForYou',
    ],

    // Every page on the site should have an entry below...

    // KEEP THE PAGES IN ALPHABETICAL ORDER! TA.

    'about' =>  [
        'title'			=> gettext('About us'),
        'url'			=> 'about/',
    ],
    'parliaments' =>  [
        'title' 	=> 'Parliaments and assemblies',
        'url'       => 'parliaments/',
    ],

    'alert_stats' =>  [
        'title'			=> 'Email alerts statistics',
        'parent'		=> 'admin',
        'url'			=> 'admin/alert_stats.php',
    ],
    'admin_badusers' =>  [
        'title'			=> 'Bad users',
        'parent'		=> 'admin',
        'url'			=> 'admin/badusers.php',
    ],
    'admin_home' =>  [
        'title'			=> 'Home',
        'parent'		=> 'admin',
        'url'			=> 'admin/',
    ],
    'admin_comments' =>  [
        'title'			=> 'Recent comments',
        'parent'		=> 'admin',
        'url'			=> 'admin/comments.php',
    ],
    'admin_commentreport' =>  [
        'title'			=> 'Processing a comment report',
        'parent'		=> 'admin',
        'url'			=> 'admin/report.php',
        'session_vars'	=>  ['rid', 'cid'],
    ],
    'admin_commentreports' =>  [
        'title'			=> 'Outstanding comment reports',
        'parent'		=> 'admin',
        'url'			=> 'admin/reports.php',
    ],
    'admin_failedsearches' =>  [
        'title'			=> 'Failed searches',
        'parent'		=> 'admin',
        'url'			=> 'admin/failedsearches.php',
    ],
    'admin_glossary' =>  [
        'title'			=> 'Manage glossary entries',
        'parent'		=> 'admin',
        'url'			=> 'admin/glossary.php',
    ],
    'admin_glossary_pending' =>  [
        'title'			=> 'Review pending glossary entries',
        'parent'		=> 'admin',
        'url'			=> 'admin/glossary_pending.php',
    ],
    'admin_searchlogs' =>  [
        'title'			=> 'Recent searches',
        'parent'		=> 'admin',
        'url'			=> 'admin/searchlogs.php',
    ],
    'admin_popularsearches' =>  [
        'title'			=> 'Popular searches in last 30 days (first 1000)',
        'parent'		=> 'admin',
        'url'			=> 'admin/popularsearches.php',
    ],
    'admin_statistics' =>  [
        'title'			=> 'General statistics',
        'parent'		=> 'admin',
        'url'			=> 'admin/statistics.php',
    ],
    'admin_reportstats' =>  [
        'title'			=> 'Reporting statistics',
        'parent'		=> 'admin',
        'url'			=> 'admin/reporting_stats.php',
    ],
    'admin_photos' =>  [
        'title'			=> 'Photo upload/attribution',
        'parent'		=> 'admin',
        'url'			=> 'admin/photos.php',
    ],
    'admin_profile_message' =>  [
        'title'			=> 'Profile message banner',
        'parent'		=> 'admin',
        'url'			=> 'admin/profile-message.php',
    ],
    'admin_mpurls' =>  [
        'title'			=> 'MP Websites',
        'parent'		=> 'admin',
        'url'			=> 'admin/websites.php',
    ],
    'admin_policies' =>  [
        'title'			=> 'MP Policy details',
        'parent'		=> 'admin',
        'url'			=> 'admin/policies.php',
    ],
    'admin_banner' =>  [
        'title'			=> 'Edit Banners',
        'parent'		=> 'admin',
        'url'			=> 'admin/banner.php?editorial_option=banner',
    ],
    'admin_announcement' =>  [
        'title'			=> 'Edit Announcements',
        'parent'		=> 'admin',
        'url'			=> 'admin/banner.php?editorial_option=announcements',
    ],
    'admin_featured' =>  [
        'title'			=> 'Featured debates',
        'parent'		=> 'admin',
        'url'			=> 'admin/featured.php',
    ],
    'admin_topics' =>  [
        'title'			=> 'Topics',
        'parent'		=> 'admin',
        'url'			=> 'admin/topics.php',
    ],
    'admin_edittopics' =>  [
        'title'			=> 'Edit Topic',
        'parent'		=> 'admin_topics',
        'url'			=> 'admin/edittopic.php',
    ],
    'admin_wikipedia' =>  [
        'title' => 'Wikipedia links',
        'parent' => 'admin',
        'url' => 'admin/wikipedia.php',
    ],

    // Added by Richard Allan for email alert functions

    'alert' =>  [
        'menu'			=>  [
            'text'			=> 'Email Alerts',
            'title'			=> "Set up alerts for updates on an MP or Peer by email",
            'sidebar'		=> 'alert',

        ],
        'title'			=> 'TheyWorkForYou Email Alerts',
        'url'			=> 'alert/',
    ],
    'alertwelcome' =>  [
        'title'			=> 'Email Alerts',
        'url'			=> 'alert/',
    ],

    // End of ALERTS additions

    'api_front'		=>  [
        'menu'			=>  [
            'text'			=> 'API',
            'title'			=> 'Access our data',
        ],
        'title'			=> 'TheyWorkForYou API',
        'url'			=> 'api/',
    ],
    'api_doc_front'		=>  [
        'menu'			=>  [
            'text'			=> 'API',
            'title'			=> 'Access our data',
        ],
        'parent'		=> 'api_front',
        'url'			=> 'api/',
    ],
    'api_key'		=>  [
        'title'			=> 'Plan and keys',
        'parent'		=> 'api_front',
        'url'			=> 'api/key',
    ],
    'api_invoices'		=>  [
        'title'			=> 'Invoices',
        'parent'		=> 'api_front',
        'url'			=> 'api/invoices',
    ],

    'boundaries' => [
        'title' => 'Constituency boundaries',
        'url' => 'boundaries/',
    ],

    'calendar_summary' =>  [
        'menu'			=>  [
            'text'			=> 'Upcoming',
            'title'			=> '',
        ],
        'parent'		=> 'hansard',
        'url'			=> 'calendar/',
    ],
    'calendar_future_head' =>  [
        'parent'		=> 'calendar_summary',
        'title'			=> 'Upcoming business',
        'url'			=> 'calendar/',
    ],
    'calendar_future' =>  [
        'parent'		=> 'calendar_future_head',
        'url'			=> 'calendar/',
    ],
    'calendar_today_head' =>  [
        'parent'		=> 'calendar_summary',
        'title'			=> 'Today&rsquo;s business',
        'url'			=> 'calendar/',
    ],
    'calendar_today' =>  [
        'parent'		=> 'calendar_today_head',
        'url'			=> 'calendar/',
    ],
    'calendar_past_head' =>  [
        'parent'		=> 'calendar_summary',
        'title'			=> 'Previous business',
        'url'			=> 'calendar/',
    ],
    'calendar_past' =>  [
        'parent'		=> 'calendar_past_head',
        'url'			=> 'calendar/',
    ],

    'cards' =>  [
        'title'			=> 'MP Stats Cards',
        'url'			=> 'cards/',
    ],

    'campaign_foi' =>  [
        'title'			=> 'Freedom of Information (Parliament) Order 2009',
        'url'			=> 'foiorder2009/',
    ],
    'campaign' =>  [
        'title'			=> '', #Free Our Bills!',
        'url'			=> 'freeourbills/',
    ],
    'campaign_edm' =>  [
        'title'			=> 'Early Day Motion',
        'parent'		=> 'campaign',
        'url'			=> 'freeourbills/',
    ],

    'commentreport' =>  [
        'title'			=> 'Reporting a comment',
        'url'			=> 'report/',
        'session_vars'	=>  ['id'],
    ],

    'comments_recent' =>  [
        'menu'			=>  [
            'text'			=> 'Recent comments',
            'title'			=> "Recently posted comments",
        ],
        'parent'		=> 'home',
        'title'			=> "Recent comments",
        'url'			=> 'comments/recent/',
    ],

    'contact' =>  [
        'menu'			=>  [
            'text'			=> gettext('Contact'),
            'title'			=> '',
        ],
        'title'			=> gettext('Contact'),
        'url'			=> 'contact/',
    ],
    'news' => [
        'title' => gettext('News'),
        'url' => 'https://www.mysociety.org/category/projects/theyworkforyou/',
    ],
    'debate'  =>  [
        'parent'		=> 'debatesfront',
        'url'			=> 'debates/',
        'session_vars'	=>  ['id'],
    ],
    'debates'  =>  [
        'parent'		=> 'debatesfront',
        'url'			=> 'debates/',
        'session_vars'	=>  ['id'],
    ],
    'debatesday' =>  [
        'parent'		=> 'debatesfront',
        'session_vars'	=>  ['d'],
        'url'			=> 'debates/',
    ],
    'alldebatesfront' =>  [
        'menu'			=>  [
            'text'			=> 'Debates',
            'title'			=> "Debates in the House of Commons, Westminster Hall, and the House of Lords",
        ],
        'parent'		=> 'hansard',
        'title'			=> 'UK Parliament Hansard Debates',
        'rss'			=> 'rss/debates.rss',
        'url'			=> 'debates/',
    ],
    'debatesfront' =>  [
        'menu'			=>  [
            'text'			=> 'Commons debates',
            'title'			=> "Debates in the House of Commons",
        ],
        'parent'		=> 'alldebatesfront',
        'title'			=> 'House of Commons debates',
        'rss'			=> 'rss/debates.rss',
        'url'			=> 'debates/',
    ],
    'debatesyear' =>  [
        'parent'		=> 'debatesfront',
        'url'			=> 'debates/',
    ],
    'divisions_recent' =>  [
        'menu'			=>  [
            'text'			=> 'Recent Votes',
            'title'			=> '',
        ],
        'parent'		=> 'hansard',
        'title'			=> 'Recent Votes',
        'url'			=> 'divisions/',
    ],
    'divisions_recent_commons' =>  [
        'menu'			=>  [
            'text'			=> 'Recent Votes (Commons)',
            'title'			=> 'Recent Votes (Commons)',
        ],
        'parent'		=> 'hansard',
        'title'			=> 'Recent Votes',
        'url'			=> 'divisions/?house=commons',
    ],
    'divisions_recent_lords' =>  [
        'menu'			=>  [
            'text'			=> 'Recent Votes (Lords)',
            'title'			=> 'Recent Votes (Lords)',
        ],
        'parent'		=> 'hansard',
        'title'			=> 'Recent Votes',
        'url'			=> 'divisions/?house=lords',
    ],
    'divisions_recent_wales' =>  [
        'menu'			=>  [
            'text'			=> gettext('Recent Votes'),
            'title'			=> gettext('Recent Votes'),
        ],
        'parent'		=> 'wales_home',
        'title'			=> gettext('Recent Votes'),
        'url'			=> 'divisions/?house=senedd',
    ],
    'divisions_recent_sp' =>  [
        'menu'			=>  [
            'text'			=> 'Recent Votes',
            'title'			=> 'Recent Votes',
        ],
        'parent'		=> 'sp_home',
        'title'			=> 'Recent Votes',
        'url'			=> 'divisions/?house=scotland',
    ],
    'divisions_vote' =>  [
        'parent'		=> 'divisions_recent',
        'title'			=> 'Vote',
        'url'			=> 'divisions/division.php',
        'session_vars'	=> ['vote'],
    ],
    'donate' =>  [
        'menu'			=>  [
            'text'			=> gettext('Donate'),
            'title'			=> '',
        ],
        'title'			=> gettext('Donate'),
        'url'			=> 'support-us/',
    ],
    'epvote' =>  [
        'url'			=> 'vote/',
    ],

    'gadget' => [
        'url'			=> 'gadget/',
        'title'			=> 'TheyWorkForYou Google gadget',
    ],

    'glossary' =>  [
        'heading'		=> 'Glossary',
        'parent'		=> 'help_us_out',
        'url'			=> 'glossary/',
    ],
    'glossary_item' =>  [
        'heading'		=> 'Glossary heading',
        'parent'		=> 'help_us_out',
        'url'			=> 'glossary/',
        'session_vars'	=>  ['g'],
    ],
    'hansard' =>  [
        'menu'			=>  [
            'text'			=> 'UK Parliament',
            'title'			=> "Houses of Parliament debates, Written Answers, Statements, Westminster Hall debates, and Bill Committees",
        ],
        'title'			=> '',
        'url'			=> '',
    ],
    // Hansard landing page
    'hansard_landing' =>  [
        'title'                 => 'Hansard',
        'url'                   => 'search-hansard/',
    ],
    'help' =>  [
        'title'			=> gettext('Help - Frequently Asked Questions'),
        'url'			=> 'help/',
    ],
    'help_us_out' =>  [
        'menu'			=>  [
            'text'			=> 'Glossary',
            'title'			=> "Parliament's jargon explained",
        ],
        'title'			=> 'Glossary',
        'heading'		=> 'Add a glossary item',
        'url'			=> 'addterm/',
        'sidebar'		=> 'glossary_add',
    ],
    'home' =>  [
        'title'			=> "UK Parliament",
        'rss'			=> 'news/index.rdf',
        'url'			=> '',
    ],
    'houserules' =>  [
        'title'			=> 'House rules',
        'url'			=> 'houserules/',
    ],

    'linktous' =>  [
        'title'			=> gettext('Link to us'),
        'heading'		=> gettext('How to link to us'),
        'url'			=> 'help/linktous/',
    ],
    'api' =>  [
        'title'			=> gettext('API'),
        'heading'		=> gettext('API - Query the TheyWorkForYou database'),
        'url'			=> 'api/',
    ],
    'data' =>  [
        'title'			=> gettext('Raw Data'),
        'heading'		=> gettext('Raw data (XML) - the data behind TheyWorkForYou and Public Whip'),
        'url'			=> 'https://parser.theyworkforyou.com',
    ],
    'devmailinglist' =>  [
        'title'			=> gettext('Developer mailing list'),
        'url'			=> 'https://groups.google.com/a/mysociety.org/forum/#!forum/theyworkforyou',
    ],
    'code' =>  [
        'title'			=> gettext('Source code'),
        'heading'		=> gettext('TheyWorkForYou Source code'),
        'url'			=> 'https://github.com/mysociety/theyworkforyou',
    ],
    'australia' =>  [
        'title'			=> 'Australia',
        'heading'		=> 'Open Australia',
        'url'			=> 'https://www.openaustralia.org/',
    ],
    'ireland' =>  [
        'title'			=> 'Ireland',
        'heading'		=> 'TheyWorkForYou for the Houses of the Oireachtas',
        'url'			=> 'https://www.kildarestreet.com/',
    ],
    'mzalendo' =>  [
        'title'			=> 'Mzalendo',
        'heading'		=> 'Keeping an eye on the Kenyan Parliament',
        'url'			=> 'https://info.mzalendo.com/',
    ],
    'lordsdebate'  =>  [
        'parent'		=> 'lordsdebatesfront',
        'url'			=> 'lords/',
        'session_vars'	=>  ['id'],
    ],
    'lordsdebates'  =>  [
        'parent'		=> 'lordsdebatesfront',
        'url'			=> 'lords/',
        'session_vars'	=>  ['id'],
    ],
    'lordsdebatesday' =>  [
        'parent'		=> 'lordsdebatesfront',
        'session_vars'	=>  ['d'],
        'url'			=> 'lords/',
    ],
    'lordsdebatesfront' =>  [
        'menu'			=>  [
            'text'			=> 'Lords debates',
            'title'			=> "House of Lords debates",
        ],
        'parent'		=> 'alldebatesfront',
        'title'			=> 'House of Lords debates',
        'rss'			=> 'rss/lords.rss',
        'url'			=> 'lords/',
    ],
    'lordsdebatesyear' =>  [
        'parent'		=> 'lordsdebatesfront',
        'url'			=> 'lords/',
    ],

    // Parliament landing page
    'parliament_landing' =>  [
        'title'                 => 'Parliament',
        'url'                   => 'parliament/',
    ],

    'peer' =>  [
        'parent'		=> 'peers',
        'title'			=> 'Peer',
        'url'			=> 'peer/',
    ],
    'peers' =>  [
        'menu'			=>  [
            'text'			=> 'Lords',
            'title'			=> "List of Lords",
        ],
        'parent'		=> 'hansard',
        'title'			=> '',
        'url'			=> 'peers/',
    ],
    'overview' =>  [
        'menu'			=>  [
            'text'			=> 'Overview',
            'title'			=> "Overview of the UK Parliament",
        ],
        'parent'		=> 'hansard',
        'title'			=> "Hansard and Official Reports for the UK Parliament, Scottish Parliament, and Northern Ireland Assembly - done right",
        'rss'			=> 'news/index.rdf',
        'url'			=> '',
    ],
    'mla' =>  [
        'parent'		=> 'mlas',
        'title'			=> 'Find your MLA',
        'url'			=> 'mla/',
    ],
    'mlas' =>  [
        'parent'		=> 'ni_home',
        'menu'			=>  [
            'text'			=> 'MLAs',
            'title'			=> "List of Members of the Northern Ireland Assembly (MLAs)",
        ],
        'title'			=> '',
        'url'			=> 'mlas/',
    ],
    'msps' =>  [
        'parent'		=> 'sp_home',
        'menu'			=>  [
            'text'			=> 'MSPs',
            'title'			=> "List of Members of the Scottish Parliament (MSPs)",
        ],
        'title'			=> '',
        'url'			=> 'msps/',
    ],
    'msp' =>  [
        'parent'		=> 'msps',
        'title'			=> 'Find your MSP',
        'url'			=> 'msp/',
    ],
    /* Not 'Your MP', whose name is 'yourmp'... */
    'mp' =>  [
        'parent'			=> 'mps',
        'title'			=> 'Find your MP',
        'url'			=> 'mp/',
    ],
    'emailfriend' =>  [
        'title'			=> 'Send this page to a friend',
        'url'			=> 'email/',
    ],
    // The directory MPs' RSS feeds are stored in.
    'mp_rss' =>  [
        'url'			=> 'rss/mp/',
    ],
    'mps' =>  [
        'menu'			=>  [
            'text'			=> 'MPs',
            'title'			=> "List of Members of Parliament (MPs)",
        ],
        'parent'		=> 'hansard',
        'title'			=> '',
        'url'			=> 'mps/',
    ],

    /* Northern Ireland Assembly */
    'ni_home' => [
        'menu'			=>  [
            'text'			=> 'Northern Ireland Assembly',
            'title'			=> 'Full authority over <em>transferred matters</em>, which include agriculture, education, employment, the environment and health',
        ],
        'title'			=> 'Northern Ireland Assembly',
        'url'			=> 'ni/',
    ],
    'nioverview' =>  [
        'parent'		=> 'ni_home',
        'menu'			=>  [
            'text'			=> 'Debates',
            'title'			=> "Debates in the Northern Ireland Assembly",
        ],
        'title'			=> '',
        'rss'			=> 'rss/ni.rss',
        'url'			=> 'ni/?more=1',
    ],
    'nidebate'  =>  [
        'parent'		=> 'nidebatesfront',
        'url'			=> 'ni/',
        'session_vars'	=>  ['id'],
    ],
    'nidebates'  =>  [
        'parent'		=> 'nidebatesfront',
        'url'			=> 'ni/',
        'session_vars'	=>  ['id'],
    ],
    'nidebatesday' =>  [
        'parent'		=> 'nidebatesfront',
        'session_vars'	=>  ['d'],
        'url'			=> 'ni/',
    ],
    'nidebatesfront' =>  [
        'menu'			=>  [
            'text'			=> 'Debates',
            'title'			=> "Northern Ireland Assembly debates",
        ],
        'parent'		=> 'nioverview',
        'title'			=> 'Northern Ireland Assembly debates',
        'rss'			=> 'rss/ni.rss',
        'url'			=> 'ni/',
    ],
    'nidebatesyear' =>  [
        'parent'		=> 'nidebatesfront',
        'url'			=> 'ni/',
    ],

    /* London Assembly */

    'london_home' => [
        'menu'          =>  [
            'text'      => 'London Assembly',
            'title'     => 'Members of the London Assembly, answers from the Mayor of London',
        ],
        'title'         => 'London Assembly',
        'url'           => 'london/',
    ],

    'london-assembly-members' =>  [
        'parent'        => 'london_home',
        'menu'          =>  [
            'text'      => 'London Assembly Members',
            'title'     => "List of Members of the London Assembly)",
        ],
        'title'         => '',
        'url'           => 'london-assembly-members/',
    ],

    'london-assembly-member' =>  [
        'parent'        => 'london-assembly-members',
        'title'         => 'Find your London Assembly Member',
        'url'           => 'london-assembly-member/',
    ],

    'lmqs' =>  [
        'parent'		=> 'london_home',
        'url'			=> 'london/',
        'session_vars'	=>  ['id'],
    ],
    'lmqsday'  =>  [
        'parent'		=> 'london_home',
        'url'			=> 'london/',
    ],
    'lmqsfront' =>  [
        'parent'		=> 'london_home',
        'menu'			=>  [
            'text'			=> 'Questions to the Mayor of London',
            'title'			=> "Questions to the Mayor of London",
        ],
        'title'			=> 'questions to the Mayor of London',
        'url'			=> 'london/',
    ],
    'lmqsyear' =>  [
        'parent'		=> 'london_home',
        'url'			=> 'london/',
    ],

    'otheruseredit' =>  [
        'pg'			=> 'editother',
        'title'			=> "Editing a user's data",
        'url'			=> 'user/',
    ],
    'privacy' =>  [
        'title'			=> gettext('Privacy Policy'),
        'url'			=> 'privacy/',
    ],

    /* Public bill committees */
    'pbc_front' =>  [
        'menu'			=>  [
            'text'			=> 'Bill Committees',
            'title'			=> "Public Bill Committees (formerly Standing Committees) debates",
        ],
        'parent'		=> 'hansard',
        'title'			=> 'Public Bill Committees',
        'rss'			=> 'rss/pbc.rss',
        'url'			=> 'pbc/',
    ],
    'pbc_session' => [
        'title' => 'Session',
        'url' => 'pbc/',
        'parent' => 'pbc_front',
    ],
    'pbc_bill' => [
        'title' => '',
        'url' => 'pbc/',
        'parent' => 'pbc_front',
        'session_vars'	=>  ['bill'],
    ],
    'pbc_clause' => [
        'parent'		=> 'pbc_front',
        'url'			=> 'pbc/',
        'session_vars'	=>  ['id'],
    ],
    'pbc_speech' => [
        'parent'		=> 'pbc_front',
        'url'			=> 'pbc/',
        'session_vars'	=>  ['id'],
    ],

    'people' => [
        'menu' => [
            'text' => 'People',
            'title' => '',
        ],
        'title'			=> 'Representatives',
        'url'			=> '',
    ],

    'raw' =>  [
        'title'			=> 'Raw data',
        'url'			=> 'raw/',
    ],

    'regmem' =>  [
        'title'			=> 'Changes to the Register of Members\' Interests',
        'url'			=> 'regmem/',
    ],

    'regmem_date' =>  [
        'url'			=> 'regmem/',
        'parent'		=> 'regmem',
    ],

    'regmem_mp' =>  [
        'url'			=> 'regmem/',
        'parent'		=> 'regmem',
    ],

    'regmem_diff' =>  [
        'url'			=> 'regmem/',
        'parent'		=> 'regmem',
    ],

    'royal' =>  [
        'parent'        => 'hansard',
        'title'         => 'Royal',
        'url'           => 'royal/',
    ],

    'topic' =>  [
        'parent'        => 'topics',
        'url'           => 'topic/topic.php',
    ],

    'topics' =>  [
        'title'        => 'Topics',
        'url'          => 'topic/',
    ],

    'search'		=>  [
        'sidebar'		=> 'search',
        'url'			=> 'search/',
        'robots'		=> 'noindex, nofollow',
        'heading'		=> '',
        'session_vars'	=>  ['q', 'o', 'pop'],
    ],

    'sitenews'		=>  [
        'menu'			=>  [
            'text'			=> 'TheyWorkForYou news',
            'title'			=> "News about changes to this website",
        ],
        'parent'		=> 'home',
        'rss'			=> 'news/index.rdf',
        'sidebar'		=> 'sitenews',
        'title'			=> 'TheyWorkForYou news',
        'url'			=> 'news/',
    ],
    'sitenews_archive'		=>  [
        'parent'		=> 'sitenews',
        'rss'			=> 'news/index.rdf',
        'sidebar'		=> 'sitenews',
        'title'			=> 'Archive',
        'url'			=> 'news/archives/',
    ],
    'sitenews_atom' 	=>  [
        'url'			=> 'news/atom.xml',
    ],
    'sitenews_date'	=>  [
        'parent'		=> 'sitenews',
        'rss'			=> 'news/index.rdf',
        'sidebar'		=> 'sitenews',
    ],
    'sitenews_individual'	=>  [
        'parent'		=> 'sitenews',
        'rss'			=> 'news/index.rdf',
        'sidebar'		=> 'sitenews',
    ],
    'sitenews_rss1' 	=>  [
        'url'			=> 'news/index.rdf',
    ],
    'sitenews_rss2' 	=>  [
        'url'			=> 'news/index.xml',
    ],

    /* Scottish Parliament */
    'sp_home' => [
        'menu'			=>  [
            'text'			=> 'Scottish Parliament',
            'title'			=> 'Scottish education, health, agriculture, justice, prisons and other devolved areas. Some tax-varying powers',
        ],
        'title'			=> 'Scottish Parliament',
        'url'			=> 'scotland/',
    ],
    'spoverview' =>  [
        'parent'		=> 'sp_home',
        'menu'			=>  [
            'text'			=> 'Overview',
            'title'			=> "Overview of the Scottish Parliament",
        ],
        'title'			=> '',
        'url'			=> 'scotland/',
    ],
    'spdebate'  =>  [
        'parent'		=> 'spdebatesfront',
        'url'			=> 'sp/',
        'session_vars'	=>  ['id'],
    ],
    'spdebates'  =>  [
        'parent'		=> 'spdebatesfront',
        'url'			=> 'sp/',
        'session_vars'	=>  ['id'],
    ],
    'spdebatesday' =>  [
        'parent'		=> 'spdebatesfront',
        'session_vars'	=>  ['d'],
        'url'			=> 'sp/',
    ],
    'spdebatesfront' =>  [
        'menu'			=>  [
            'text'			=> 'Debates',
            'title'			=> 'Debates in the Scottish Parliament',
        ],
        'parent'		=> 'sp_home',
        'title'			=> 'Scottish Parliament debates',
        'url'			=> 'sp/',
    ],

    'spdebatesyear' =>  [
        'parent'		=> 'spdebatesfront',
        'url'			=> 'sp/',
    ],
    'spwrans'  =>  [
        'parent'		=> 'spwransfront',
        'url'			=> 'spwrans/',
        #'session_vars'	=> array ('id'),
    ],
    'spwransday'  =>  [
        'parent'		=> 'spwransfront',
        'url'			=> 'spwrans/',
    ],
    'spwransfront'  =>  [
        'menu'			=>  [
            'text'			=> 'Written Answers',
            'title'			=> 'Written Answers and Statements',
        ],
        'parent'		=> 'sp_home',
        'title'			=> 'Scottish Parliament Written answers',
        'url'			=> 'spwrans/',
    ],
    'spwransmp' => [
        'parent'		=> 'spwransfront',
        'title'			=> 'For questions asked by ',
        'url'			=> 'spwrans/',
    ],
    'spwransyear' =>  [
        'parent'		=> 'spwransfront',
        'url'			=> 'spwrans/',
    ],

    // Topic pages

    'topic' =>  [
        'url'           => 'topic/',
        'title'         => 'Topics',
    ],

    'topicbenefits' =>  [
        'url'           => 'topic/benefits',
        'parent'        => 'topic',
        'title'         => 'Benefits',
    ],

    'topiccrimestats' =>  [
        'url'           => 'topic/crime-stats',
        'parent'        => 'topic',
        'title'         => 'Crime Statistics',
    ],

    'topicnhs' =>  [
        'url'           => 'topic/nhs',
        'parent'        => 'topic',
        'title'         => 'NHS',
    ],

    'useralerts' => [
        'menu'			=> [
            'text'			=> 'Email Alerts',
            'title'			=> 'Check your email alerts',
        ],
        'title'			=> 'Your Email Alerts',
        'url'			=> 'user/alerts/',
        'parent'		=> 'userviewself',
    ],
    'userchangepc' =>  [
        'title'			=> 'Change your postcode',
        'url'			=> 'user/changepc/',
    ],
    'userconfirm' =>  [
        'url'			=> 'user/confirm/',
    ],
    'userconfirmed' =>  [
        'sidebar'		=> 'userconfirmed',
        'title'			=> 'Welcome to TheyWorkForYou!',
        'url'			=> 'user/confirm/',
    ],
    'userconfirmfailed' =>  [
        'title'			=> 'Oops!',
        'url'			=> 'user/confirm/',
    ],
    'useredit' =>  [
        'pg'			=> 'edit',
        'title'			=> 'Edit your details',
        'url'			=> 'user/',
    ],
    'userjoin' =>  [
        'menu'                  =>  [
            'text'                  => gettext('Join'),
            'title'                 => gettext("Joining is free and allows you to manage your email alerts"),
        ],
        'pg'                    => 'join',
        'sidebar'               => 'userjoin',
        'title'                 => gettext('Join TheyWorkForYou'),
        'url'                   => 'user/',
    ],
    'userlogin' =>  [
        'menu'			=>  [
            'text'			=> gettext('Sign in'),
            'title'			=> gettext("If you've already joined, sign in to your account"),
        ],
        'sidebar'		=> 'userlogin',
        'title'			=> gettext('Sign in'),
        'url'			=> 'user/login/',
    ],

    'userlogout' =>  [
        'menu'			=>  [
            'text'			=> 'Sign out',
            'title'			=> "Sign out",
        ],
        'url'			=> 'user/logout/',
    ],
    'userpassword' =>  [
        'title'			=> 'Change password',
        'url'			=> 'user/password/',
    ],
    'userprompt' =>  [
        'title'			=> 'Please sign in',
        'url'			=> 'user/prompt/',
    ],
    'userview' =>  [
        'session_vars'	=> ['u'],
        'url'			=> 'user/',
    ],
    'userviewself' =>  [
        'menu'			=>  [
            'text'			=> 'Your details',
            'title'			=> "View and edit your details",
        ],
        'url'			=> 'user/',
    ],
    'userwelcome' =>  [
        'title'			=> 'Welcome!',
        'url'			=> 'user/',
    ],

    /* Welsh Parliament */
    'wales_home' => [
        'menu' => [
            'text' => 'Senedd / Welsh Parliament',
            'title' => 'Welsh economic development, transport, finance, local government, health, housing, the Welsh Language and other devolved areas',
        ],
        'title' => 'Senedd Cymru / Welsh Parliament',
        'url' => 'senedd/',
    ],
    'mss' =>  [
        'parent' => 'wales_home',
        'menu' =>  [
            'text' => gettext('MSs'),
            'title' => gettext("List of Members of the Senedd (MSs)"),
        ],
        'title' => '',
        'url' => 'mss/',
    ],
    'wales_debates' =>  [
        'parent' => 'wales_home',
        'menu' =>  [
            'text' => gettext('Debates'),
            'title' => gettext("Debates in the Senedd"),
        ],
        'title' => '',
        'url' => 'senedd/?more=1',
    ],
    'ms' =>  [
        'parent' => 'mss',
        'title' => gettext('Find your MS'),
        'url' => 'ms/',
    ],
    'yourms' =>  [
        'menu'			=>  [
            'text'		=> gettext('Your MSs'),
            'title'		=> gettext("Find out about your Members of the Senedd"),
        ],
        #'parent'		=> 'yourreps',
        'sidebar'		=> 'yourms',
        'title'			=> gettext('Your MSs'),
        'url'			=> 'ms/',
    ],

    'seneddoverview' =>  [
        'parent' => 'wales_home',
        'menu' =>  [
            'text' => gettext('Overview'),
            'title' => gettext("Overview of the Senedd debates"),
        ],
        'title' => '',
        'rss' => 'rss/senedd.rss',
        'url' => 'senedd/',
    ],
    'senedddebate'  =>  [
        'parent' => 'senedddebatesfront',
        'url' => 'senedd/',
        'session_vars' =>  ['id'],
    ],
    'senedddebates'  =>  [
        'parent' => 'senedddebatesfront',
        'url' => 'senedd/',
        'session_vars' =>  ['id'],
    ],
    'senedddebatesday' =>  [
        'parent' => 'senedddebatesfront',
        'session_vars' =>  ['d'],
        'url' => 'senedd/',
    ],
    'senedddebatesfront' =>  [
        'menu' =>  [
            'text' => 'Debates',
            'title' => gettext("Senedd debates"),
        ],
        'parent' => 'seneddoverview',
        'title' => gettext('Senedd debates'),
        'rss' => 'rss/senedd.rss',
        'url' => 'senedd/',
    ],
    'senedddebatesyear' =>  [
        'parent' => 'senedddebatesfront',
        'url' => 'senedd/',
    ],

    /* Westminster Hall */
    'whall'  =>  [
        'parent'		=> 'whallfront',
        'url'			=> 'whall/',
        'session_vars'	=>  ['id'],
    ],
    'whalls'  =>  [
        'parent'		=> 'whallfront',
        'url'			=> 'whall/',
        'session_vars'	=>  ['id'],
    ],
    'whallday' =>  [
        'parent'		=> 'whallfront',
        'session_vars'	=>  ['d'],
        'url'			=> 'whall/',
    ],
    'whallfront' =>  [
        'menu'			=>  [
            'text'			=> 'Westminster Hall',
            'title'			=> "Westminster Hall debates",
        ],
        'parent'		=> 'alldebatesfront',
        'title'			=> 'Westminster Hall debates',
        'rss'			=> 'rss/whall.rss',
        'url'			=> 'whall/',
    ],
    'whallyear' =>  [
        'parent'		=> 'whallfront',
        'url'			=> 'whall/',
    ],

    'wms' =>  [
        'parent'		=> 'wranswmsfront',
        'url'			=> 'wms/',
        'session_vars'	=> ['id'],
    ],
    'wmsday' =>  [
        'parent'		=> 'wmsfront',
        'session_vars'	=> ['d'],
        'url'			=> 'wms/',
    ],
    'wmsfront' =>  [
        'menu'			=>  [
            'text'			=> 'Written Ministerial Statements',
            'title'			=> '',
        ],
        'parent'		=> 'wranswmsfront',
        'title'			=> 'Written Ministerial Statements',
        'rss'			=> 'rss/wms.rss',
        'url'			=> 'wms/',
    ],
    'wmsyear' =>  [
        'parent'		=> 'wmsfront',
        'url'			=> 'wms/',
    ],

    'wrans'  =>  [
        'parent'		=> 'wranswmsfront',
        'url'			=> 'wrans/',
        'session_vars'	=>  ['id'],
    ],
    'wransday'  =>  [
        'parent'		=> 'wransfront',
        'url'			=> 'wrans/',
    ],
    'wransfront'  =>  [
        'menu'			=>  [
            'text'			=> 'Written Answers',
            'title'			=> "Written Answers",
        ],
        'parent'		=> 'wranswmsfront',
        'title'			=> 'Written answers',
        'url'			=> 'wrans/',
    ],
    'wransmp' => [
        'parent'		=> 'wransfront',
        'title'			=> 'For questions asked by ',
        'url'			=> 'wrans/',
    ],
    'wransyear' =>  [
        'parent'		=> 'wransfront',
        'url'			=> 'wrans/',
    ],

    'wranswmsfront'  =>  [
        'menu'			=>  [
            'text'			=> 'Written Answers',
            'title'			=> 'Written Answers and Statements',
        ],
        'parent'		=> 'hansard',
        'title'			=> 'Hansard Written Answers',
        'url'			=> 'written-answers-and-statements/',
    ],

    'yourreps' => [
        'menu' => [
            'text' => 'Your representative',
            'title' => '',
        ],
        'title' => 'Your representative',
        'url' => 'your/',
    ],
    'yourmp' =>  [
        'menu'			=>  [
            'text'			=> gettext('Your MP'),
            'title'			=> gettext("Find out about your Member of Parliament"),
        ],
        'sidebar'		=> 'yourmp',
        'title'			=> gettext('Your MP'),
        'url'			=> 'mp/',
        'parent'			=> 'mps',
    ],
    'yourmp_recent' =>  [
        'menu'			=>  [
            'text'			=> 'Recent appearances',
            'title'			=> "Recent speeches and written answers by this MP",
        ],
        'parent'		=> 'yourmp',
        'title'			=> "Your MP's recent appearances in parliament",
        'url'			=> 'mp/?recent=1',
    ],
    'yourmsp' =>  [
        'menu'			=>  [
            'text'			=> 'Your MSPs',
            'title'			=> "Find out about your Members of the Scottish Parliament",
        ],
        #'parent'		=> 'yourreps',
        'sidebar'		=> 'yourmsp',
        'title'			=> 'Your MSPs',
        'url'			=> 'msp/',
    ],
    'yourmla' =>  [
        'menu'			=>  [
            'text'			=> 'Your MLAs',
            'title'			=> "Find out about your Members of the Legislative Assembly",
        ],
        #'parent'		=> 'yourreps',
        'sidebar'		=> 'yourmla',
        'title'			=> 'Your MLAs',
        'url'			=> 'mla/',
    ],
];

// We just use the sections for creating page headings/titles.
// The 'title' is always used for the <title> tag of the page.
// The text displayed on the page itself will also be this,
// UNLESS the section has a 'heading', in which case that's used instead.

$section =  [


    'about' =>  [
        'title' 	=> 'About Us',
    ],
    'admin' =>  [
        'title'		=> 'Admin',
    ],
    'debates' =>  [
        'title' 	=> 'Debates',
        'heading'	=> 'House of Commons Debates',
    ],
    'help_us_out' =>  [
        'title' 	=> 'Help Us Out',
    ],
    'hansard' =>  [
        'title' 	=> 'Hansard',
    ],
    'home' =>  [
        'title' 	=> 'Home',
    ],
    'mp' =>  [
        'title' 	=> 'Your MP',
    ],
    'search' =>  [
        'title' 	=> 'Search',
    ],
    'sitenews' =>  [
        'title' 	=> 'TheyWorkForYou news',
    ],
    'wrans' =>  [
        'title' 	=> 'Written Answers',
    ],

];
