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
// 		$URL = new URL('search');
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

$this->page = array (

// Things used on EVERY page, unless overridden for a page:
	'default' => array (
		'parent'	=> '',
		'session_vars' => array('super_debug'),
		'sitetitle'		=> 'TheyWorkForYou',
	),
	
	
	
// Every page on the site should have an entry below...	

// KEEP THE PAGES IN ALPHABETICAL ORDER! TA.
	
	'about' => array (
		'title'			=> 'About us',
		'url'			=> 'about/'
	),
    'parliaments' => array (
    	'title' 	=> 'Parliaments and assemblies',
    	'url'       => 'parliaments/'
    ),
	'addcomment'  => array (
		'url'			=> 'addcomment/',
	),

	'admin_alerts' => array (
		'title'			=> 'Email alerts',
		'parent'		=> 'admin',
		'url'			=> 'admin/alerts.php',
	),
	'alert_stats' => array (
		'title'			=> 'Email alerts',
		'parent'		=> 'admin',
		'url'			=> 'admin/alert_stats.php',
	),
	'admin_badusers' => array (
		'title'			=> 'Bad users',
		'parent'		=> 'admin',
		'url'			=> 'admin/badusers.php'
	),
	'admin_home' => array (
		'title'			=> 'Home',
		'parent'		=> 'admin',
		'url'			=> 'admin/'
	),
	'admin_comments' => array (
		'title'			=> 'Recent comments',
		'parent'		=> 'admin',
		'url'			=> 'admin/comments.php'
	),
	'admin_commentreport' => array (
		'title'			=> 'Processing a comment report',
		'parent'		=> 'admin',
		'url'			=> 'admin/report.php',
		'session_vars'	=> array ('rid', 'cid')
	),
	'admin_commentreports' => array (
		'title'			=> 'Outstanding comment reports',
		'parent'		=> 'admin',
		'url'			=> 'admin/reports.php'
	),
	'admin_failedsearches' => array (
		'title'			=> 'Failed searches',
		'parent'		=> 'admin',
		'url'			=> 'admin/failedsearches.php'
	),
	'admin_glossary' => array (
		'title'			=> 'Manage glossary entries',
		'parent'		=> 'admin',
		'url'			=> 'admin/glossary.php'
	),
	'admin_glossary_pending' => array (
		'title'			=> 'Review pending glossary entries',
		'parent'		=> 'admin',
		'url'			=> 'admin/glossary_pending.php'
	),
	'admin_searchlogs' => array (
		'title'			=> 'Recent searches',
		'parent'		=> 'admin',
		'url'			=> 'admin/searchlogs.php'
	),
	'admin_popularsearches' => array (
		'title'			=> 'Popular searches in last 30 days (first 1000)',
		'parent'		=> 'admin',
		'url'			=> 'admin/popularsearches.php'
	),
	'admin_statistics' => array (
		'title'			=> 'General statistics',
		'parent'		=> 'admin',
		'url'			=> 'admin/statistics.php'
	),
	'admin_trackbacks' => array (
		'title'			=> 'Recent trackbacks',
		'parent'		=> 'admin',
		'url'			=> 'admin/trackbacks.php'
	),
	'admin_photos' => array (
		'title'			=> 'Photo upload/attribution',
		'parent'		=> 'admin',
		'url'			=> 'admin/photos.php',
	),
	'admin_mpurls' => array (
		'title'			=> 'MP Websites',
		'parent'		=> 'admin',
		'url'			=> 'admin/websites.php',
	),
	
// Added by Richard Allan for email alert functions

	'alert' => array (
		'menu'			=> array (
			'text'			=> 'Email Alerts',
			'title'			=> "Set up alerts for updates on an MP or Peer by email",
			'sidebar'		=> 'alert'

		),
		'title'			=> 'TheyWorkForYou Email Alerts',
		'url'			=> 'alert/',
	),		
	'alertwelcome' => array (
		'title'			=> 'Email Alerts',
		'url'			=> 'alert/',
	),

// End of ALERTS additions
		
	'api_front'		=> array (
		'menu'			=> array (
			'text'			=> 'API',
			'title'			=> 'Access our data'
		),
		'title'			=> 'TheyWorkForYou API',
		'url'			=> 'api/'
	),
	'api_doc_front'		=> array (
		'menu'			=> array (
			'text'			=> 'API',
			'title'			=> 'Access our data'
		),
		'parent'		=> 'api_front',
		'url'			=> 'api/'
	),
	'api_key'		=> array (
		'title'			=> 'API Keys',
		'parent'		=> 'api_front',
		'url'			=> 'api/key'
	),

    'boundaries' => array(
        'title' => 'Constituency boundaries',
        'url' => 'boundaries/',
    ),

	'calendar_summary' => array (
		'menu'			=> array (
			'text'			=> 'Upcoming',
			'title'			=> '',
		),
		'parent'		=> 'hansard',
		'url'			=> 'calendar/'
	),
	'calendar_future_head' => array (
		'parent'		=> 'calendar_summary',
		'title'			=> 'Upcoming business',
		'url'			=> 'calendar/'
	),
	'calendar_future' => array (
		'parent'		=> 'calendar_future_head',
		'url'			=> 'calendar/'
	),
	'calendar_today_head' => array (
		'parent'		=> 'calendar_summary',
		'title'			=> 'Today&rsquo;s business',
		'url'			=> 'calendar/'
	),
	'calendar_today' => array (
		'parent'		=> 'calendar_today_head',
		'url'			=> 'calendar/'
	),
	'calendar_past_head' => array (
		'parent'		=> 'calendar_summary',
		'title'			=> 'Previous business',
		'url'			=> 'calendar/'
	),
	'calendar_past' => array (
		'parent'		=> 'calendar_past_head',
		'url'			=> 'calendar/'
	),

	'cards' => array (
		'title'			=> 'MP Stats Cards',
		'url'			=> 'cards/'
	),

	'campaign_foi' => array (
		'title'			=> 'Freedom of Information (Parliament) Order 2009',
		'url'			=> 'foiorder2009/'
	),
	'campaign' => array (
		'title'			=> '', #Free Our Bills!',
		'url'			=> 'freeourbills/'
	),
	'campaign_edm' => array (
		'title'			=> 'Early Day Motion',
		'parent'		=> 'campaign',
		'url'			=> 'freeourbills/'
	),

	'commentreport' => array (
		'title'			=> 'Reporting a comment',
		'url'			=> 'report/',
		'session_vars'	=> array ('id')
	),

	'comments_recent' => array (
		'menu'			=> array (
			'text'			=> 'Recent comments',
			'title'			=> "Recently posted comments"
		),
		'parent'		=> 'home',
		'title'			=> "Recent comments",
		'url'			=> 'comments/recent/'
	),

	'contact' => array (
		'title'			=> 'Contact',
		'url'			=> 'contact/'
	),
	'news' => array(
	    'title' => 'News',
	    'url' => 'http://www.mysociety.org/category/projects/theyworkforyou/'
	),
	'debate'  => array (	
		'parent'		=> 'debatesfront',
		'url'			=> 'debate/',
		'session_vars'	=> array ('id'),
	),
	'debates'  => array (
		'parent'		=> 'debatesfront',
		'url'			=> 'debates/',
		'session_vars'	=> array ('id'),
	),
	'debatesday' => array (
		'parent'		=> 'debatesfront',
		'session_vars'	=> array ('d'),
		'url'			=> 'debates/',
	),			
	'alldebatesfront' => array (
		'menu'			=> array (
			'text'			=> 'Debates',
			'title'			=> "Debates in the House of Commons, Westminster Hall, and the House of Lords"
		),
		'parent'		=> 'hansard',
		'title'			=> 'UK Parliament Hansard Debates',
		'rss'			=> 'debates/debates.rss',
		'url'			=> 'debates/'
	),
	'debatesfront' => array (
		'menu'			=> array (
			'text'			=> 'Commons debates',
			'title'			=> "Debates in the House of Commons"
		),
		'parent'		=> 'alldebatesfront',
		'title'			=> 'House of Commons debates',
		'rss'			=> 'debates/debates.rss',
		'url'			=> 'debates/'
	),
	'debatesyear' => array (
		'parent'		=> 'debatesfront',
		'url'			=> 'debates/'
	),
	'epvote' => array (
		'url'			=> 'vote/'
	),

	'gadget' => array(
		'url'			=> 'gadget/',
		'title'			=> 'TheyWorkForYou Google gadget',
	),

	'glossary' => array (
		'heading'		=> 'Glossary',
		'parent'		=> 'help_us_out',
		'url'			=> 'glossary/'
	),
	'glossary_addterm' => array (
		'menu'			=> array (
			'text'			=> 'Add a term',
			'title'			=> "Add a definition for a term to the glossary"
		),
		'parent'		=> 'help_us_out',
		'title'			=> 'Add a glossary item',
		'url'			=> 'addterm/',
		'session_vars'	=> array ('g')
	),
	'glossary_addlink' => array (
		'menu'			=> array (
			'text'			=> 'Add a link',
			'title'			=> "Add an external link"
		),
		'parent'		=> 'help_us_out',
		'title'			=> 'Add a link',
		'url'			=> 'addlink/',
		'session_vars'	=> array ('g')
	),
	'glossary_item' => array (
		'heading'		=> 'Glossary heading',
		'parent'		=> 'help_us_out',
		'url'			=> 'glossary/',
		'session_vars'	=> array ('g')
	),
	'hansard' => array (
		'menu'			=> array (
			'text'			=> 'UK Parliament',
			'title'			=> "Houses of Parliament debates, Written Answers, Statements, Westminster Hall debates, and Bill Committees"
		),
		'title'			=> '',
		'url'			=> ''
	),
        // Hansard landing page
        'hansard_landing' => array (
                'title'                 => 'Hansard',
                'url'                   => 'search-hansard/',
        ),
	'help' => array (
		'title'			=> 'Help - Frequently Asked Questions',
		'url'			=> 'help/'
	),
	'help_us_out' => array (
		'menu'			=> array (
			'text'			=> 'Glossary',
			'title'			=> "Parliament's jargon explained"
		),
		'title'			=> 'Glossary',
		'heading'		=> 'Add a glossary item',
		'url'			=> 'addterm/',
		'sidebar'		=> 'glossary_add'
	),
	'home' => array (
		'title'			=> "UK Parliament",
		'rss'			=> 'news/index.rdf',
		'url'			=> ''
	),
	'houserules' => array (
		'title'			=> 'House rules',
		'url'			=> 'houserules/'
	),

	'linktous' => array (
		'title'			=> 'Link to us',
		'heading'		=> 'How to link to us',
		'url'			=> 'help/linktous/'
	),
	'api' => array (
		'title'			=> 'API',
		'heading'		=> 'API - Query the TheyWorkForYou database',
		'url'			=> 'api/'
	),
    'data' => array (
    	'title'			=> 'Raw Data',
    	'heading'		=> 'Raw data (XML) - the data behind TheyWorkForYou and Public Whip',
    	'url'			=> 'http://parser.theyworkforyou.com'
    ),
    'pombola' => array (
    	'title'			=> 'Pombola',
    	'heading'		=> 'mySociety\'s platform for running a Parliamentary monitoring site like this',
    	'url'			=> 'http://www.mysociety.org/international/pombola/'
    ),
    'devmailinglist' => array (
    	'title'			=> 'Developer mailing list',
    	'heading'		=> 'Developer mailing list',
    	'url'			=> 'https://secure.mysociety.org/admin/lists/mailman/listinfo/developers-public'
    ),        
    'code' => array (
    	'title'			=> 'Source code',
    	'heading'		=> 'TheyWorkForYou Source code',
    	'url'			=> 'http://github.com/mysociety/theyworkforyou'
    ),    
    'irc' => array (
    	'title'			=> 'IRC chat channel',
    	'heading'		=> 'IRC chat channel',
    	'url'			=> 'http://www.irc.mysociety.org/'
    ),
    'newzealand' => array (
    	'title'			=> 'New Zealand',
    	'heading'		=> 'They Work For You - New Zealand',
    	'url'			=> 'http://www.theyworkforyou.co.nz/'
    ),    
    'australia' => array (
    	'title'			=> 'Australia',
    	'heading'		=> 'Open Australia',
    	'url'			=> 'http://www.openaustralia.org/'
    ),
    'ireland' => array (
    	'title'			=> 'Ireland',
    	'heading'		=> 'TheyWorkForYou for the Houses of the Oireachtas',
    	'url'			=> 'http://www.kildarestreet.com/'
    ),    
    'mzalendo' => array (
    	'title'			=> 'Mzalendo',
    	'heading'		=> 'Keeping an eye on the Kenyan Parliament',
    	'url'			=> 'http://info.mzalendo.com/'
    ),
	'lordsdebate'  => array (
		'parent'		=> 'lordsdebatesfront',
		'url'			=> 'lords/',
		'session_vars'	=> array ('gid'),
	),
	'lordsdebates'  => array (
		'parent'		=> 'lordsdebatesfront',
		'url'			=> 'lords/',
		'session_vars'	=> array ('id'),
	),
	'lordsdebatesday' => array (
		'parent'		=> 'lordsdebatesfront',
		'session_vars'	=> array ('d'),
		'url'			=> 'lords/',
	),			
	'lordsdebatesfront' => array (
		'menu'			=> array (
			'text'			=> 'Lords debates',
			'title'			=> "House of Lords debates"
		),
		'parent'		=> 'alldebatesfront',
		'title'			=> 'House of Lords debates',
		'rss'			=> 'lords/lords.rss',
		'url'			=> 'lords/'
	),
	'lordsdebatesyear' => array (
		'parent'		=> 'lordsdebatesfront',
		'url'			=> 'lords/'
	),

        // Parliament landing page
        'parliament_landing' => array (
                'title'                 => 'Parliament',
                'url'                   => 'parliament/',
        ),

	'peer' => array (
		'parent'		=> 'peers',
		'title'			=> 'Peer',
		'url'			=> 'peer/'
	),
	'peers' => array (
		 'menu'			=> array (
			'text'			=> 'Lords',
			'title'			=> "List of all Lords"
		),
		'parent'		=> 'hansard',
		'title'			=> '',
		'url'			=> 'peers/'
	),
'overview' => array (
	 'menu'			=> array (
		'text'			=> 'Overview',
		'title'			=> "Overview of the UK Parliament"
	),
	'parent'		=> 'hansard',
	'title'			=> "Hansard and Official Reports for the UK Parliament, Scottish Parliament, and Northern Ireland Assembly - done right",
	'rss'			=> 'news/index.rdf',
	'url'			=> ''
),
	'mla' => array (
		'parent'		=> 'mlas',
		'title'			=> 'MLA',
		'url'			=> 'mla/'
	),
	'mlas' => array (
		'parent'		=> 'ni_home',
		'menu'			=> array (
			'text'			=> 'MLAs',
			'title'			=> "List of all Members of the Northern Ireland Assembly (MLAs)"
		),
		'title'			=> '',
		'url'			=> 'mlas/'
	),
	'msps' => array (
		'parent'		=> 'sp_home',
		'menu'			=> array (
			'text'			=> 'MSPs',
			'title'			=> "List of Members of the Scottish Parliament (MSPs)"
		),
		'title'			=> '',
		'url'			=> 'msps/'
	),
    'msp' => array (
    	'parent'		=> 'msps',	    
    	'title'			=> 'MSP',
    	'url'			=> 'msp/'
    ),
	/* Not 'Your MP', whose name is 'yourmp'... */
	'mp' => array (
		'parent'			=> 'mps',
		'title'			=> 'MP',
		'url'			=> 'mp/'
	),
	'emailfriend' => array (
		'title'			=> 'Send this page to a friend',
		'url'			=> 'email/'
	),
	// The directory MPs' RSS feeds are stored in.
	'mp_rss' => array (
		'url'			=> 'rss/mp/'
	),
    'mps' => array (
    	 'menu'			=> array (
    		'text'			=> 'MPs',
    		'title'			=> "List of all Members of Parliament (MPs)"
    	),
    	'parent'		=> 'hansard',
    	'title'			=> '',
    	'url'			=> 'mps/'
    ),

	/* Northern Ireland Assembly */
	'ni_home' => array(
		'menu'			=> array (
			'text'			=> 'Northern Ireland Assembly',
			'title'			=> 'Full authority over <em>transferred matters</em>, which include agriculture, education, employment, the environment and health'
		),
		'title'			=> 'Northern Ireland Assembly',
		'url'			=> 'ni/'
	),
    'nioverview' => array (
    	'parent'		=> 'ni_home',
    	'menu'			=> array (
    		'text'			=> 'Debates',
    		'title'			=> "Overview of the Northern Ireland Assembly debates"
    	),
    	'title'			=> '',
    	'rss'			=> 'ni/ni.rss',
    	'url'			=> 'ni/'
    ),
	'nidebate'  => array (
		'parent'		=> 'nidebatesfront',
		'url'			=> 'ni/',
		'session_vars'	=> array ('gid'),
	),
	'nidebates'  => array (
		'parent'		=> 'nidebatesfront',
		'url'			=> 'ni/',
		'session_vars'	=> array ('id'),
	),
	'nidebatesday' => array (
		'parent'		=> 'nidebatesfront',
		'session_vars'	=> array ('d'),
		'url'			=> 'ni/',
	),			
	'nidebatesfront' => array (
		'menu'			=> array (
			'text'			=> 'Debates',
			'title'			=> "Northern Ireland Assembly debates"
		),
		'parent'		=> 'nioverview',
		'title'			=> 'Northern Ireland Assembly debates',
		'rss'			=> 'ni/ni.rss',
		'url'			=> 'ni/'
	),
	'nidebatesyear' => array (
		'parent'		=> 'nidebatesfront',
		'url'			=> 'ni/'
	),

	'otheruseredit' => array (
		'pg'			=> 'editother',
		'title'			=> "Editing a user's data",
		'url'			=> 'user/'
	),			
	'privacy' => array (
		'title'			=> 'Privacy Policy',
		'url'			=> 'privacy/'
	),

	/* Public bill committees */
	'pbc_front' => array (
		'menu'			=> array (
			'text'			=> 'Bill Committees',
			'title'			=> "Public Bill Committees (formerly Standing Committees) debates"
		),
		'parent'		=> 'hansard',
		'title'			=> 'Public Bill Committees',
		'rss'			=> 'pbc/pbc.rss',
		'url'			=> 'pbc/'
	),
	'pbc_session' => array(
		'title' => 'Session',
		'url' => 'pbc/',
		'parent' => 'pbc_front',
	),
	'pbc_bill' => array(
		'title' => '',
		'url' => 'pbc/',
		'parent' => 'pbc_front',
		'session_vars'	=> array ('bill'),
	),
	'pbc_clause' => array(
		'parent'		=> 'pbc_front',
		'url'			=> 'pbc/',
		'session_vars'	=> array ('id'),
	),
	'pbc_speech' => array(
		'parent'		=> 'pbc_front',
		'url'			=> 'pbc/',
		'session_vars'	=> array ('id'),
	),

	'people' => array(
		'menu' => array(
			'text' => 'People',
			'title' => '',
		),
		'title'			=> 'Representatives',
		'url'			=> '',
	),

	'raw' => array (
		'title'			=> 'Raw data',
		'url'			=> 'raw/'
	),

	'regmem' => array (
		'title'			=> 'Changes to the Register of Members\' Interests',
		'url'			=> 'regmem/'
	),
	
	'regmem_date' => array (
		'url'			=> 'regmem/',
		'parent'		=> 'regmem'
	),
	
	'regmem_mp' => array (
		'url'			=> 'regmem/',
		'parent'		=> 'regmem'
	),
	
	'regmem_diff' => array (
		'url'			=> 'regmem/',
		'parent'		=> 'regmem'
	),
	
	'royal' => array (
		'title'			=> 'Royal',
		'url'			=> 'royal/'
	),

	'search'		=> array (
		'sidebar'		=> 'search',
		'url'			=> 'search/',
		'robots'		=> 'noindex, nofollow',
		'heading'		=> '',
		'session_vars'	=> array ('s', 'pid', 'o', 'pop')
	),
	'search_help'		=> array (
		'sidebar'		=> 'search',
		'title'			=> 'Help with searching',
		'url'			=> 'search/'
	),
	
	'sitenews'		=> array (
		'menu'			=> array (
			'text'			=> 'TheyWorkForYou news',
			'title'			=> "News about changes to this website"
		),
		'parent'		=> 'home',
		'rss'			=> 'news/index.rdf',
		'sidebar'		=> 'sitenews',
		'title'			=> 'TheyWorkForYou news',
		'url'			=> 'news/'
	),
	'sitenews_archive'		=> array (
		'parent'		=> 'sitenews',
		'rss'			=> 'news/index.rdf',
		'sidebar'		=> 'sitenews',
		'title'			=> 'Archive',
		'url'			=> 'news/archives/'
	),
	'sitenews_atom' 	=> array (
		'url'			=> 'news/atom.xml'
	),
	'sitenews_date'	=> array (
		'parent'		=> 'sitenews',
		'rss'			=> 'news/index.rdf',
		'sidebar'		=> 'sitenews'
	),
	'sitenews_individual'	=> array (
		'parent'		=> 'sitenews',
		'rss'			=> 'news/index.rdf',
		'sidebar'		=> 'sitenews',
	),
	'sitenews_rss1' 	=> array (
		'url'			=> 'news/index.rdf'
	),
	'sitenews_rss2' 	=> array (
		'url'			=> 'news/index.xml'
	),
	
	/* Scottish Parliament */
	'sp_home' => array(
		'menu'			=> array (
			'text'			=> 'Scottish Parliament',
			'title'			=> 'Scottish education, health, agriculture, justice, prisons and other devolved areas. Some tax-varying powers'
		),
		'title'			=> 'Scottish Parliament',
		'url'			=> 'scotland/'
	),
    'spoverview' => array (
    	'parent'		=> 'sp_home',
    	'menu'			=> array (
    		'text'			=> 'Overview',
    		'title'			=> "Overview of the Scottish Parliament"
    	),
    	'title'			=> '',
    	'url'			=> 'scotland/'
    ),	
	'spdebate'  => array (
		'parent'		=> 'spdebatesfront',
		'url'			=> 'sp/',
		'session_vars'	=> array ('id'),
	),
	'spdebates'  => array (
		'parent'		=> 'spdebatesfront',
		'url'			=> 'sp/',
		'session_vars'	=> array ('id'),
	),
	'spdebatesday' => array (
		'parent'		=> 'spdebatesfront',
		'session_vars'	=> array ('d'),
		'url'			=> 'sp/',
	),			
	'spdebatesfront' => array (
		'menu'			=> array (
			'text'			=> 'Debates',
			'title'			=> ''
		),
		'parent'		=> 'sp_home',
		'title'			=> 'Scottish Parliament debates',
		'rss'			=> 'sp/sp.rss',
		'url'			=> 'sp/'
	),

	'spdebatesyear' => array (
		'parent'		=> 'spdebatesfront',
		'url'			=> 'sp/'
	),
	'spwrans'  => array (
		'parent'		=> 'spwransfront',
		'url'			=> 'spwrans/',
		#'session_vars'	=> array ('id'),
	),
	'spwransday'  => array (
		'parent'		=> 'spwransfront',
		'url'			=> 'spwrans/'
	),
	'spwransfront'  => array (
		'menu'			=> array (
			'text'			=> 'Written Answers',
			'title'			=> ''
		),
		'parent'		=> 'sp_home',
		'title'			=> 'Scottish Parliament Written answers',
		'url'			=> 'spwrans/'
	),
	'spwransmp' => array(
		'parent'		=> 'spwransfront',
		'title'			=> 'For questions asked by ',
		'url'			=> 'spwrans/'
	),
	'spwransyear' => array (
		'parent'		=> 'spwransfront',
		'url'			=> 'spwrans/'
	),

	// Topic pages

	'topic' => array (
		'url'           => 'topic/',
		'title'         => 'Topics'
	),

	'topiccrimestats' => array (
		'url'           => 'topic/crime-stats',
		'parent'        => 'topic',
		'title'         => 'Crime Statistics'
	),

	// The URL 3rd parties need to ping something here.
	'trackback' => array (
		'url'			=> 'trackback/'
	),

	'useralerts' => array(
		'menu'			=> array(
			'text'			=> 'Email Alerts',
			'title'			=> 'Check your email alerts'
		),
		'title'			=> 'Your Email Alerts',
		'url'			=> 'user/alerts/',
		'parent'		=> 'userviewself'
	),
	'userchangepc' => array (
		'title'			=> 'Change your postcode',
		'url'			=> 'user/changepc/'
	),
	'userconfirm' => array (
		'url'			=> 'user/confirm/'
	),
	'userconfirmed' => array (
		'sidebar'		=> 'userconfirmed',
		'title'			=> 'Welcome to TheyWorkForYou!',
		'url'			=> 'user/confirm/'
	),
	'userconfirmfailed' => array (
		'title'			=> 'Oops!',
		'url'			=> 'user/confirm/'
	),
	'useredit' => array (
		'pg'			=> 'edit',
		'title'			=> 'Edit your details',
		'url'			=> 'user/'
	),
    'userjoin' => array (
                'menu'                  => array (
                        'text'                  => 'Join',
                        'title'                 => "Joining is free and allows you to annotate speeches"
                ),
                'pg'                    => 'join',
                'sidebar'               => 'userjoin',
                'title'                 => 'Join TheyWorkForYou',
                'url'                   => 'user/'
        ),
	'getinvolved' => array (
		'menu'			=> array (
			'text'			=> 'Get involved',
			'title'			=> "Contribute to TheyWorkForYou"
		),
		'pg'			=> 'getinvolved',
		'sidebar'		=> 'userjoin',
		'title'			=> 'Contribute to TheyWorkForYou',
		'url'			=> 'getinvolved/'
	),
	'userlogin' => array (
		'menu'			=> array (
			'text'			=> 'Sign in',
			'title'			=> "If you've already joined , sign in to add annotations"
		),
		'sidebar'		=> 'userlogin',
		'title'			=> 'Sign in',
		'url'			=> 'user/login/'
	),

	'userlogout' => array (
		'menu'			=> array (
			'text'			=> 'Sign out',
			'title'			=> "Sign out"
		),
		'url'			=> 'user/logout/'
	),		
	'userpassword' => array (
		'title'			=> 'Change password',
		'url'			=> 'user/password/'
	),
	'userprompt' => array (
		'title'			=> 'Please sign in',
		'url'			=> 'user/prompt/'
	),
	'userview' => array (
		'session_vars'	=> array('u'),
		'url'			=> 'user/'
	),
	'userviewself' => array (
		'menu'			=> array (
			'text'			=> 'Your details',
			'title'			=> "View and edit your details"
		),
		'url'			=> 'user/'
	),
	'userwelcome' => array (
		'title'			=> 'Welcome!',
		'url'			=> 'user/'
	),

	'video_front' => array(
		'title'			=> 'Video speech matching'
	),
	'video_main' => array(
		'title'			=> 'Video speech matching'
	),

	/* Welsh Assembly */
	'wales_home' => array(
		'menu' => array(
			'text' => 'Welsh Assembly',
			'title' => 'Welsh economic development, transport, finance, local government, health, housing, the Welsh Language and other devolved areas',
		),
		'title' => 'Welsh Assembly',
		'url' => 'wales/',
	),
	'wales_overview' => array(
		'title' => 'Welsh Assembly',
		'url' => 'wales/',
		'parent' => 'wales_home',
	),

	/* Westminster Hall */
	'whall'  => array (	
		'parent'		=> 'whallfront',
		'url'			=> 'whall/',
		'session_vars'	=> array ('id'),
	),
	'whalls'  => array (	
		'parent'		=> 'whallfront',
		'url'			=> 'whall/',
		'session_vars'	=> array ('id'),
	),
	'whallday' => array (
		'parent'		=> 'whallfront',
		'session_vars'	=> array ('d'),
		'url'			=> 'whall/',
	),			
	'whallfront' => array (
		'menu'			=> array (
			'text'			=> 'Westminster Hall',
			'title'			=> "Westminster Hall debates"
		),
		'parent'		=> 'alldebatesfront',
		'title'			=> 'Westminster Hall debates',
		'rss'			=> 'whall/whall.rss',
		'url'			=> 'whall/'
	), 
	'whallyear' => array (
		'parent'		=> 'whallfront',
		'url'			=> 'whall/'
	),

	'wms' => array (
		'parent'		=> 'wranswmsfront',
		'url'			=> 'wms/',
		'session_vars'	=> array('id')
	),
	'wmsday' => array (
		'parent'		=> 'wmsfront',
		'session_vars'	=> array('d'),
		'url'			=> 'wms/'
	),
	'wmsfront' => array (
		'menu'			=> array (
			'text'			=> 'Written Ministerial Statements',
			'title'			=> ''
		),
		'parent'		=> 'wranswmsfront',
		'title'			=> 'Written Ministerial Statements',
		'rss'			=> 'wms/wms.rss',
		'url'			=> 'wms/'
	),
	'wmsyear' => array (
		'parent'		=> 'wmsfront',
		'url'			=> 'wms/'
	),

	'wrans'  => array (
		'parent'		=> 'wranswmsfront',
		'url'			=> 'wrans/',
		'session_vars'	=> array ('id')
	),
	'wransday'  => array (
		'parent'		=> 'wransfront',
		'url'			=> 'wrans/'
	),
	'wransfront'  => array (
		'menu'			=> array (
			'text'			=> 'Written Answers',
			'title'			=> "Written Answers"
		),
		'parent'		=> 'wranswmsfront',
		'title'			=> 'Written answers',
		'url'			=> 'wrans/'
	),
	'wransmp' => array(
		'parent'		=> 'wransfront',
		'title'			=> 'For questions asked by ',
		'url'			=> 'wrans/'
	),
	'wransyear' => array (
		'parent'		=> 'wransfront',
		'url'			=> 'wrans/'
	),

	'wranswmsfront'  => array (
		'menu'			=> array (
			'text'			=> 'Written Answers',
			'title'			=> 'Written Answers and Statements',
		),
		'parent'		=> 'hansard',
		'title'			=> 'Hansard Written Answers',
		'url'			=> 'written-answers-and-statements/'
	),

	'yourreps' => array(
		'menu' => array(
			'text' => 'Your representative',
			'title' => '',
		),
		'title' => 'Your representative',
		'url' => 'your/',
	),
	'yourmp' => array (
		'menu'			=> array (
			'text'			=> 'Your MP',
			'title'			=> "Find out about your Member of Parliament"
		),
		'sidebar'		=> 'yourmp',
		'title'			=> 'Your MP',
		'url'			=> 'mp/',
		'parent'			=> 'mps',
	),
	'yourmp_recent' => array (
		'menu'			=> array (
			'text'			=> 'Recent appearances',
			'title'			=> "Recent speeches and written answers by this MP"
		),
		'parent'		=> 'yourmp',
		'title'			=> "Your MP's recent appearances in parliament",
		'url'			=> 'mp/?recent=1'
	),
	'yourmsp' => array (
		'menu'			=> array (
			'text'			=> 'Your MSPs',
			'title'			=> "Find out about your Members of the Scottish Parliament"
		),
		#'parent'		=> 'yourreps',
		'sidebar'		=> 'yourmsp',
		'title'			=> 'Your MSPs',
		'url'			=> 'msp/'
	),
	'yourmla' => array (
		'menu'			=> array (
			'text'			=> 'Your MLAs',
			'title'			=> "Find out about your Members of the Legislative Assembly"
		),
		#'parent'		=> 'yourreps',
		'sidebar'		=> 'yourmla',
		'title'			=> 'Your MLAs',
		'url'			=> 'mla/'
	),
);



// We just use the sections for creating page headings/titles.
// The 'title' is always used for the <title> tag of the page.
// The text displayed on the page itself will also be this, 
// UNLESS the section has a 'heading', in which case that's used instead.

$this->section = array (


	'about' => array (
		'title' 	=> 'About Us'
	),
	'admin' => array (
		'title'		=> 'Admin'
	),
	'debates' => array (
		'title' 	=> 'Debates',
		'heading'	=> 'House of Commons Debates'
	),
	'help_us_out' => array (
		'title' 	=> 'Help Us Out'
	),
	'hansard' => array (
		'title' 	=> 'Hansard'
	),
	'home' => array (
		'title' 	=> 'Home'
	),
	'mp' => array (
		'title' 	=> 'Your MP'
	),
	'search' => array (
		'title' 	=> 'Search'
	),
	'sitenews' => array (
		'title' 	=> 'TheyWorkForYou news'
	),
	'wrans' => array (
		'title' 	=> 'Written Answers'
	)

);
?>
