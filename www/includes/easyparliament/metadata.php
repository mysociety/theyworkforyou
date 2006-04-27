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
		'sitetitle'		=> 'TheyWorkForYou.com',
		//deprecated   'track'		=> false
	),
	
	
	
// Every page on the site should have an entry below...	

// KEEP THE PAGES IN ALPHABETICAL ORDER! TA.
	
	'about' => array (
		'title'			=> 'About us',
		'url'			=> 'about/'
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
	
// Added by Richard Allan for email alert functions

	'alert' => array (
		'menu'			=> array (
			'text'			=> 'Email Alerts',
			'title'			=> "Set up alerts for updates on an MP or Peer by email",
			'sidebar'		=> 'alert'

		),
		'pg'			=> 'alert',
		'title'			=> 'TheyWorkForYou.com Email Alerts',
		'url'			=> 'alert/',
	),		
	'alertconfirm' => array (
		'track'			=> true,
		'url'			=> 'alert/confirm/'
	),
	'alertconfirmfailed' => array (
		'title'			=> 'Oops!',
		'track'			=> true,
		'url'			=> 'alert/confirm/'
	),
	'alertconfirmsucceeded' => array (
		'title'			=> 'Alert Confirmed!',
		'track'			=> true,
		'url'			=> 'alert/confirm/'
	),
	'alertdelete' => array (
		'track'			=> true,
		'url'			=> 'alert/delete/'
	),
	'alertdeletefailed' => array (
		'title'			=> 'Oops!',
		'track'			=> true,
		'url'			=> 'alert/delete/'
	),
	'alertdeletesucceeded' => array (
		'title'			=> 'Alert Deleted!',
		'track'			=> true,
		'url'			=> 'alert/delete/'
	),
	'alertundeletesucceeded' => array (
		'title'			=> 'Alert Undeleted!',
		'track'			=> true,
		'url'			=> 'alert/undelete/'
	),
	'alertundeletefailed' => array (
		'title'			=> 'Oops!',
		'track'			=> true,
		'url'			=> 'alert/undelete/'
	),
	'alertwelcome' => array (
		'title'			=> 'Email Alerts',
		'url'			=> 'alert/',
		'pg'			=> 'alertwelcome'
	),

// End of ALERTS additions
		
	'cards' => array (
		'title'			=> 'MP Stats Cards',
		'url'			=> 'cards/'
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
		'title'			=> 'Contact us',
		'url'			=> 'contact/'
	),
	
	'debate'  => array (	
		'parent'		=> 'debatesfront',
		'track'			=> true,
		'url'			=> 'debate/',
		'session_vars'	=> array ('id'),
	),
	'debates'  => array (
		'parent'		=> 'debatesfront',
		'track'			=> true,
		'url'			=> 'debates/',
		'session_vars'	=> array ('id'),
	),
	'debatesday' => array (
		'parent'		=> 'debatesfront',
		'session_vars'	=> array ('d'),
		'track'			=> true,
		'url'			=> 'debates/',
	),			
	'debatesfront' => array (
		'menu'			=> array (
			'text'			=> 'Commons Debates',
			'title'			=> "House of Commons debates"
		),
		'parent'		=> 'hansard',
		'title'			=> 'House of Commons debates',
		'track'			=> true,
		'rss'			=> 'debates/debates.rss',
		'url'			=> 'debates/'
	),
	'debatesyear' => array (
		'parent'		=> 'debatesfront',
		'title'			=> 'Debates for ',
		'url'			=> 'debates/'
	),
	'disclaimer' => array (
		'title'			=> 'Terms of use',
		'url'			=> 'termsofuse/'
	),
	'epvote' => array (
		'url'			=> 'vote/'
	),
	'glossary' => array (
		'heading'		=> 'Glossary',
		'parent'		=> 'help_us_out',
		'track'			=> true,
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
		'track'			=> true,
		'url'			=> 'glossary/',
		'session_vars'	=> array ('g')
	),
	'hansard' => array (
		'menu'			=> array (
			'text'			=> 'Hansard',
			'title'			=> "House of Commons debates and Written Answers"
		),
		'title'			=> 'House of Commons and House of Lords',
		'track'			=> true,
		'url'			=> 'hansard/'
	),	
	'hansard_date' => array (
		'parent'		=> 'hansard',
		'title'			=> 'House of Commons and House of Lords',
		'track'			=> true,
		'url'			=> 'hansard/'
	),	
	'help' => array (
		'menu'			=> array (
			'text'			=> 'Help',
			'title'			=> "Answers to your questions"
		),
		'title'			=> 'Help',
		'track'			=> true,
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
		'menu'			=> array (
			'text'			=> 'Home',
			'title'			=> "The front page of the site"
		),
		'title'			=> "Are your MPs and Peers working for you in the UK's Parliament?",
		'track'			=> true,
		'rss'			=> 'news/index.rdf',
		'url'			=> ''
	),
	'houserules' => array (
		'title'			=> 'House Rules',
		'url'			=> 'houserules/'
	),

	'linktous' => array (
		'title'			=> 'Link to us',
		'heading'		=> 'How to link to us',
		'url'			=> 'help/linktous/'
	),

	'lordsdebate'  => array (
		'parent'		=> 'lordsdebatesfront',
		'track'			=> true,
		'url'			=> 'lords/',
		'session_vars'	=> array ('id'),
	),
	'lordsdebates'  => array (
		'parent'		=> 'lordsdebatesfront',
		'track'			=> true,
		'url'			=> 'lords/',
		'session_vars'	=> array ('id'),
	),
	'lordsdebatesday' => array (
		'parent'		=> 'lordsdebatesfront',
		'session_vars'	=> array ('d'),
		'track'			=> true,
		'url'			=> 'lords/',
	),			
	'lordsdebatesfront' => array (
		'menu'			=> array (
			'text'			=> 'Lords Debates',
			'title'			=> "House of Lords debates"
		),
		'parent'		=> 'hansard',
		'title'			=> 'House of Lords debates',
		'track'			=> true,
		'rss'			=> 'lords/lords.rss',
		'url'			=> 'lords/'
	),
	'lordsdebatesyear' => array (
		'parent'		=> 'lordsdebatesfront',
		'title'			=> 'Debates for ',
		'url'			=> 'lords/'
	),

	'peer' => array (
		'title'			=> 'Peer',
		'track'			=> true,
		'url'			=> 'peer/'
	),
	'peers' => array (
		 'menu'			=> array (
			'text'			=> 'All Lords',
			'title'			=> "List of all Lords"
		),
		'title'			=> 'All Lords',
		'track'			=> true,
		'url'			=> 'peers/'
	),

	/* Not 'Your MP', whose name is 'yourmp'... */
	'mp' => array (
		'title'			=> 'MP',
		'track'			=> true,
		'url'			=> 'mp/'
	),
	'emailfriend' => array (
		'title'			=> 'Send this page to a friend',
		'track'			=> true,
		'url'			=> 'email/'
	),
	'c4_mp' => array (
		'title'			=> 'MP',
		'track'			=> true,
		'url'			=> 'mp/c4/'
	),
	'c4x_mp' => array (
		'title'			=> 'MP',
		'track'			=> true,
		'url'			=> 'mp/c4x/'
	),
	// The directory MPs' RSS feeds are stored in.
	'mp_rss' => array (
		'url'			=> 'rss/mp/'
	),

	'mps' => array (
		 'menu'			=> array (
			'text'			=> 'All MPs',
			'title'			=> "List of all MPs"
		),
		'title'			=> 'All MPs',
		'track'			=> true,
		'url'			=> 'mps/'
	),
	'c4_mps' => array (
		'title' => 'All MPs',
		'track' => true,
		'url' => 'mps/c4/'
	),
	'c4x_mps' => array (
		'title' => 'All MPs',
		'track' => true,
		'url' => 'mps/c4x/'
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
	
	'search'		=> array (
		'sidebar'		=> 'search',
		'track'			=> true,
		'url'			=> 'search/',
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
		'track'			=> true,
		'url'			=> 'news/'
	),
	'sitenews_archive'		=> array (
		'parent'		=> 'sitenews',
		'rss'			=> 'news/index.rdf',
		'sidebar'		=> 'sitenews',
		'title'			=> 'Archive',
		'track'			=> true,
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
// deprecated 		'track'			=> true
	),
	'sitenews_rss1' 	=> array (
		'url'			=> 'news/index.rdf'
	),
	'sitenews_rss2' 	=> array (
		'url'			=> 'news/index.xml'
	),
	
	'skin'		=> array (
		'title'			=> 'Skin this site',
		'url'			=> 'skin/'
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
	//deprecated 	'track'			=> true,
		'url'			=> 'user/confirm/'
	),
	'userconfirmed' => array (
		'sidebar'		=> 'userconfirmed',
		'title'			=> 'Welcome to TheyWorkForYou.com!',
	//deprecated 	'track'			=> true,
		'url'			=> 'user/confirm/'
	),
	'userconfirmfailed' => array (
		'title'			=> 'Oops!',
	//deprecated 	'track'			=> true,
		'url'			=> 'user/confirm/'
	),
	'useredit' => array (
		'pg'			=> 'edit',
		'title'			=> 'Edit your details',
		'url'			=> 'user/'
	),'userjoin' => array (
                'menu'                  => array (
                        'text'                  => 'Join',
                        'title'                 => "Joining is free and allows you to post comments"
                ),
                'pg'                    => 'join',
                'sidebar'               => 'userjoin',
                'title'                 => 'Join TheyWorkForYou.com',
        //deprecated    'track'                 => true,
                'url'                   => 'user/'
        ),  	
	'getinvolved' => array (
		'menu'			=> array (
			'text'			=> 'Get involved',
			'title'			=> "Contribute to TheyWorkForYou.com"
		),
		'pg'			=> 'getinvolved',
		'sidebar'		=> 'userjoin',
		'title'			=> 'Contribute to TheyWorkForYou.com',
	//deprecated 	'track'			=> true,
		'url'			=> 'getinvolved/'
	),		
	'userlogin' => array (
		'menu'			=> array (
			'text'			=> 'Log in',
			'title'			=> "If you've already joined, log in to post comments"
		),
		'sidebar'		=> 'userlogin',
		'title'			=> 'Log in',
	//deprecated 	'track'			=> true,
		'url'			=> 'user/login/'
	),
	
	'userlogout' => array (
		'menu'			=> array (
			'text'			=> 'Log out',
			'title'			=> "Log out"
		),
		'url'			=> 'user/logout/'
	),		
	'userpassword' => array (
		'title'			=> 'Change password',
		'url'			=> 'user/password/'
	),
	'userprompt' => array (
		'title'			=> 'Please log in',
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
		'parent'		=> 'hansard',
		'title'			=> 'Westminster Hall debates',
		'rss'			=> 'whall/whall.rss',
		'url'			=> 'whall/'
	),
	'whallyear' => array (
		'parent'		=> 'whallfront',
		'title'			=> 'Westminster Hall debates for ',
		'url'			=> 'whall/'
	),

	'wms' => array (
		'parent'		=> 'wmsfront',
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
			'title'			=> 'Written Ministerial Statements'
		),
		'parent'		=> 'hansard',
		'title'			=> 'Written Ministerial Statements',
		'rss'			=> 'wms/wms.rss',
		'url'			=> 'wms/'
	),
	'wmsyear' => array (
		'parent'		=> 'wmsfront',
		'title'			=> 'Written Ministerial Statements for ',
		'url'			=> 'wms/'
	),

	'wrans'  => array (
		'parent'		=> 'wransfront',
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
		'parent'		=> 'hansard',
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
		'title'			=> 'Written answers for ',
		'url'			=> 'wrans/'
	),

	'yourmp' => array (
		'menu'			=> array (
			'text'			=> 'Your MP',
			'title'			=> "Find out about your Member of Parliament"
		),
		'sidebar'		=> 'yourmp',
		'title'			=> 'Your MP',
		'url'			=> 'mp/'
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
