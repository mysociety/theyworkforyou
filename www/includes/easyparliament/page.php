<?php

require_once INCLUDESPATH . '../../../phplib/tracking.php';

class PAGE {

	// So we can tell from other places whether we need to output the page_start or not.
	// Use the page_started() function to do this.
	var $page_start_done = false;
	
	var $heading_displayed = false;
	
	// We want to know where we are with the stripes, the main structural elements
	// of most pages, so that if we output an error message we can wrap it in HTML
	// that won't break the rest of the page.
	// Changed in $this->stripe_start().
	var $within_stripe_main = false;
	var $within_stripe_sidebar = false;
		
	
	function page_start () {
	  ob_start();
	  set_time_limit(0);
		global $DATA, $this_page, $THEUSER;
		
		if (!$this->page_started()) {
			// Just in case something's already started this page...
	
			$parent = $DATA->page_metadata($this_page, "parent");

			if ($parent == 'admin' && ! $THEUSER->is_able_to('viewadminsection')) {
				// If the user tries to access the admin section when they're not
				// allowed, then show them nothing.
			
				if (!$THEUSER->isloggedin()) {
					$THISPAGE = new URL($this_page);
					
					$LOGINURL = new URL('userlogin');
					$LOGINURL->insert(array('ret' => $THISPAGE->generate('none') ));
				
					$text = "<a href=\"" . $LOGINURL->generate() . "\">You'd better log in!</a>";
				} else {
					$text = "That's all folks!";
				}
				
				$this_page = 'home';
				
				$this->page_header();
				$this->page_body();
				$this->content_start();
				$this->stripe_start();
				
				print "<p>$text</p>\n";
				
				$this->stripe_end();
				$this->page_end();
				exit;
			}
			
			$this->page_header();
			$this->page_body();
			$this->content_start();
			
			$this->page_start_done = true;		
			
		}
	}
	
	
	function page_end ($extra = null) {
		$this->content_end();
		$this->page_footer($extra);
	}
	
	
	function page_started () {
		return $this->page_start_done == true ? true : false;
	}

	function heading_displayed () {
		return $this->heading_displayed == true ? true : false;
	}
	
	function within_stripe () {
		if ($this->within_stripe_main == true || $this->within_stripe_sidebar == true) {
			return true;
		} else {
			return false;
		}
	}

	function within_stripe_sidebar () {
		if ($this->within_stripe_sidebar == true) {
			return true;
		} else {
			return false;
		}
	}


	function page_header () {
		global $DATA, $this_page;
		
		$linkshtml = "";
	
		$title = '';
		$sitetitle = $DATA->page_metadata($this_page, "sitetitle");
		$keywords_title = '';
		
		if ($this_page == 'home') {
			$title = $sitetitle . ': ' . $DATA->page_metadata($this_page, "title");
		
		} else {

			if ($page_subtitle = $DATA->page_metadata($this_page, "subtitle")) {
				$title = "$page_subtitle";
			} elseif ($page_title = $DATA->page_metadata($this_page, "title")) {
				$title = "$page_title";
			}
			// We'll put this in the meta keywords tag.
			$keywords_title = $title;

			$parent_page = $DATA->page_metadata($this_page, 'parent');
			if ($parent_title = $DATA->page_metadata($parent_page, 'title')) {
				$title .= ": $parent_title";
			}

			if ($title == '') {
				$title = $sitetitle;
			} else {			
				$title .= ' (' . $sitetitle . ')';
			}
		}

		if (!$metakeywords = $DATA->page_metadata($this_page, "metakeywords")) {
			$metakeywords = "";
		}
		if (!$metadescription = $DATA->page_metadata($this_page, "metadescription")) {
			$metadescription = "";
		}

	
		if ($this_page != "home") {
			$URL = new URL('home');
			
			$linkshtml = "\t<link rel=\"start\" title=\"Home\" href=\"" . $URL->generate() . "\" />\n";
		}


		// Create the next/prev/up links for navigation.
		// Their data is put in the metadata in hansardlist.php
		$nextprev = $DATA->page_metadata($this_page, "nextprev");

		if ($nextprev) {
			// Four different kinds of back/forth links we might build.
			$links = array ("first", "prev", "up", "next", "last");

			foreach ($links as $n => $type) {
				if (isset($nextprev[$type]) && isset($nextprev[$type]['listurl'])) {
				
					if (isset($nextprev[$type]['body'])) {
						$linktitle = htmlentities( trim_characters($nextprev[$type]['body'], 0, 40) );
						if (isset($nextprev[$type]['speaker']) &&
							count($nextprev[$type]['speaker']) > 0) {
							$linktitle = $nextprev[$type]['speaker']['first_name'] . ' ' . $nextprev[$type]['speaker']['last_name'] . ': ' . $linktitle;	
						}

					} elseif (isset($nextprev[$type]['hdate'])) {
						$linktitle = format_date($nextprev[$type]['hdate'], SHORTDATEFORMAT);
					}

					$linkshtml .= "\t<link rel=\"$type\" title=\"$linktitle\" href=\"" . $nextprev[$type]['listurl'] . "\" />\n";
				}
			}
		}
		
		// Needs to come before any HTML is output, in case it needs to set a cookie.
		$SKIN = new SKIN();
			
		if (!$keywords = $DATA->page_metadata($this_page, "keywords")) {
			$keywords = "";	
		} else {
			$keywords = ",".$DATA->page_metadata($this_page, "keywords");
		}

		?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
	<title><?php echo $title; ?></title>
	<meta name="description" content="Making parliament easy." />
	<meta name="keywords" content="Parliament, government, house of commons, house of lords, MP, Peer, Member of Parliament, MPs, Peers, Lords, Commons, UK, Britain, British, Welsh, Scottish, Wales, Scotland, <?php echo htmlentities($keywords_title).htmlentities($keywords); ?>" />
	<link rel="author" title="Send feedback" href="mailto:<?php echo str_replace('@', '&#64;', CONTACTEMAIL); ?>" />
	<link rel="home" title="Home" href="http://<?php echo DOMAIN; ?>/" />
<?php
		echo $linkshtml; 
		
		$SKIN->output_stylesheets();

		if ($rssurl = $DATA->page_metadata($this_page, 'rss')) {
			// If this page has an RSS feed set.
			?>
	<link rel="alternate" type="application/rss+xml" title="TheyWorkForYou RSS" href="http://<?php echo DOMAIN . WEBPATH . $rssurl; ?>" />
<?php
		}
				

		if ($this_page == 'sitenews_individual') {
			$this->_mt_javascript();
		}
		?>

<script src="http://www.google-analytics.com/urchin.js"
type="text/javascript">
</script>
<script type="text/javascript">
_uacct = "UA-116619-1";
urchinTracker();
</script>

</head>

<?php
	}	
	
	
	
	function page_body () {
		global $this_page;
		
		// Start the body, put in the page headings.
		?>
<body>
<div id="container">
<?php
		twfy_debug ("PAGE", "This page: $this_page");
		
		print "\t<a name=\"top\"></a>\n\n";

		$this->title_bar();
		
		$this->menu();
		
	}
	
	
	
	function title_bar () {
		// The title bit of the page, with possible search box.
		global $this_page;
		
		//$img = '<img src="' . IMAGEPATH . 'theyworkforyoucom.gif" width="293" height="28" alt="TheyWorkForYou.com" />';

		$img = '<img src="' . IMAGEPATH . 'theyworkforyoucombeta.gif" width="320" height="28" alt="TheyWorkForYou.com beta" />';
		
		//isn't this very hacky? shouldn't we be cobranding cleverly using METADATA? ( I've repeated this below however -stef"
		if (get_http_var('c4')) {
			$img = '<img src="/images/c4banner.gif" alt="TheyWorkForYou.com with Channel 4" />';
		} elseif (get_http_var('c4x')) {
			$img = '<img src="/images/c4Xbanner.gif" alt="TheyWorkForYou.com with Channel 4" />';
		}

		if ($this_page != 'home') {
			if (get_http_var('c4')) {
				$HOMEURL = 'http://www.channel4.com/news/microsites/E/election2005/';
				$HOMETITLE = 'To Channel 4\'s main election site';
			} elseif (get_http_var('c4x')) {
				$HOMEURL = 'http://www.channel4.com/life/microsites/E/elexion/';
				$HOMETITLE = 'To Channel 4\'s main election site';
			} else {
				$HOMEURL = new URL('home');
				$HOMEURL = $HOMEURL->generate();
				$HOMETITLE = 'To the front page of the site';
			}
			$img = '<a href="' . $HOMEURL . '" title="' . $HOMETITLE . '">' . $img . '</a>';
		}
		?>
	<div id="banner">
		<div id="title">
			<h1><?php echo $img; ?></h1>
		</div>
<?php
	#		if ($this_page != 'home' && $this_page != 'search' && $this_page != 'yourmp') {
			$URL = new URL('search');
			$URL->reset();
			?>
		<div id="search">
			<form action="<?php echo $URL->generate(); ?>" method="get">
			<p>Search <input name="s" size="15" maxlength="100" /> <input type="submit" class="submit" value="GO" /></p>
			</form>
		</div>
<?php
	#		}
		?>
	</div> <!-- end #banner -->
<?php	
	}
	
	
	
	function menu () {
		global $this_page, $DATA, $THEUSER;
	
		// Page names mapping to those in metadata.php.
		// Links in the top menu, and the sublinks we see if
		// we're within that section.
		$items = array (
			'home' 		=> array ('sitenews', 'comments_recent', 'api_front'),
			'hansard' 	=> array ('debatesfront', 'wransfront', 'whallfront', 'wmsfront', 'lordsdebatesfront'),
			'yourmp'	=> array (),
			'mps'           => array (),
			'peers'		=> array (),
			'help_us_out'	=> array (), 
/*			'help_us_out'	=> array ('glossary_addterm'),  */
			'help'		=> array ()
		);
		
		// If the user's postcode is set, then we allow them to view the
		// bottom menu link to this page...
		if ($THEUSER->postcode_is_set()) {
			$items['yourmp'] = array ('yourmp_recent');
		}
		
	
		$top_links = array();
		$bottom_links = array();
		
		// We work out which of the items in the top and bottom menus
		// are highlighted - $top_hilite and $bottom_hilite respectively.
		
		$this_parent = $DATA->page_metadata($this_page, 'parent');
		
		if ($this_parent == '') {
			// This page is probably one of the ones in the top men.
			// So hilite it and no bottom menu hilites.
			$top_hilite = $this_page;
			$bottom_hilite = '';
		
		} else {
			// Does this page's parent have a parent?
			$parents_parent = $DATA->page_metadata($this_parent, 'parent');

			if ($parents_parent == '') {
				// No grandparent - this page's parent is in the top menu.
				// We're on one of the pages linked to by the bottom menu.
				// So hilite it and its parent.
				$top_hilite = $this_parent;
				$bottom_hilite = $this_page;
			} else {
				// This page is not in either menu. So hilite its parent
				// (in the bottom menu) and its grandparent (in the top).
				$top_hilite = $parents_parent;
				$bottom_hilite = $this_parent;
			}
		}

		foreach ($items as $toppage => $bottompages) {
			
			// Generate the links for the top menu.
			
			// What gets displayed for this page.
			$menudata = $DATA->page_metadata($toppage, 'menu');
			$text = $menudata['text'];
			$title = $menudata['title'];
			
			// Where we're linking to.
			$URL = new URL($toppage);			
			
			$class = $toppage == $top_hilite ? ' class="on"' : '';

			$top_links[] = '<a href="' . $URL->generate() . '" title="' . $title . '"' . $class . '>' . $text . '</a>';

			if ($toppage == $top_hilite) {
				// This top menu link is highlighted, so generate its bottom menu.
				
				foreach ($bottompages as $bottompage) {
					$menudata = $DATA->page_metadata($bottompage, 'menu');
					$text = $menudata['text'];
					$title = $menudata['title'];
					// Where we're linking to.
					$URL = new URL($bottompage);	
					$class = $bottompage == $bottom_hilite ? ' class="on"' : '';
					$bottom_links[] = '<a href="' . $URL->generate() . '" title="' . $title . '"' . $class . '>' . $text . '</a>';
				}
			}

		}
		?>
	<div id="menu">
		<div id="topmenu">
<?php
			$user_bottom_links = $this->user_bar($top_hilite, $bottom_hilite);
			if ($user_bottom_links) $bottom_links = $user_bottom_links;
			?>
			<ul id="site">
			<li><?php print implode("</li>\n\t\t\t<li>", $top_links); ?></li>
			</ul>
			<br />
		</div>
		<div id="bottommenu">
			<ul>
			<li><?php print implode("</li>\n\t\t\t<li>", $bottom_links); ?></li>
			</ul>
		</div>
	</div> <!-- end #menu -->
	
<?php
	}


	function user_bar ($top_hilite='', $bottom_hilite='') {
		// Called from menu(), but separated out here for clarity.
		// Does just the bit of the menu related to login/join/etc.
		global $this_page, $DATA, $THEUSER;

		$bottom_links = array();

		// We may want to send the user back to this current page after they've
		// joined, logged out or logged in. So we put the URL in $returl.
		$URL = new URL($this_page);
		$returl = $URL->generate();
		
			// The 'get involved' link.
			$menudata 	= $DATA->page_metadata('getinvolved', 'menu');
			$getinvolvedtitle	= $menudata['title'];
			$getinvolvedtext 	= $menudata['text'];
			
			$GETINVURL 	= new URL('getinvolved');
			if ($this_page != 'getinvolved') {
				//if ($this_page != "userlogout" && 
				//	$this_page != "userpassword" && 
				//	$this_page != 'userjoin') {
					// We don't do this on the logout page, because then the user
					// will return straight to the logout page and be logged out
					// immediately!
					// And it's also silly if we're sent back to Change Password.
					// And the join page.
				//	$LOGINURL->insert(array("ret"=>$returl));
				//}
				$getinvolvedclass = '';
			} else {
				$getinvolvedclass = ' class="on"';
			}
		if ($THEUSER->isloggedin()) {
		
			// The 'Edit details' link.
			$menudata 	= $DATA->page_metadata('userviewself', 'menu');
			$edittext 	= $menudata['text'];
			$edittitle 	= $menudata['title'];
			$EDITURL 	= new URL('userviewself');
			if ($this_page == 'userviewself' || $this_page == 'useredit' || $top_hilite == 'userviewself') {
				$editclass = ' class="on"';
				$bottompages = array();
				foreach ($bottompages as $bottompage) {
					$menudata = $DATA->page_metadata($bottompage, 'menu');
					$text = $menudata['text'];
					$title = $menudata['title'];
					// Where we're linking to.
					$URL = new URL($bottompage);	
					$class = $bottompage == $bottom_hilite ? ' class="on"' : '';
					$bottom_links[] = '<a href="' . $URL->generate() . '" title="' . $title . '"' . $class . '>' . $text . '</a>';
				}
			} else {
				$editclass = '';
			}

			// The 'Log out' link.
			$menudata 	= $DATA->page_metadata('userlogout', 'menu');
			$logouttext	= $menudata['text'];
			$logouttitle= $menudata['title'];
			
			$LOGOUTURL	= new URL('userlogout');
			if ($this_page != 'userlogout') {
				$LOGOUTURL->insert(array("ret"=>$returl));
				$logoutclass = '';
			} else {
				$logoutclass = ' class="on"';
			}
			
			$username = $THEUSER->firstname() . ' ' . $THEUSER->lastname();

		?>
			<ul id="user">
			<li><a href="<?php echo $LOGOUTURL->generate(); ?>" title="<?php echo $logouttitle; ?>"<?php echo $logoutclass; ?>><?php echo $logouttext; ?></a></li>
			<li><a href="<?php echo $EDITURL->generate(); ?>" title="<?php echo $edittitle; ?>"<?php echo $editclass; ?>><?php echo $edittext; ?></a></li>
			<li><span class="name"><?php echo htmlentities($username); ?></span></li>
<!--			<li><a href="<?php echo $GETINVURL->generate(); ?>" title="<?php echo $getinvolvedtitle; ?>"<?php echo $getinvolvedclass; ?>><?php echo $getinvolvedtext; ?></a></li> -->
			</ul>
<?php

		} else {
			// User logged out.

			// The 'Join' link.
			$menudata 	= $DATA->page_metadata('userjoin', 'menu');
			$jointext 	= $menudata['text'];
			$jointitle 	= $menudata['title'];
			
			$JOINURL 	= new URL('userjoin');
			if ($this_page != 'userjoin') {
				if ($this_page != 'userlogout' && $this_page != 'userlogin') {
					// We don't do this on the logout page, because then the user
					// will return straight to the logout page and be logged out
					// immediately!
					$JOINURL->insert(array("ret"=>$returl));	
				}
				$joinclass = '';
			} else {
				$joinclass = ' class="on"';
			}

			// The 'Log in' link.
			$menudata 	= $DATA->page_metadata('userlogin', 'menu');
			$logintext 	= $menudata['text'];
			$logintitle	= $menudata['title'];
			
			$LOGINURL 	= new URL('userlogin');
			if ($this_page != 'userlogin') {
				if ($this_page != "userlogout" && 
					$this_page != "userpassword" && 
					$this_page != 'userjoin') {
					// We don't do this on the logout page, because then the user
					// will return straight to the logout page and be logged out
					// immediately!
					// And it's also silly if we're sent back to Change Password.
					// And the join page.
					$LOGINURL->insert(array("ret"=>$returl));
				}
				$loginclass = '';
			} else {
				$loginclass = ' class="on"';
			}
		
		?>
			<ul id="user">
			<li><a href="<?php echo $LOGINURL->generate(); ?>" title="<?php echo $logintitle; ?>"<?php echo $loginclass; ?>><?php echo $logintext; ?></a></li>
			<li><a href="<?php echo $JOINURL->generate(); ?>" title="<?php echo $jointitle; ?>"<?php echo $joinclass; ?>><?php echo $jointext; ?></a></li>
<!--			<li><a href="<?php echo $GETINVURL->generate(); ?>" title="<?php echo $getinvolvedtitle; ?>"<?php echo $getinvolvedclass; ?>><?php echo $getinvolvedtext; ?></a></li> -->
			</ul>
<?php
		}
		return $bottom_links;
	}


	function content_start () {
		global $DATA, $this_page;
		
		// Where the actual meat of the page begins, after the title and menu.
		?>
	<div id="content">

<?php			
	}


	function stripe_start ($type='side', $id='') {
		// $type is one of:
		// 	'side' - a white stripe with a coloured sidebar.
		//           (Has extra padding at the bottom, often used for whole pages.)
		//  'head-1' - used for the page title headings in hansard.
		//	'head-2' - used for section/subsection titles in hansard.
		// 	'1', '2' - For alternating stripes in listings.
		//	'time-1', 'time-2' - For displaying the times in hansard listings.
		// 	'procedural-1', 'procedural-2' - For the proecdures in hansard listings.
		//	'foot' - For the bottom stripe on hansard debates/wrans listings.
		// $id is the value of an id for this div (if blank, not used).
		?>
		<div class="stripe-<?php echo $type; ?>"<?php
		if ($id != '') {
			print ' id="' . $id . '"';
		}
		?>>
			<div class="main">
<?php
		$this->within_stripe_main = true;
		// On most, uncomplicated pages, the first stripe on a page will include
		// the page heading. So, if we haven't already printed a heading on this
		// page, we do it now...
		if (!$this->heading_displayed()) {
			$this->heading();
		}
	}


	function stripe_end ($contents = array(), $extra = '') {
		// $contents is an array containing 0 or more hashes.
		// Each hash has two values, 'type' and 'content'.
		// 'Type' could be one of these:
		//	'include' - will include a sidebar named after the value of 'content'.php.
		//	'nextprev' - $this->nextprevlinks() is called ('content' currently ignored).
		//	'html' - The value of the 'content' is simply displayed.
		//	'extrahtml' - The value of the 'content' is displayed after the sidebar has
		//					closed, but within this stripe.

		// If $contents is empty then '&nbsp;' will be output.
		
		/* eg, take this hypothetical array:
			$contents = array(
				array (
					'type'	=> 'include',
					'content'	=> 'mp'
				),
				array (
					'type'	=> 'html',
					'content'	=> "<p>This is your MP</p>\n"
				),
				array (
					'type'	=> 'nextprev'
				),
				array (
					'extrahtml' => '<a href="blah">Source</a>'
				)
			);
		
			The sidebar div would be opened.
			This would first include /includes/easyparliament/templates/sidebars/mp.php.
			Then display "<p>This is your MP</p>\n".
			Then call $this->nextprevlinks().
			The sidebar div would be closed.
			'<a href="blah">Source</a>' is displayed.
			The stripe div is closed.
			
			But in most cases we only have 0 or 1 hashes in $contents.
		
		*/
		
		// $extra is html that will go after the sidebar has closed, but within
		// this stripe.
		// eg, the 'Source' bit on Hansard pages.
		global $DATA, $this_page;

		$this->within_stripe_main = false;
		?>
			</div> <!-- end .main -->
			<div class="sidebar">
<?php
		$this->within_stripe_sidebar = true;
		$extrahtml = '';
		
		if (count($contents) == 0) {
			print "\t\t\t&nbsp;\n";
		} else {
			foreach ($contents as $hash) {
				if (isset($hash['type'])) {
					if ($hash['type'] == 'include') {
						$this->include_sidebar_template($hash['content']);
					
					} elseif ($hash['type'] == 'nextprev') {
						$this->nextprevlinks();
					
					} elseif ($hash['type'] == 'html') {
						print $hash['content'];
					
					} elseif ($hash['type'] == 'extrahtml') {
						$extrahtml .= $hash['content'];
					}
				}

			}
		}

		$this->within_stripe_sidebar = false;
		?>
			</div> <!-- end .sidebar -->
			<div class="break"></div>
<?php
		if ($extrahtml != '') {
			?>
			<div class="extra"><?php echo $extrahtml; ?></div>
<?php
			}
			?>
		</div> <!-- end .stripe-* -->
		
<?php
	}



	function include_sidebar_template ($sidebarname) {
		global $this_page, $DATA;
		
			$sidebarpath = INCLUDESPATH.'easyparliament/sidebars/'.$sidebarname.'.php';

			if (file_exists($sidebarpath)) {
				include $sidebarpath;
			}
	}
	
	
	function block_start($data=array()) {
		// Starts a 'block' div, used mostly on the home page,
		// on the MP page, and in the sidebars.
		// $data is a hash like this:
		//	'id'	=> 'help',	
		//	'title'	=> 'What are debates?'
		//	'url'	=> '/help/#debates' 	[if present, will be wrapped round 'title']
		//	'body'	=> false	[If not present, assumed true. If false, no 'blockbody' div]
		// Both items are optional (although it'll look odd without a title).
		
		$this->blockbody_open = false;
		
		if (isset($data['id']) && $data['id'] != '') {
			$id = ' id="' . $data['id'] . '"';
		} else {
			$id = '';
		}
		
		$title = isset($data['title']) ? $data['title'] : '';
		
		if (isset($data['url'])) {
			$title = '<a href="' . $data['url'] . '">' . $title . '</a>';
		}
		?>
				<div class="block"<?php echo $id; ?>>
					<h4><?php echo $title; ?></h4>
<?php
		if (!isset($data['body']) || $data['body'] == true) {
			?>
					<div class="blockbody">
<?php
			$this->blockbody_open = true;
			}
	}
	
	
	function block_end () {
		if ($this->blockbody_open) {
			?>
					</div>
<?php
			}
			?>
				</div> <!-- end .block -->	

<?php
	}
	

	function heading() {
		global $this_page, $DATA;

		// As well as a page's title, we may display that of its parent.
		// A page's parent can have a 'title' and a 'heading'.
		// The 'title' is always used to create the <title></title>.
		// If we have a 'heading' however, we'll use that here, on the page, instead.
		
		$parent_page = $DATA->page_metadata($this_page, 'parent');
	
		if ($parent_page != '') {
			// Not a top-level page, so it has a section heading.
			// This is the page title of the parent.
			$section_text = $DATA->page_metadata($parent_page, 'title');
			
		} else {
			// Top level page - no parent, hence no parental title.
			$section_text = '';
		}
		
		
		// A page can have a 'title' and a 'heading'.
		// The 'title' is always used to create the <title></title>.
		// If we have a 'heading' however, we'll use that here, on the page, instead.
		
		$page_text = $DATA->page_metadata($this_page, "heading");

		if ($page_text == '' && !is_bool($page_text)) {
			// If the metadata 'heading' is set, but empty, we display nothing.
		} elseif ($page_text == false) {
			// But if it just hasn't been set, we use the 'title'.
			$page_text = $DATA->page_metadata($this_page, "title");
		}
		
		if ($page_text == $section_text) {
			// We don't want to print both.
			$page_text = '&nbsp;';
		} elseif ($page_text && !$section_text) {
			// Bodge for if we have a page_text but no section_text.
			$section_text = $page_text;
			$page_text = '&nbsp;';
		}

		if ($this_page != 'home' && $this_page != 'yourmp' && $this_page != 'mp' && $this_page != 'peer' && $this_page != 'c4_mp' && $this_page != 'c4x_mp') {

			if ($section_text && $parent_page != 'help_us_out' && $parent_page != 'home') {
				print "\t\t\t\t<h2>$section_text</h2>\n";
			}
			
			if ($page_text) {
				print "\t\t\t\t<h3>$page_text</h3>\n";
			}

		}
	
		// So we don't print the heading twice by accident from $this->stripe_start().
		$this->heading_displayed = true;
	}



	

	function content_end () {
		global $DATA, $this_page;
		
		$pages = array ('about', 'contact', 'linktous', 'houserules', 'disclaimer');
		
		foreach ($pages as $page) {
			$URL = new URL($page);
			$title = $DATA->page_metadata($page, 'title');
			
			if ($page == $this_page) {
				$links[] = $title;
			} else {
				$links[] = '<a href="' . $URL->generate() . '">' . $title . '</a>';
			}
		}

		$user_agent = ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) ? strtolower( $_SERVER['HTTP_USER_AGENT'] ) : '';
		if (stristr($user_agent, 'Firefox/'))
			$links[] = '<a href="http://mycroft.mozdev.org/download.html?name=theyworkforyou">Add search to Firefox</a>';
		?>
	
		<div id="footer">
			<p><?php
		print implode(' &nbsp;&nbsp;&nbsp; ', $links);
		?></p>
			
		</div>

	</div> <!-- end #content -->
<?php

	}
	
	
	
	function page_footer ($extra = null) {
		global $DATA, $this_page;



		// This makes the tracker appear on all sections, but only actually on theyworkforyou.com
				//if ($DATA->page_metadata($this_page, 'track') ) {
		if (substr(DOMAIN, -18) == "theyworkforyou.com" && substr(DOMAIN, 0, 7)!= "staging")  {
					// We want to track this page.
			// Kind of fake URLs needed for the tracker.
			$url = urlencode('http://' . DOMAIN . '/' . $this_page);
			?>
<script type="text/javascript"><!--
an=navigator.appName;sr='http://x3.extreme-dm.com/';srw="na";srb="na";d=document;r=41;function pr(n) {
d.write("<div><img alt=\"\" src=\""+sr+"n\/?tag=fawkes&p=<?php echo $url; ?>&j=y&srw="+srw+"&srb="+srb+"&l="+escape(d.referrer)+"&rs="+r+"\" height=\"1\" width=\"1\"></div>");}//-->
</script><script type="text/javascript"><!--
s=screen;srw=s.width;an!="Netscape"?srb=s.colorDepth:srb=s.pixelDepth//-->
</script><script type="text/javascript"><!--
pr()//-->
</script><noscript><div><img alt="" src="http://x3.extreme-dm.com/z/?tag=fawkes&amp;p=<?php echo $url; ?>&amp;j=n" height="1" width="1" /></div></noscript>
<?php
			if (get_http_var('c4') || get_http_var('c4x')) { ?>
<script type="text/javascript" src="http://www.channel4.com/media/scripts/statstag.js"></script> <!--//end WEB STATS --> <noscript><div style="display:none"><img width="1" height="1" src="http://stats.channel4.com/njs.gif?dcsuri=/nojavascript&amp;WT.js=No" alt="" /></div></noscript>
<?			}

			// mySociety tracking, not on staging
			if (defined('OPTION_TRACKING') && OPTION_TRACKING) {
		                track_event($extra);
			}
		}

		// DAMN, this really shouldn't be in PAGE.
		$db = new ParlDB;
		$db->display_total_duration();
		
		$rusage = getrusage();
		$duration = getmicrotime() - STARTTIME;
		twfy_debug ("TIME", "Total time for page: $duration seconds.");
		$duration = $rusage['ru_utime.tv_sec']*1000000 + $rusage['ru_utime.tv_usec'] - STARTTIMEU;
		twfy_debug ('TIME', "Total user time: $duration microseconds.");
		$duration = $rusage['ru_stime.tv_sec']*1000000 + $rusage['ru_stime.tv_usec'] - STARTTIMES;
		twfy_debug ('TIME', "Total system time: $duration microseconds.");
		
		?>
</div> <!-- end #container -->

</body>
</html>
<?php
	ob_end_flush();
	}



	function postcode_form () {
		// Used on the mp (and yourmp) pages.
		// And the userchangepc page.
		global $THEUSER;
		
		$MPURL = new URL('yourmp');
		?>
				<br />
<?php
		$this->block_start(array('id'=>'mp', 'title'=>'Find out about your MP'));
		?>
						<form action="<?php echo $MPURL->generate(); ?>" method="get">
<?php
	if (get_http_var('c4')) print '<input type="hidden" name="c4" value="1">';
	if (get_http_var('c4x')) print '<input type="hidden" name="c4x" value="1">';
		if ($THEUSER->postcode_is_set()) {
			
			$FORGETURL = new URL('userchangepc');
			$FORGETURL->insert(array('forget'=>'t'));
			?>
						<p>Your current postcode: <strong><?php echo $THEUSER->postcode(); ?></strong> &nbsp; <small>(<a href="<?php echo $FORGETURL->generate(); ?>" title="The cookie storing your postcode will be erased">Forget this postcode</a>)</small></p>
<?php
		}
		?>
						<p><strong>Enter your UK postcode: </strong>

						<input type="text" name="pc" value="<?php echo htmlentities(get_http_var('pc')); ?>" maxlength="10" size="10" /> <input type="submit" value="GO" class="submit" /> <small>(e.g. BS3 1QP)</small>
						</p>
						<input type="hidden" name="ch" value="t" />
						</form>
<?php	
		$this->block_end();
	}
	
	

	function member_rss_block ($urls) {
		// Returns the html for a person's rss feeds sidebar block.
		// Used on MP/Peer page.
		
		$html = '
				<div class="block">
				<h4>RSS feeds</h4>
					<div class="blockbody">
						<ul>
';
		if (isset($urls['appearances'])) {
			$html .= '<li><a href="' . $urls['appearances'] . '"><img src="/images/rss.gif" alt="RSS feed" border="0" align="middle" /></a> <a href="' . $urls['appearances'] . '">Recent appearances</a></li>';
		}
		
		$HELPURL = new URL('help');
			
		$html .= '
						</ul>
						<p><a href="' . $HELPURL->generate() . '#rss" title="An explanation of what RSS feeds are for"><small>What is RSS?</small></a></p>
					</div>
				</div>
';
		return $html;

	}
	

	function display_member($member, $extra_info) {
		global $THEUSER, $DATA, $this_page;

		#		if (get_http_var('c4')) {
			#			print '<p align="center"><img src="/images/yourmp_head.gif" style="border:none;"></p>';
			#		}

		$title = ucfirst($member['full_name']);
		if (!$member['current_member'] && $member['house']=='House of Commons') {
			$title .= ', former';
		}
		if ($member['house'] == 'House of Commons') $title .= ' MP';
		if ($rssurl = $DATA->page_metadata($this_page, 'rss')) {
			$title = '<a href="' . WEBPATH . $rssurl . '"><img src="/images/rss.gif" alt="RSS feed" border="0" align="right" /></a> ' . $title;
		}
		print '<p class="printonly">This data was produced by TheyWorkForYou from a variety of sources.</p>';
		$this->block_start(array('id'=>'mp', 'title'=>$title));
		if (is_file(BASEDIR . IMAGEPATH . 'mpsL/' . $member['person_id'] . '.jpg')) {
		?>
						<img src="<?php echo IMAGEPATH;?>mpsL/<?php echo $member['person_id']; ?>.jpg" alt="Photo of <?php echo $member['full_name']; ?>" class="portrait" />
		<? } elseif (is_file(BASEDIR . IMAGEPATH . 'mpsL/' . $member['person_id'] . '.jpeg')) {
		?>
						<img src="<?php echo IMAGEPATH;?>mpsL/<?php echo $member['person_id']; ?>.jpeg" alt="Photo of <?php echo $member['full_name']; ?>" class="portrait" />
		<? } elseif (is_file(BASEDIR . IMAGEPATH . 'mps/' . $member['person_id'] . '.jpg')) {
		?>
						<img src="<?php echo IMAGEPATH;?>mps/<?php echo $member['person_id']; ?>.jpg" alt="Photo of <?php echo $member['full_name']; ?>" height="118" class="portrait" />
		<? } elseif (is_file(BASEDIR . IMAGEPATH . 'mps/' . $member['person_id'] . '.jpeg')) {
		?>
						<img src="<?php echo IMAGEPATH;?>mps/<?php echo $member['person_id']; ?>.jpeg" alt="Photo of <?php echo $member['full_name']; ?>" height="118" class="portrait" />
		<? } ?>
						<ul class="hilites">
						<?php
		$desc = '';
		if (!$member['current_member']) {
			$desc = 'Former ';
		}
		if ($member['party']) {
			$desc .= htmlentities($member['party']);
			if ($member['party']=='Speaker' || $member['party']=='Deputy-Speaker')
				$desc .= ', and';
	       		if ($member['house'] == 'House of Commons') $desc .= ' MP for ' . $member['constituency'];
			elseif ($member['party'] != 'Bishop') $desc .= ' Peer';
		}
		if ($desc) print "<li><strong>$desc</strong></li>";
		if ($member['other_parties']) {
			print "<li>Changed party ";
			foreach ($member['other_parties'] as $r) {
				$out[] = 'from ' . $r['from'] . ' on ' . format_date($r['date'], SHORTDATEFORMAT);
			}
			print join('; ', $out);
			print '</li>';
		}

		// Ministerial position
		if (array_key_exists('office', $extra_info)) {
			$mins = array();
			foreach ($extra_info['office'] as $row) {
				if ($row['to_date'] == '9999-12-31' && $row['source'] != 'chgpages/selctee') {
					$m = prettify_office($row['position'], $row['dept']);
					$m .= ' (since ' . format_date($row['from_date'], SHORTDATEFORMAT) . ')';
					$mins[] = $m;
				}
			}
			if ($mins) {
				print '<li>' . join('<br />', $mins) . '</li>';
			}
		}

		if ($member['lord_entered_house']) {
			print '<li><strong>Entered the House of Lords ';
			if (strlen($member['lord_entered_house_text'])==4)
				print 'in ';
			else
				print 'on ';
			print $member['lord_entered_house_text'].'</strong>';
			print '</strong>';
			if ($member['lord_entered_reason']) print ' &mdash; ' . $member['lord_entered_reason'];
			print '</li>';
		}
		if ($member['mp_left_house']) {
			print '<li><strong>Previously MP for ';
			print $member['mp_left_constituency'] . ' until ';
			print $member['mp_left_house_text'].'</strong>';
			if ($member['mp_left_reason']) print ' &mdash; ' . $member['mp_left_reason'];
			print '</li>';
		}
		if ($member['entered_house'] != '1997-05-01') {
			if (strlen($member['entered_house_text'])==4) {
				print '<li><strong>Became a Lord in ';
			} else {
				print '<li><strong>Entered Parliament on ';
			}
			print $member['entered_house_text'].'</strong>';
			if ($member['entered_reason']) print ' &mdash; ' . $member['entered_reason'];
			print '</li>';
		}
		if (!$member['current_member']) {
			print '<li><strong>Left Parliament on '.$member['left_house_text'].'</strong>';
			if ($member['left_reason']) print ' &mdash; ' . $member['left_reason'];
			print '</li>';
		}
		if (isset($extra_info['majority_in_seat'])) { 
			?>
						<li><strong>Majority:</strong> 
						<?php echo number_format($extra_info['majority_in_seat']); ?> voters <?php

			if (isset($extra_info['swing_to_lose_seat_today'])) { 
				?>
							&#8212; <?php echo make_ranking($extra_info['swing_to_lose_seat_today_rank']); ?> safest out of <?php echo $extra_info['swing_to_lose_seat_today_rank_outof']; ?> MPs
<?php 
			} ?></li>
<?php 
		} 
		
					
		if ($member['the_users_mp'] == true) {
			$pc = $THEUSER->postcode();
			?>
						<li><a href="http://www.writetothem.com/?a=WMC&amp;pc=<?php echo htmlentities(urlencode($pc)); ?>"><strong>Send a message to <?php echo $member['full_name']; ?></strong></a> (only use this for <em>your</em> MP) <small>(via WriteToThem.com)</small></li>
						<li><a href="http://www.hearfromyourmp.com/?pc=<?=htmlentities(urlencode($pc)) ?>"><strong>Get messages from your MP</strong></a> <small>(via HearFromYourMP)</small></strong></a></li>
<?php
		} elseif ($member['house'] == 'House of Commons' && $member['current_member']) {
			?>
						<li><a href="http://www.writetothem.com/"><strong>Send a message to your MP</strong></a> <small>(via WriteToThem.com)</small></li>
						<li><a href="http://www.hearfromyourmp.com/"><strong>Sign up to <em>HearFromYourMP</em></strong></a> to get messages from your MP</li>
<?php
		} elseif ($member['house'] == "House of Lords" && $member['current_member']) {
			?>
						<li><a href="http://www.writetothem.com/?person=uk.org.publicwhip/person/<?php echo $member['person_id']; ?>"><strong>Send a message to <?php echo $member['full_name']; ?></strong></a> <small>(via WriteToThem.com)</small></li>
<?php

		}

		if ($member['current_member']) {
			print '<li><a href="/alert/?only=1&amp;pid='.$member['person_id'].'"><strong>Email me whenever '. $member['full_name']. ' speaks</strong></a> (no more than once per day)</li>';
		}

		?>
						</ul>
						
						
						<ul class="jumpers">
						<li><a href="#votingrecord">Voting record</a></li>
<?		if ($member['house'] == 'House of Commons') { ?>
						<li><a href="#topics">Topics of interest</a></li>
<?		} ?>
						<li><a href="#hansard">Recent appearances in Parliament</a></li>
						<li><a href="#numbers">Numerology</a></li>
<?php		if (isset($extra_info['register_member_interests_html'])) { ?>
						<li><a href="#register">Register of Members' Interests</a></li>
<?php		}
		if (isset($extra_info['expenses2004_col1'])) { ?>
 						<li><a href="#expenses">Expenses</a></li>
<?php		}

		if (isset($extra_info['edm_ais_url'])) {
			?>
						<li><a href="<?php echo $extra_info['edm_ais_url']; ?>">Early Day Motions signed by this MP</a> <small>(From edm.ais.co.uk)</small></li>
<?php
		}
		?>
						</ul>
<?php
		$this->block_end();

		// Voting Record.
		?> <a name="votingrecord"></a> <?php
		$this->block_start(array('id'=>'votingrecord', 'title'=>'Voting record (from PublicWhip)'));
		$displayed_stuff = 0;
		function display_dream_comparison($extra_info, $member, $dreamid, $desc, $inverse, $search) {
			if (isset($extra_info["public_whip_dreammp${dreamid}_distance"])) {
				$dmpscore = floatval($extra_info["public_whip_dreammp${dreamid}_distance"]);
				if ($inverse) 
					$dmpscore = 1.0 - $dmpscore;
				$dmpdesc = "unknown about";
				if ($dmpscore > 0.95 && $dmpscore <= 1.0)
					$dmpdesc = "very strongly against";
				elseif ($dmpscore > 0.85)
					$dmpdesc = "quite strongly against";
				elseif ($dmpscore > 0.6)
					$dmpdesc = "moderately against";
				elseif ($dmpscore > 0.4)
					$dmpdesc = "a mixture of for and against";
				elseif ($dmpscore > 0.15) 
					$dmpdesc = "moderately for";
				elseif ($dmpscore > 0.05) 
					$dmpdesc = "quite strongly for";
				elseif ($dmpscore >= 0.0) 
					$dmpdesc = "very strongly for";
				// How many votes Dream MP and MP both voted (and didn't abstain) in
				// $extra_info["public_whip_dreammp${dreamid}_both_voted"];
				$search_link = "/search/?s=" . urlencode($search) . 
					"&pid=" . $member['person_id'] . "&pop=1";
				?>
				<li>
				<strong><?=ucfirst($dmpdesc)?></strong>
			<?=$desc?>. 
<small class="unneededprintlinks"> 
<a href="http://www.publicwhip.org.uk/mp.php?mpid=<?=$member['member_id']?>&amp;dmp=<?=$dreamid?>">votes</a>,
<a href="<?=$search_link?>">speeches</a>
</small>

				</li>
<?php
				return true;
			}
			return false;
		}

	if (isset($extra_info["public_whip_dreammp219_distance"])) {
		$displayed_stuff = 1; ?>


	<p id="howvoted">How <?=$member['full_name']?> voted on key issues since 2001:</p>
	<ul id="dreamcomparisons">
	<?
		$got_dream = false;
		$got_dream |= display_dream_comparison($extra_info, $member, 811, "introducing a <strong>smoking ban</strong>", false, "smoking");
		#$got_dream |= display_dream_comparison($extra_info, $member, 856, "the <strong>changes to parliamentary scrutiny in the <a href=\"http://en.wikipedia.org/wiki/Legislative_and_Regulatory_Reform_Bill\">Legislative and Regulatory Reform Bill</a></strong>", false, "legislative and regulatory reform bill");
		$got_dream |= display_dream_comparison($extra_info, $member, 230, "introducing <strong>ID cards</strong>", true, "id cards");
		$got_dream |= display_dream_comparison($extra_info, $member, 363, "introducing <strong>foundation hospitals</strong>", false, "foundation hospital");
		$got_dream |= display_dream_comparison($extra_info, $member, 367, "introducing <strong>student top-up fees</strong>", true, "top-up fees");
		$got_dream |= display_dream_comparison($extra_info, $member, 258, "Labour's <strong>anti-terrorism laws</strong>", true, "terrorism");
		$got_dream |= display_dream_comparison($extra_info, $member, 219, "the <strong>Iraq war</strong>", true, "iraq");
		$got_dream |= display_dream_comparison($extra_info, $member, 358, "the <strong>fox hunting ban</strong>", true, "hunting");
		$got_dream |= display_dream_comparison($extra_info, $member, 826, "equal <strong>gay rights</strong>", false, "gay");
		if (!$got_dream) {
			print "<li>" . $member['full_name'] . " has not voted enough in this parliament to have any scores.</li>";
		}
		print '</ul>';
?>
<p class="italic">
<small>Read about <a href="/help/#votingrecord">how the voting record is decided</a>.</small>
</p>

<? } ?>

<?
		// Links to full record at Guardian and Public Whip	
		$record = array();
		if (isset($extra_info['guardian_howtheyvoted'])) {
			$record[] = '<a href="' . $extra_info['guardian_howtheyvoted'] . '" title="At The Guardian">well-known issues</a> <small>(from the Guardian)</small>';
		}
		if (isset($extra_info['public_whip_division_attendance']) && $extra_info['public_whip_division_attendance'] != 'n/a') { 
			$record[] = '<a href="http://www.publicwhip.org.uk/mp.php?id=uk.org.publicwhip/member/' . $member['member_id'] . '&amp;showall=yes#divisions" title="At Public Whip">their full record</a>';
		}

		if (count($record) > 0) {
			$displayed_stuff = 1;
			?>
			<p>More on <?php echo implode(' &amp; ', $record); ?></p>
<?php
		}
	        
		// Rebellion rate
		if (isset($extra_info['public_whip_rebellions']) && $extra_info['public_whip_rebellions'] != 'n/a') {	
			$displayed_stuff = 1;
	?>					<ul>
							<li><a href="http://www.publicwhip.org.uk/mp.php?id=uk.org.publicwhip/member/<?=$member['member_id'] ?>#divisions" title="See more details at Public Whip">
                        <strong><?php echo htmlentities(ucfirst($extra_info['public_whip_rebel_description'])); ?> rebels</strong></a> against their party<?php
			if (isset($extra_info['public_whip_rebelrank'])) {
				echo " in this parliament"; /* &#8212; ";
				if (isset($extra_info['public_whip_rebelrank_joint']))
					print 'joint ';
				echo make_ranking($extra_info['public_whip_rebelrank']);
				echo " most rebellious of ";
				echo $extra_info['public_whip_rebelrank_outof'];
				echo ($member['house']=='House of Commons') ? " MPs" : ' Lords';
				*/
			}
			?>.
			</li>
		</ul><?php
		}

		if (!$displayed_stuff) {
			print '<p>No data to display yet.</p>';
		}
		$this->block_end();

		# Topics of interest only for MPs at the moment
		if ($member['house'] == 'House of Commons') {

?>	<a name="topics"></a>
		<? $this->block_start(array('id'=>'topics', 'title'=>'Topics of interest')); 
		$topics_block_empty = true;

		// Select committee membership
		if (array_key_exists('office', $extra_info)) {
			$mins = array();
			foreach ($extra_info['office'] as $row) {
				if ($row['to_date'] == '9999-12-31' && $row['source'] == 'chgpages/selctee') {
					$m = prettify_office($row['position'], $row['dept']);
					if ($row['from_date']!='2004-05-28')
						$m .= ' (since ' . format_date($row['from_date'], SHORTDATEFORMAT) . ')';
					$mins[] = $m;
				}
			}
			if ($mins) {
				print "<p><strong>Select committee membership</strong></p>";
				print "<ul>";
				foreach ($mins as $min) {
					print '<li>' . $min . '</li>';
				}
				print "</ul>";
				$topics_block_empty = false;
			}
		}
		$wrans_dept = false;
		$wrans_dept_1 = null;
		$wrans_dept_2 = null;
		if (isset($extra_info['wrans_departments'])) { 
				$wrans_dept = true;
				$wrans_dept_1 = "<li><strong>Departments:</strong> ".$extra_info['wrans_departments']."</p>";
		} 
		if (isset($extra_info['wrans_subjects'])) { 
				$wrans_dept = true;
				$wrans_dept_2 = "<li><strong>Subjects:</strong> ".$extra_info['wrans_subjects']."</p>";
		} 
		
		if ($wrans_dept) {
			print "<p><strong>Asks most questions about</strong></p>";
			print "<ul>";
			if ($wrans_dept_2) print $wrans_dept_2;
			if ($wrans_dept_1) print $wrans_dept_1;
			print "</ul>";
			$topics_block_empty = false;
			$WRANSURL = new URL('search');
			$WRANSURL->insert(array('pid'=>$member['person_id'], 's'=>'section:wrans', 'pop'=>1));
		?>							<p><small>(based on <a href="<?=$WRANSURL->generate()?>">written questions asked by <?=$member['full_name']?></a> and answered by departments)</small></p><?
		}
		if ($topics_block_empty) {
			print "<p><em>This MP is not on any select committee and has had no written questions answered for which we know the department or subject.</em></p>";
		}
		$this->block_end();

		}

	?>		<a name="hansard"></a> <?
		$title = 'Most recent appearances in parliament';
		if ($rssurl = $DATA->page_metadata($this_page, 'rss')) {
			$title = '<a href="' . WEBPATH . $rssurl . '"><img src="/images/rss.gif" alt="RSS feed" border="0" align="right" /></a> ' . $title;
		}

		$this->block_start(array('id'=>'hansard', 'title'=>$title));
?>
<p><em>If this MP or Lord has contributed more than once to one debate, only one speech is shown.</em></p>
<?php
		// This is really far from ideal - I don't really want $PAGE to know
		// anything about HANSARDLIST / DEBATELIST / WRANSLIST.
		// But doing this any other way is going to be a lot more work for little 
		// benefit unfortunately.
	
	        twfy_debug_timestamp();
		$HANSARDLIST = new HANSARDLIST();
		
		$searchstring = "speaker:$member[person_id]";
		global $SEARCHENGINE;
        	$SEARCHENGINE = new SEARCHENGINE($searchstring); 
	    	$args = array (
	    		's' => $searchstring,
	    		'p' => 1,
	    		'num' => 3,
		        'pop' => 1,
			'o' => 'd',
	    	);
	        $HANSARDLIST->display('search_min', $args);
	        twfy_debug_timestamp();

		$MOREURL = new URL('search');
		$MOREURL->insert( array('pid'=>$member['person_id'], 'pop'=>1) );
		?>
	<p id="moreappear"><a href="<?php echo $MOREURL->generate(); ?>#n4">More of <?php echo ucfirst($member['full_name']); ?>'s recent appearances</a></p>

<?php
		if ($rssurl = $DATA->page_metadata($this_page, 'rss')) {
			// If we set an RSS feed for this page.
			$HELPURL = new URL('help');
			?>
					<p class="unneededprintlinks"><a href="<?php echo WEBPATH . $rssurl; ?>" title="XML version of this person's recent appearances">RSS feed</a> (<a href="<?php echo $HELPURL->generate(); ?>#rss" title="An explanation of what RSS feeds are for">?</a>)</p>
<?php
		}
		
		$this->block_end();


		?> <a name="numbers"></a> <?php
		$this->block_start(array('id'=>'numbers', 'title'=>'Numerology'));
		$displayed_stuff = 0;
		?>
		<p><em>Please note that numbers do not measure quality. 
		Also, MPs and Lords may do other things not currently covered
		by this site.</em> (<a href="/help/#numbers">More about this</a>)</p>
<ul>
<?php

		$since_text = 'in the last year';
		if ($member['entered_house'] > '2005-05-05')
			$since_text = 'since joining Parliament';

		$MOREURL = new URL('search');
		if ($member['house']=='House of Commons') {
			$section = 'debates';
		} else {
			$section = 'lordsdebates';
		}
		$MOREURL->insert(array('pid'=>$member['person_id'], 's'=>'section:'.$section, 'pop'=>1));
		$displayed_stuff |= display_stats_line('debate_sectionsspoken_inlastyear', 'Has spoken in <a href="' . $MOREURL->generate() . '">', 'debate', '</a> ' . $since_text, '', $extra_info);

		$MOREURL->insert(array('pid'=>$member['person_id'], 's'=>'section:wrans', 'pop'=>1));
		// We assume that if they've answered a question, they're a minister
		$minister = false; $Lminister = false;
		if (isset($extra_info['wrans_answered_inlastyear']) && $extra_info['wrans_answered_inlastyear'] > 0 && $extra_info['wrans_asked_inlastyear'] == 0)
			$minister = true;
		if (isset($extra_info['Lwrans_answered_inlastyear']) && $extra_info['Lwrans_answered_inlastyear'] > 0 && $extra_info['Lwrans_asked_inlastyear'] == 0)
			$Lminister = true;
		$displayed_stuff |= display_stats_line('wrans_asked_inlastyear', 'Has received answers to <a href="' . $MOREURL->generate() . '">', 'written question', '</a> ' . $since_text, '', $extra_info, $minister, $Lminister);

		if (isset($extra_info['select_committees'])) {
			print "<li>Is a member of <strong>$extra_info[select_committees]</strong> select committee";
			if ($extra_info['select_committees'] > 1)
				print "s";
			if (isset($extra_info['select_committees_chair']))
				print " ($extra_info[select_committees_chair] as chair)";
			print '.</li>';
		}

		if (isset($extra_info['writetothem_responsiveness_notes'])) {
		?><li>Responsiveness to messages sent via <a href="http://www.writetothem.com">WriteToThem.com</a> in 2005: <?=$extra_info['writetothem_responsiveness_notes']?>.</li><?
		} elseif (isset($extra_info['writetothem_responsiveness_mean_2005'])) {
			$mean = $extra_info['writetothem_responsiveness_mean_2005'];
			$extra_info['writetothem_responsiveness_mean_2005'] = $extra_info['writetothem_responsiveness_low_2005'] . ' &ndash; ' . $extra_info['writetothem_responsiveness_high_2005'];
			$displayed_stuff |= display_stats_line('writetothem_responsiveness_mean_2005', 'Replied within 2 or 3 weeks to <a href="http://www.writetothem.com/stats/2005/mps" title="From WriteToThem.com">', '', '</a> <!-- Mean: ' . $mean . ' --> of messages sent via WriteToThem.com during 2005, according to polling data', '', $extra_info);
		}

		$after_stuff = ' <small>(From Public Whip)</small>';
		if ($member['party'] == 'Scottish National Party') {
			$after_stuff .= '<br /><em>Note SNP MPs do not vote on legislation not affecting Scotland.</em>';
		}
		$displayed_stuff |= display_stats_line('public_whip_division_attendance', 'Has attended <a href="http://www.publicwhip.org.uk/mp.php?id=uk.org.publicwhip/member/' . $member['member_id'] . '&amp;showall=yes#divisions" title="See more details at Public Whip">', 'of vote', '</a> in parliament', $after_stuff, $extra_info);

		$displayed_stuff |= display_stats_line('comments_on_speeches', 'People have made <a href="/comments/recent/?pid='.$member['person_id'].'">', 'comment', "</a> on this MP's speeches", '', $extra_info);
		
		if (isset($extra_info['number_of_alerts'])) {
			$displayed_stuff = 1;
			?>
		<li><strong><?=htmlentities($extra_info['number_of_alerts']) ?></strong> <?=($extra_info['number_of_alerts']==1?'person is':'people are') ?> tracking whenever this <?=($member['house']=='House of Commons'?'MP':'peer') ?> speaks<?php
			if ($member['current_member']) {
				print ' &mdash; <a href="/alert/?only=1&amp;pid='.$member['person_id'].'">email me whenever '. $member['full_name']. ' speaks</a>';
			}
			print '.</li>';
		}

		$displayed_stuff |= display_stats_line('three_word_alliterations', 'Has used a three-word alliterative phrase (e.g. "she sells seashells") ', 'time', ' in debates', ' <small>(<a href="/help/#numbers">Why is this here?</a>)</small>', $extra_info);
		#		$displayed_stuff |= display_stats_line('ending_with_a_preposition', "Has ended a sentence with 'with' ", 'time', ' in debates', '', $extra_info);
		#		$displayed_stuff |= display_stats_line('only_asked_why', "Has made a speech consisting solely of 'Why?' ", 'time', ' in debates', '', $extra_info);


?>
						</ul>
<?php
		if (!$displayed_stuff) {
			print '<p>No data to display yet.</p>';
		}
		$this->block_end();

		if (isset($extra_info['register_member_interests_html'])) {
?>				
<a name="register"></a>
<?php
			$this->block_start(array('id'=>'register', 'title'=>"Register of Members' Interests"));

			if ($extra_info['register_member_interests_html'] != '') {
				echo $extra_info['register_member_interests_html'];
			} else {
				echo "\t\t\t\t<p>Nil</p>\n";
			}
			echo '<p class="italic">';
			if (isset($extra_info['register_member_interests_date'])) {
				echo 'Register last updated: ';
				echo format_date($extra_info['register_member_interests_date'], SHORTDATEFORMAT);
				echo '. ';
			}
			echo '<a href="http://www.publications.parliament.uk/pa/cm/cmregmem/051214/memi01.htm">More about the Register</a>';
			echo '</p>';
			print '<p><strong><span style="color: #ff0000;">New:</span> <a href="/regmem/?p='.$member['person_id'].'">View the history of this MP\'s entries in the Register</a></strong></p>';
			$this->block_end();
		}

		if (isset($extra_info['expenses2004_col1'])) {
?>
<a name="expenses"></a>
<?php
			$title = 'Expenses';
			$this->block_start(array('id'=>'expenses', 'title'=>$title));
			print '<p class="italic">Figures in brackets are ranks. Parliament\'s <a href="http://www.parliament.uk/site_information/allowances.cfm">explanatory notes</a>.</p>';
			print '<table class="people"><tr><th>Type</th><th>2004/05';
			if (isset($extra_info['expenses2005_col1_rank_outof'])) {
				print ' (ranking out of ' . $extra_info['expenses2005_col1_rank_outof'] . ')';
			}
			print '</th><th>2003/04';
			if (isset($extra_info['expenses2004_col1_rank_outof'])) {
				print ' (ranking out of&nbsp;'.$extra_info['expenses2004_col1_rank_outof'].')';
			}
			print '</th><th>2002/03';
			if (isset($extra_info['expenses2003_col1_rank_outof'])) {
				print ' (ranking out of&nbsp;'.$extra_info['expenses2003_col1_rank_outof'].')';
			}
			print '</th><th>2001/02';
			if (isset($extra_info['expenses2002_col1_rank_outof'])) {
				print ' (ranking out of&nbsp;'.$extra_info['expenses2002_col1_rank_outof'].')';
			}
			print '</th></tr>';
			print '<tr><td class="row-1">Additional Costs Allowance</td>';
			$this->expenses_printout('col1', $extra_info,1);
			print '</tr><tr><td class="row-2">London Supplement</td>';
			$this->expenses_printout('col2', $extra_info,2);
			print '</tr><tr><td class="row-1">Incidental Expenses Provision</td>';
			$this->expenses_printout('col3', $extra_info,1);
			print '</tr><tr><td class="row-2">Staffing Allowance</td>';
			$this->expenses_printout('col4', $extra_info,2);
			print '</tr><tr><td class="row-1">Members\' Travel</td>';
			$this->expenses_printout('col5', $extra_info,1);
			print '</tr><tr><td class="row-2">Members\' Staff Travel</td>';
			$this->expenses_printout('col6', $extra_info,2);
			print '</tr><tr><td class="row-1">Centrally Purchased Stationery</td>';
			$this->expenses_printout('col7', $extra_info,1);
			print '</tr><tr><td class="row-2">Stationery: Associated Postage Costs</td>';
			$this->expenses_printout('col7a', $extra_info,2);
			print '</tr><tr><td class="row-1">Centrally Provided Computer Equipment</td>';
			$this->expenses_printout('col8', $extra_info,1);
			print '</tr><tr><td class="row-2">Other Costs</td>';
			$this->expenses_printout('col9', $extra_info,2);
			print '</tr><tr><th style="text-align: right">Total</th>';
			$this->expenses_printout('total', $extra_info,1);
			print '</tr></table>';
			$this->block_end();
		}
	}
	
	function expenses_printout($col, $extra_info, $style) {
		for ($ey=2005; $ey>=2002; --$ey) {
			$k = 'expenses' . $ey . '_' . $col;
			$kr = $k . '_rank';
			print '<td class="row-'.$style.'">';
			if (isset($extra_info[$k])) {
				print '&pound;'.number_format(str_replace(',','',$extra_info[$k]));
			} elseif ($col=='col7a') {
				print 'N/A';
			} else {
				print '&nbsp;';
			}
			if (isset($extra_info[$kr]) && $extra_info[$k]>0) {
				print " (" . make_ranking($extra_info[$kr]) . ")";
			}
			print '</td>';
		}
	}
	
	
	function generate_member_links ($member, $links) {
		// Receives its data from $MEMBER->display_links;
		// This returns HTML, rather than outputting it.
		// Why? Because we need this to be in the sidebar, and 
		// we can't call the MEMBER object from the sidebar includes
		// to get the links. So we call this function from the mp
		// page and pass the HTML through to stripe_end(). Better than nothing.

		// Bah, can't use $this->block_start() for this, as we're returning HTML...
		$html = '
				<div class="block">
					<h4>More useful links for this ' . ($member->house()==1 ? 'MP' : 'peer') . '</h4>
					<div class="blockbody">
					<ul' . (get_http_var('c4')?' style="list-style-type:none;"':''). '>';

		if (isset($links['maiden_speech'])) {
			$maiden_speech = fix_gid_from_db($links['maiden_speech']);
			$html .= '<li><a href="/debate/?id=' . $maiden_speech . '">Maiden speech</a></li>';
		}

		// BIOGRAPHY.
		if (isset($links['mp_website'])) {
			$html .= '<li><a href="' . $links['mp_website'] . '">'. $member->full_name().'\'s personal website</a></li>';
		}

		if(isset($links['guardian_biography'])) {
			$html .= '	<li><a href="' . $links['guardian_biography'] . '">Biography</a> <small>(From The Guardian)</small></li>';
		}
		if(isset($links['wikipedia_url'])) {
			$html .= '	<li><a href="' . $links['wikipedia_url'] . '">Biography</a> <small>(From Wikipedia)</small></li>';
		}
		
		if(isset($links['diocese_url'])) {
			$html .= '	<li><a href="' . $links['diocese_url'] . '">Diocese website</a></li>';
		}
		
		if (isset($links['guardian_parliament_history'])) {
			$html .= '	<li><a href="' . $links['guardian_parliament_history'] . '">Parliamentary career</a> <small>(From The Guardian)</small></li>';
		}

		if (isset($links['guardian_election_results'])) {
			$html .= '	<li><a href="' . $links['guardian_election_results'] . '">Election results for ' . $member->constituency() . '</a> <small>(From The Guardian)</small></li>';
		}

		if (isset($links['guardian_candidacies'])) {
			$html .= '	<li><a href="' . $links['guardian_candidacies'] . '">Previous candidacies</a> <small>(From The Guardian)</small></li>';
		}

		if (isset($links['guardian_contactdetails'])) {
			$html .= '	<li><a href="' . $links['guardian_contactdetails'] . '">Contact details</a> <small>(From The Guardian)</small></li>';
		}

		if (isset($links['bbc_profile_url'])) {
			$html .= '	<li><a href="' . $links['bbc_profile_url'] . '">General information</a> <small>(From BBC News)</small></li>';

		} 

		
		$html .= "	</ul>
					</div>
				</div> <!-- end block -->
";
		return $html;
	}
	
	
	function error_message ($message, $fatal = false) {	
		// If $fatal is true, we exit the page right here.
		// $message is like the array used in $this->message()
			
		if (!$this->page_started()) {
			$this->page_start();
		}
		
		if (is_string($message)) {
			// Sometimes we're just sending a single line to this function
			// rather like the bigger array...
			$message = array (
				'text' => $message
			);
		}
		
		$this->message($message, 'error');
			
		if ($fatal) {
			if ($this->within_stripe()) {
				$this->stripe_end();
			}
			$this->page_end();
		}
	
	}
	
	
	function message ($message, $class='') {
		// Generates a very simple but common page content.
		// Used for when a user logs out, or votes, or any simple thing
		// where there's a little message and probably a link elsewhere.
		// $message is an array like:
		// 		'title' => 'You are now logged out'.
		//		'text'	=> 'Some more text here',
		//		'linkurl' => 'http://www.easyparliament.org/debates/',
		//		'linktext' => 'Back to previous page'
		// All fields optional.
		// 'linkurl' should already have htmlentities done on it.
		// $class is a class name that will be applied to the message's HTML elements.
		
		if ($class != '') {
			$class = ' class="' . $class . '"';
		}
		
		$need_to_close_stripe = false;
		
		if (!$this->within_stripe()) {
			$this->stripe_start();
			$need_to_close_stripe = true;
		}

		if (isset($message['title'])) {
			?>
			<h3<?php echo $class; ?>><?php echo $message['title']; ?></h3>
<?php
		}
		
		if (isset($message['text'])) {
			?>
			<p<?php echo $class; ?>><?php echo $message['text']; ?></p>
<?php
		}
		
		if (isset($message['linkurl']) && isset($message['linktext'])) {
			?>
			<p><a href="<?php echo $message['linkurl']; ?>"><?php echo $message['linktext']; ?></a></p>
<?php
		}
		
		if ($need_to_close_stripe) {
			$this->stripe_end();
		}
	}
	
	

	function set_hansard_headings ($info) {
		// Called from HANSARDLIST->display().
		// $info is the $data['info'] array passed to the template.
		// If the page's HTML hasn't already been started, it sets the page
		// headings that will be needed later in the page.
		
		global $DATA, $this_page;

		if (!$this->page_started()) {
			// The page's HTML hasn't been started yet, so we'd better do it.

			// Set the page title (in the <title></title>).

			$page_title = '';

			if (isset($info['text'])) {
				// Use a truncated version of the page's main item's body text.
				// trim_words() is in utility.php. Trim to 40 chars.
				$page_title = trim_characters($info['text'], 0, 40);
				
			} elseif (isset($info['year'])) {
				// debatesyear and wransyear pages.
				$page_title = $DATA->page_metadata($this_page, 'title');

				$page_title .= $info['year'];	
			}

			if (isset($info['date'])) {
				// debatesday and wransday pages.
				if ($page_title != '') {
					$page_title .= ': ';
				}
				$page_title .= format_date ($info['date'], SHORTDATEFORMAT);
			}
			
			if ($page_title != '') {
				$DATA->set_page_metadata($this_page, 'title', $page_title);
			}
		
			if (isset($info['date'])) {
				// Set the page heading (displayed on the page).
				$page_heading = format_date($info['date'], LONGERDATEFORMAT);
				$DATA->set_page_metadata($this_page, 'heading', $page_heading);
			}
	
		}

	}

	
	function nextprevlinks () {
		
		// Generally called from $this->stripe_end();
		
		global $DATA, $this_page;

		// We'll put the html in these and print them out at the end of the function...
		$prevlink = '';
		$uplink = '';
		$nextlink = '';
	
		// This data is put in the metadata in hansardlist.php
		$nextprev = $DATA->page_metadata($this_page, 'nextprev');
		// $nextprev will have three arrays: 'prev', 'up' and 'next'.
		// Each should have a 'body', 'title' and 'url' element.


		// PREVIOUS ////////////////////////////////////////////////

		if (isset($nextprev['prev'])) {

			$prev = $nextprev['prev'];
			
			if (isset($prev['url'])) {	
				$prevlink = '<a href="' . $prev['url'] . '" title="' . $prev['title'] . '">&laquo; ' . $prev['body'] . '</a>';
		
			} else {
				$prevlink = '&laquo; ' . $prev['body'];
			}
		}
		
		if ($prevlink != '') {
			$prevlink = '<span class="prev">' . $prevlink . '</span>';
		}
		
		
		// UP ////////////////////////////////////////////////
		
		if (isset($nextprev['up'])) {

			$uplink = '<span class="up"><a href="' .  $nextprev['up']['url'] . '" title="' . $nextprev['up']['title'] . '">' . $nextprev['up']['body'] . '</a></span>';
		}
		
		
		// NEXT ////////////////////////////////////////////////

		if (isset($nextprev['next'])) {
			$next = $nextprev['next'];
			
			if (isset($next['url'])) {
				$nextlink = '<a href="' .  $next['url'] . '" title="' . $next['title'] . '">' . $next['body'] . ' &raquo;</a>';
			} else {
				$nextlink = $next['body'] . ' &raquo;';
			}
		}
		
		if ($nextlink != '') {
			$nextlink = '<span class="next">' . $nextlink . '</span>';
		}
		
		
		// Now output the HTML!
		?>
				<p class="nextprev">
					<?php print $uplink; ?>
						
					<?php print $prevlink; ?>
						
					<?php print $nextlink; ?>

				</p>
<?php		
					
	}

	 
	function recess_message () {
		// Returns a message if parliament is currently in recess.
		include_once INCLUDESPATH."easyparliament/recess.php";
	
		$message = '';
		
		$recess = currently_in_recess();
		
		if ($recess != false) {
			list($name, $from, $to) = $recess;
			$from = format_date($from, SHORTDATEFORMAT);
			$to = format_date($to, SHORTDATEFORMAT);
            // If years are the same, only print once
            if (substr($from, -4, 4) == substr($to, -4, 4)) {
                $from = substr($from, 0, strlen($from) - 4);
            }
			$message = "$name. Parliament is not sitting from $from until $to.";
		}
		
		return $message;
	}


	function trackback_rss ($trackbackdata) {
		/*
		Outputs Trackback Auto Discovery RSS for something.
		
		$trackbackdata = array (
			'itemurl' 	=> 'http://www.easyparliament.org/debate/?id=2003-02-28.544.2',
			'pingurl' 	=> 'http://www.easyparliament.org/trackback/?e=2345',
			'title' 	=> 'This item or page title',
			'date' 		=> '2003-02-28T13:47:00+00:00'
		);
		*/
		?>
<!--
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:dc="http://purl.org/dc/elements/1.1/"
         xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/">
<rdf:Description
    rdf:about="<?php echo $trackbackdata['itemurl']; ?>"
    trackback:ping="<?php echo $trackbackdata['pingurl']; ?>"
    dc:identifier="<?php echo $trackbackdata['itemurl']; ?>"
    dc:title="<?php echo str_replace('"', "'", $trackbackdata['title']); ?>"
    dc:date="<?php echo $trackbackdata['date']; ?>" />
</rdf:RDF>
-->
<?php
	}

	function search_form ($value='') {
		global $SEARCHENGINE;
		// Search box on the search page.
		// If $value is set then it will be displayed in the form.
		// Otherwise the value of 's' in the URL will be displayed.

		$wtt = get_http_var('wtt');

		$URL = new URL('search');
		$URL->reset(); // no need to pass any query params as a form action. They are not used.
		
		if ($value == '')
			$value = get_http_var('s');

		if ($wtt<2) {
		?>
<div class="mainsearchbox">
					<form action="<?php echo $URL->generate(); ?>" method="get">
<?php if (get_http_var('o')) { ?>
					<input type="hidden" name="o" value="<?php echo htmlentities(get_http_var('o')); ?>" />
<?php }
	if (get_http_var('house')) { ?>
					<input type="hidden" name="house" value="<?=htmlentities(get_http_var('house')); ?>" />
<?php } ?>
					<input type="text" name="s" value="<?php echo htmlentities($value); ?>" maxlength="100" size="20" />
					<input type="submit" value=" <?=($wtt?'Modify search':'Search') ?> " /><br />
<?

if ($wtt) print '<input type="hidden" name="wtt" value="1">';

} else { ?>
<div class="mainsearchbox">
	<form action="http://www.writetothem.com/lords" method="get">
	<input type="hidden" name="pid" value="<?=htmlentities(get_http_var('pid')) ?>">
	<input type="submit" style="font-size: 150%" value=" I want to write to this Lord " /><br />
<? }

if (!$wtt) { ?>
<div style='margin-top: 5px'>
<?php
     $orderUrl = new URL('search');
        $ordering = get_http_var('o');
        if ($ordering!='r' && $ordering!='d' && $ordering != 'p') {
            $ordering='d';
        }
        
        if ($ordering=='r') {
            print '<strong>Most relevant results are first</strong>';
        } else {
            printf("<a href='%s'>Show most relevant results first</a>", $orderUrl->generate('html', array('o'=>'r')));
        }

        print "&nbsp;|&nbsp;";
        if ($ordering=='d') {
            print '<strong>Most recent results are first</strong>';
        } else {
            printf("<a href='%s'>Show most recent results first</a>", $orderUrl->generate('html', array('o'=>'d')));
        }

	print "&nbsp;|&nbsp;";
	if ($ordering=='p') {
		print '<strong>Use by person</strong>';
	} else {
		printf('<a href="%s">Show use by person</a>', $orderUrl->generate('html', array('o'=>'p')));
	}
?>
</div>
<?php	$qds = '';
	if ($SEARCHENGINE) {
		$qds = $SEARCHENGINE->query_description_short();
		$plural = 'is';
		if (strstr($qds, 'phrases') || strstr($qds, 'words') || preg_match('/word.*?phrase/', $qds)) $plural = 'are';
		$qds .= " $plural mentioned in Parliament";
		$qds = preg_replace('#^spoken by (.*?) is mentioned in#', 'something is said by $1 in', $qds);
		$qds = preg_replace('#spoken by (.*?) is mentioned in#', 'is spoken by $1 in', $qds);
	}
        $person_id = get_http_var('pid');
        if ($person_id != "") {
            $member = new MEMBER(array('person_id' => $person_id));
	    if ($member->valid) {
	            $name = $member->full_name();
                ?>
                    <p>
                    <input type="radio" name="pid" value="<?php echo htmlentities($person_id) ?>" checked>Search only <?php echo htmlentities($name) ?> 
                    <input type="radio" name="pid" value="" >Search all speeches
                    </p>
                <?
	    }
       }
}
            ?>

					</form>
				</div><? if ($SEARCHENGINE && !$wtt) { ?>
			<div class="stripe-2" align="center" style="margin-bottom: 0.5em">
		<a href="/alert/?only=1<?=($value?'&amp;keyword='.urlencode($value):'') . ($person_id?'&amp;pid='.urlencode($person_id):'') ?>">Email me when <?=$qds ?></a>
		</div>
<?php				}
	}
	
	
	
	
	function login_form ($errors = array()) {
		// Used for /user/login/ and /user/prompt/
		// $errors is a hash of potential errors from a previous log in attempt.
		?>
				<form method="post" action="<?php $URL = new URL('userlogin'); $URL->reset(); echo $URL->generate(); ?>">


<?php
		if (isset($errors["email"])) {
			$this->error_message($errors['email']);
		}
		if (isset($errors["invalidemail"])) {
			$this->error_message($errors['invalidemail']);
		}
?>
				<div class="row">
				<span class="label"><label for="email">Email address:</label></span>
				<span class="formw"><input type="text" name="email" id="email" value="<?php echo htmlentities(get_http_var("email")); ?>" maxlength="100" size="30" class="form" /></span>
				</div>

<?php
		if (isset($errors["password"])) {
			$this->error_message($errors['password']);
		}
		if (isset($errors["invalidpassword"])) {
			$this->error_message($errors['invalidpassword']);
		}
?>
				<div class="row">
				<span class="label"><label for="password">Password:</label></span>
				<span class="formw"><input type="password" name="password" id="password" maxlength="30" size="20" class="form" /></span>
				</div>

				<div class="row">
				<span class="label">&nbsp;</span>
				<span class="formw"><input type="checkbox" name="remember" id="remember" value="true" <?php
		$remember = get_http_var("remember");
		if (get_http_var("submitted") != "true" || $remember == "true") {
			print "checked=\"checked\" ";
		}
		?>/> <label for="remember">Remember login details.*</label></span>
				</div>

				<div class="row">
				<span class="label">&nbsp;</span>
				<span class="formw"><input type="submit" value="Login" class="submit" /> <small><a href="<?php 
		$URL = new URL("userpassword");
		$URL->insert(array("email"=>get_http_var("email")));
		echo $URL->generate(); 
?>">Forgotten your password?</a></small></span>
				</div>

				<div class="row">
				<small></small>
				</div>

				<input type="hidden" name="submitted" value="true" />
<?php
		// I had to havk about with this a bit to cover glossary login.
		// Glossary returl can't be properly formatted until the "add" form
		// has been submitted, so we have to do this rubbish:
		global $glossary_returl;
		if ((get_http_var("ret") != "") || ($glossary_returl != "")) {
			// The return url for after the user has logged in.
			if (get_http_var("ret") != "") {
				$returl = get_http_var("ret");
			}
			else {
				$returl = $glossary_returl;
			}
			?>
				<input type="hidden" name="ret" value="<?php echo htmlentities($returl); ?>" />
<?php
		}
		?>
				</form>
<?php
	}
	
	
	
	function mp_search_form ($person_id) {
		// Search box on the MP page.
	
		$URL = new URL('search');
		$URL->remove(array('s'));
		?>
				<div class="mpsearchbox">
					<form action="<?php echo $URL->generate(); ?>" method="get">
                    <p>
                    <input name="s" size="12" maxlength="100" /> 
                    <input type="hidden" name="pid" value="<?=$person_id ?>" />
                    <input type="submit" class="submit" value="GO" /></p>
					</form>
				</div>
<?php
	}

	
	function glossary_search_form ($args) {
		// Search box on the glossary page.
		global $THEUSER;
		
		$type = "";

		if (isset($args['blankform']) && $args['blankform'] == 1) {
			$formcontent = "";
		}
		else {
			$formcontent = htmlentities(get_http_var('g'));
		}
		
		if ($THEUSER->isloggedin()) {
			$URL = new URL($args['action']);
			$URL->remove(array('g'));
		}
		else {
			$URL = new URL('userprompt');
			$URL->remove(array('g'));
			$type = "<input type=\"hidden\" name=\"type\" value=\"2\" />";
		}
		
		$add_link = $URL->generate('url');
		?>
		<form action="<?php echo $add_link; ?>" method="get">
		<?php echo $type; ?>
		<p>Help make TheyWorkForYou.com better by adding a definition:<br />
		<label for="g"><input type="text" name="g" value="<?php echo $formcontent; ?>" size="45" />
		<input type="submit" value="Search" class="submit" /></label>
		</p>
		</form>
<?php
	}

	function glossary_add_definition_form ($args) {
		// Add a definition for a new Glossary term.
		global $GLOSSARY;
		
		$URL = new URL($args['action']);
		$URL->remove(array('g'));
		
		?>
	<div class="glossaryaddbox">
		<form action="<?php print $URL->generate(); ?>" method="post">
		<input type="hidden" name="g" value="<?php echo $args['s']; ?>"/>
		<input type="hidden" name="return_page" value="glossary" />
		<label for="definition"><p><textarea name="definition" id="definition" rows="15" cols="55"><?php echo htmlentities($GLOSSARY->current_term['body']); ?></textarea></p>
		
		<p><input type="submit" name="previewterm" value="Preview" class="submit" />
		<input type="submit" name="submitterm" value="Post" class="submit" /></p></label>
		<p><small>Only &lt;b&gt; and &lt;i&gt; tags are allowed. URLs and email addresses will automatically be turned into links.</small></p>
	</div>
<?php
	}

	function glossary_add_link_form ($args) {
		// Add an external link to the glossary.
		global $GLOSSARY;
		
		$URL = new URL('glossary_addlink');
		$URL->remove(array('g'));
		?>
	<h4>All checks fine and dandy!</h4><p>Just so you know, we found <strong><?php echo $args['count']; ?></strong> occurences of <?php echo $GLOSSARY->query; ?> in Hansard</p>
	<p>Please add your link below:</p>
	<h4>Add an external link for <em><?php echo $args['s']; ?></em></h4>
	<div class="glossaryaddbox">
		<form action="<?php print $URL->generate(); ?>" method="post">
		<input type="hidden" name="g" value="<?php echo $args['s']; ?>"/>
		<input type="hidden" name="return_page" value="glossary" />
		<label for="definition"><input type="text" name="definition" id="definition" />
		<p><!-- input type="submit" name="previewterm" value="Preview" class="submit" /-->
		<input type="submit" name="submitterm" value="Post" class="submit" /></p></label>
		<p><small>Only &lt;b&gt; and &lt;i&gt; tags are allowed. URLs and email addresses will automatically be turned into links.</small></p>
	</div>
<?php
	}
	
	function glossary_atoz(&$GLOSSARY) {
	// Print out a nice list of lettered links to glossary pages	
				
		$letters = array ();
		
		foreach ($GLOSSARY->alphabet as $letter => $eps) {
			// if we're writing out the current letter (list or item)
			if ($letter == $GLOSSARY->current_letter) {
				// if we're in item view - show the letter as "on" but make it a link
				if ($GLOSSARY->current_term != '') {
					$URL = new URL('glossary');
					$URL->insert(array('az' => $letter));
					$letter_link = $URL->generate('url');
					
					$letters[] = "<li class=\"on\"><a href=\"" . $letter_link . "\">" . $letter . "</a></li>";
				}
				// otherwise in list view show no link
				else {
					$letters[] = "<li class=\"on\">" . $letter . "</li>";
				}
			}
			elseif (!empty($GLOSSARY->alphabet[$letter])) {
				$URL = new URL('glossary');
				$URL->insert(array('az' => $letter));
				$letter_link = $URL->generate('url');
				
				$letters[] = "<li><a href=\"" . $letter_link . "\">" . $letter . "</a></li>";
			}
			else {
				$letters[] = '<li>' . $letter . '</li>';
			}
		}
		?>
					<div class="letters">
						<ul>
	<?php
		for($n=0; $n<13; $n++) {
			print $letters[$n];
		}
		?>
						</ul>
						<ul>
	<?php
		for($n=13; $n<26; $n++) {
			print $letters[$n];
		}
		?>
						</ul>
					</div>
					<div class="break">&nbsp;</div>
		<?php
	}

	function glossary_display_term(&$GLOSSARY) {
	// Display a single glossary term
		global $this_page;
		
		$term = $GLOSSARY->current_term;

		$term['body'] = $GLOSSARY->glossarise($term['body'], 0, 1);

		// add some extra controls for the administrators
		if ($this_page == "admin_glossary"){
			print "<a id=\"gl".$term['glossary_id']."\"></a>";
			print "<h3>" . $term['title'] . "</h3>";
			$URL = new URL('admin_glossary');
			$URL->insert(array("delete_confirm" => $term['glossary_id']));
			$delete_url = $URL->generate();
			$admin_links = "<br /><small><a href=\"".$delete_url."\">delete<a/></small>";
		}
		else {
			$admin_links = "";
		}

		if (isset($term['user_id'])) {
			$URL = new URL('userview');
			$URL->insert(array('u' => $term['user_id']));
			$user_link = $URL->generate('url');
			
			$user_details = "\t\t\t\t<p><small>contributed by user <a href=\"" . $user_link . "\">" . $term['firstname'] . " " . $term['lastname'] . "</a></small>" . $admin_links . "</p>\n";
		}
		else {
			$user_details = "";
		}

		print "\t\t\t\t<p class=\"glossary-body\">" . $term['body'] . "</p>\n" . $user_details;

		if ($this_page == "glossary_item") {
			// Add a direct search link for current glossary item
			$URL = new URL('search');
			// remember to quote the term for phrase matching in search
			$URL->insert(array('s' => '"'.$term['title'].'"'));
			$search_url = $URL->generate();
			printf ("\t\t\t\t<p>Search hansard for \"<a href=\"%s\" title=\"View search results for this glossary item\">%s</a>\"</p>", $search_url, $term['title']);
		}
	}



	function glossary_display_match_list(&$GLOSSARY) {
			if ($GLOSSARY->num_search_matches > 1) {
				$plural = "them";
				$definition = "some definitions";
			} else {
				$plural = "it";
				$definition = "a definition";
			}
			?>
			<h4>Found <?php echo $GLOSSARY->num_search_matches; ?> matches for <em><?php echo $GLOSSARY->query; ?></em></h4>
			<p>It seems we already have <?php echo $definition; ?> for that. Would you care to see <?php echo $plural; ?>?</p>
			<ul class="glossary"><?
			foreach ($GLOSSARY->search_matches as $match) {
				$URL = new URL('glossary');
				$URL->insert(array('gl' => $match['glossary_id']));
				$URL->remove(array('g'));
				$term_link = $URL->generate('url');
				?><li><a href="<?php echo $term_link ?>"><?php echo $match['title']?></a></li><?
			}
			?></ul>
<?php
	}

	function glossary_addterm_link() {
		// print a link to the "add glossary term" page
		$URL = new URL('glossary_addterm');
		$URL->remove(array("g"));
		$glossary_addterm_link = $URL->generate('url');
		print "<small><a href=\"" . $glossary_addterm_link . "\">Add a term to the glossary</a></small>";
	}

	function glossary_addlink_link() {
		// print a link to the "add external link" page
		$URL = new URL('glossary_addlink');
		$URL->remove(array("g"));
		$glossary_addlink_link = $URL->generate('url');
		print "<small><a href=\"" . $glossary_addlink_link . "\">Add an external link</a></small>";
	}


	function glossary_link() {
		// link to the glossary with no epobject_id - i.e. show all entries
		$URL = new URL('glossary');
		$URL->remove(array("g"));
		$glossary_link = $URL->generate('url');
		print "<small><a href=\"" . $glossary_link . "\">Browse the glossary</a></small>";
	}
	
	function glossary_links() {
		print "<div>";
		$this->glossary_link();
		print "<br />";
		$this->glossary_addterm_link();
		print "</div>";
	}
	
	function page_links ($pagedata) {
		// The next/prev and page links for the search page.
		global $this_page;
		
		// $pagedata has...
		$total_results 		= $pagedata['total_results'];
		$results_per_page 	= $pagedata['results_per_page'];
		$page 				= $pagedata['page'];
		
		
		if ($total_results > $results_per_page) {
			
			$numpages = ceil($total_results / $results_per_page);
			
			$pagelinks = array();
			
			// How many links are we going to display on the page - don't want to 
			// display all of them if we have 100s...
			if ($page < 10) {
				$firstpage = 1;
				$lastpage = 10;
			} else {
				$firstpage = $page - 10;
				$lastpage = $page + 9;
			}
		
			if ($firstpage < 1) {
				$firstpage = 1;
			}	
			if ($lastpage > $numpages) {
				$lastpage = $numpages;
			}
		
			// Generate all the page links.
			$URL = new URL($this_page);
			$URL->insert(array('wtt'=>get_http_var('wtt')));
			for ($n = $firstpage; $n <= $lastpage; $n++) {
				
				if ($n > 1) {
					$URL->insert(array('p'=>$n));
				} else {
					// No page number for the first page.
					$URL->remove(array('p'));
				}
				if (isset($pagedata['pid'])) {
					$URL->insert(array('pid'=>$pagedata['pid']));
				}
				
				if ($n != $page) {
					$pagelinks[] = '<a href="' . $URL->generate() . '">' . $n . '</a>';
				} else {
					$pagelinks[] = "<strong>$n</strong>";
				}
			}
			
			// Display everything.
			
			?>
				<div class="pagelinks">
					Result page: 
<?php
			
			if ($page != 1) {
				$prevpage = $page - 1;
				$URL->insert(array('p'=>$prevpage));
				?>
					<big><strong><a href="<?php echo $URL->generate(); ?>"><big>&laquo;</big> Previous</a></strong></big>
<?php
			}
			
			echo "\t\t\t\t" . implode(' ', $pagelinks); 

			if ($page != $numpages) {
				$nextpage = $page + 1;
				$URL->insert(array('p'=>$nextpage));
				?>

					<big><strong><a href="<?php echo $URL->generate(); ?>">Next <big>&raquo;</big></a></strong></big> <?php
			}	
		
			?>

				</div>
<?php
			
		}	
	
	}

	
	
	function comment_form ($commentdata) {
		// Comment data must at least contain an epobject_id.
		// Comment text is optional.
		// 'return_page' is either 'debate' or 'wran'.
		/* array (
			'epobject_id' => '7',
			'gid' => '2003-02-02.h34.2',
			'body' => 'My comment text is here.',
			'return_page' => 'debate'
		  )
		*/
		global $THEUSER, $this_page;

		if (!isset($commentdata['epobject_id']) || !is_numeric($commentdata['epobject_id'])) {
			$this->error_message("Sorry, we need an epobject id");
			return;
		}
		
		if (!$THEUSER->isloggedin()) {
			// The user is not logged in.
			
			// The URL of this page - we'll want to return here after joining/logging in.
			$THISPAGEURL = new URL($this_page);
			
			// The URLs to login / join pages.
			$LOGINURL = new URL('userlogin');
			$LOGINURL->insert(array('ret'=>$THISPAGEURL->generate().'#addcomment'));
			$JOINURL = new URL('userjoin');
			$JOINURL->insert(array('ret'=>$THISPAGEURL->generate().'#addcomment'));
			
			?>
				<p><a href="<?php echo $LOGINURL->generate(); ?>">Log in</a> or <a href="<?php echo $JOINURL->generate(); ?>">join</a> to post a comment.</p>
<?php
			return;
			
		} else if (!$THEUSER->is_able_to('addcomment')) {
			// The user is logged in but not allowed to post a comment.

			?>
				<p>You are not allowed to post comments.</p>
<?php
			return;
		}
		
		// We can post a comment...
			
		$ADDURL = new URL('addcomment');
		$RULESURL = new URL('houserules');
		?>
				<h4>Type your comment</h4>
				<a name="addcomment"></a>
				
				<p><small>
Please read our <a href="<?php echo $RULESURL->generate(); ?>"><strong>House Rules</strong></a> before posting your first comment.</small></p>

				<form action="<?php echo $ADDURL->generate(); ?>" method="post">
					<p><textarea name="body" rows="15" cols="55"><?php
		if (isset($commentdata['body'])) {
			echo htmlentities($commentdata['body']);
		}
		?></textarea></p>

					<p><input type="submit" value="Preview" class="submit" />
					<input type="submit" name="submitcomment" value="Post" class="submit" /></p>
					<input type="hidden" name="epobject_id" value="<?php echo $commentdata['epobject_id']; ?>" />
					<input type="hidden" name="gid" value="<?php echo $commentdata['gid']; ?>" />
					<input type="hidden" name="return_page" value="<?php echo $commentdata['return_page']; ?>" />
				</form>
<?php
	}


	function display_commentreport ($data) {
		// $data has key value pairs.
		// Called from $COMMENT->display_report().
		
		if ($data['user_id'] > 0) {
			$USERURL = new URL('userview');
			$USERURL->insert(array('id'=>$data['user_id']));
			$username = '<a href="' . $USERURL->generate() . '">' . htmlentities($data['user_name']) . '</a>';
		} else {
			$username = $data['user_name'];
		}
		?>	
				<div class="comment">
					<p class="credit"><strong>Comment report</strong><br />
					<small>Reported by <?php echo $username; ?> on <?php echo $data['reported']; ?></small></p>

					<p><?php echo htmlentities($data['body']); ?></p>
				</div>
<?php
		if ($data['resolved'] != 'NULL') {
			?>
				<p>&nbsp;<br /><em>This report has not been resolved.</em></p>
<?php
		} else {
			?>
				<p><em>This report was resolved on <?php echo $data['resolved']; ?></em></p>
<?php
			// We could link to the person who resolved it with $data['resolvedby'],
			// a user_id. But we don't have their name at the moment.
		}	
	
	}
	
	
	function display_commentreportlist ($data) {
		// For the admin section.
		// Gets an array of data from COMMENTLIST->render().
		// Passes it on to $this->display_table().

		if (count($data) > 0) {
		
			?>
			<h3>Reported comments</h3>
<?php
			// Put the data in an array which we then display using $PAGE->display_table().
			$tabledata['header'] = array(
				'Reported by',
				'Begins...',
				'Reported on',
				''
			);
			
			$tabledata['rows'] = array();
			
			$EDITURL = new URL('admin_commentreport');
		
			foreach ($data as $n => $report) {
				
				if (!$report['locked']) {
					// Yes, we could probably cope if we just passed the report_id
					// through, but this isn't a public-facing page and life's 
					// easier if we have the comment_id too.
					$EDITURL->insert(array(
						'rid' => $report['report_id'],
						'cid' => $report['comment_id'],
					));
					$editlink = '<a href="' . $EDITURL->generate() . '">View</a>';
				} else {
					$editlink = 'Locked';
				}
				
				$body = trim_characters($report['body'], 0, 40);
								
				$tabledata['rows'][] = array (
					$report['firstname'] . ' ' . $report['lastname'],
					$body,
					$report['reported'],
					$editlink
				);
	
			}
			
			$this->display_table($tabledata);
			
		} else {
		
			print "<p>There are no outstanding comment reports.</p>\n";
		}
	
	}
	


	function display_calendar_month ($month, $year, $dateArray, $page) {
		// From http://www.zend.com/zend/trick/tricks-Oct-2002.php
		// Adjusted for style, putting Monday first, and the URL of the page linked to.
		
		// Used in templates/html/hansard_calendar.php
		
		// $month and $year are integers.
		// $dateArray is an array of dates that should be links in this month.
		// $page is the name of the page the dates should link to.
	
		// Create array containing abbreviations of days of week.
		$daysOfWeek = array('Mon','Tue','Wed','Thu','Fri','Sat','Sun');
		
		// What is the first day of the month in question?
		$firstDayOfMonth = mktime(0,0,0,$month,1,$year);
		
		// How many days does this month contain?
		$numberDays = date('t',$firstDayOfMonth);
		
		// Retrieve some information about the first day of the
		// month in question.
		$dateComponents = getdate($firstDayOfMonth);
		
		// What is the name of the month in question?
		$monthName = $dateComponents['month'];
		
		// If this calendar is for this current, real world, month
		// we get the value of today, so we can highlight it.
		$nowDateComponents = getdate();
		if ($nowDateComponents['mon'] == $month && $nowDateComponents['year'] == $year) {
			$toDay = $nowDateComponents['mday'];
		} else {
			$toDay = '';
		}
		
		// What is the index value (0-6) of the first day of the
		// month in question.
		
		// Adjusted to cope with the week starting on Monday.
		$dayOfWeek = $dateComponents['wday'] - 1;
		
		// Adjusted to cope with the week starting on Monday.
		if ($dayOfWeek < 0) {
			$dayOfWeek = 6;
		}
		
		// Create the table tag opener and day headers
		
		$calendar  = "\t\t\t\t<div class=\"calendar\">\n";
		$calendar .= "\t\t\t\t<table border=\"0\">\n";
		$calendar .= "\t\t\t\t<caption>$monthName $year</caption>\n";
		$calendar .= "\t\t\t\t<thead>\n\t\t\t\t<tr>";
		
		// Create the calendar headers
		
		foreach($daysOfWeek as $day) {
			$calendar .= "<th>$day</th>";
		} 
		
		// Create the rest of the calendar
		
		// Initiate the day counter, starting with the 1st.
		
		$currentDay = 1;
		
		$calendar .= "</tr>\n\t\t\t\t</thead>\n\t\t\t\t<tbody>\n\t\t\t\t<tr>";
		
		// The variable $dayOfWeek is used to
		// ensure that the calendar
		// display consists of exactly 7 columns.
		
		if ($dayOfWeek > 0) { 
			$calendar .= "<td colspan=\"$dayOfWeek\">&nbsp;</td>"; 
		}
		
		$DAYURL = new URL($page);
		
		while ($currentDay <= $numberDays) {
		
			// Seventh column (Sunday) reached. Start a new row.
			
			if ($dayOfWeek == 7) {
			
				$dayOfWeek = 0;
				$calendar .= "</tr>\n\t\t\t\t<tr>";
			}
			
		
			// Is this day actually Today in the real world?
			// If so, higlight it.
			if ($currentDay == $toDay) {
				$calendar .= '<td class="on">';
			} else {
				$calendar .= '<td>';
			}

			// Is the $currentDay a member of $dateArray? If so,
			// the day should be linked.
			if (in_array($currentDay,$dateArray)) {
			
				$date = sprintf("%04d-%02d-%02d", $year, $month, $currentDay);
				
				$DAYURL->insert(array('d'=>$date));
				
				$calendar .= "<a href=\"" . $DAYURL->generate() . "\">$currentDay</a></td>";
				
				// $currentDay is not a member of $dateArray.
			
			} else {
			
				$calendar .= "$currentDay</td>";
			}
			
			// Increment counters
			
			$currentDay++;
			$dayOfWeek++;
		}
		
		// Complete the row of the last week in month, if necessary
		
		if ($dayOfWeek != 7) { 
		
			$remainingDays = 7 - $dayOfWeek;
			$calendar .= "<td colspan=\"$remainingDays\">&nbsp;</td>"; 
		}
		
		
		$calendar .= "</tr>\n\t\t\t\t</tbody>\n\t\t\t\t</table>\n\t\t\t\t</div> <!-- end calendar -->\n\n";
		
		return $calendar;
	
	}


	function display_table($data) {
		/* Pass it data to be displayed in a <table> and it renders it
			with stripes.
		
		$data is like (for example):
		array (
			'header' => array (
				'ID',
				'name'
			),
			'rows' => array (
				array (
					'37',
					'Guy Fawkes'
				),
				etc...
			)
		)
		*/

		?>
	<table border="1" cellpadding="3" cellspacing="0" width="90%">
<?php
		if (isset($data['header']) && count($data['header'])) {
			?>
	<thead>
	<tr><?php
			foreach ($data['header'] as $text) {
				?><th><?php echo $text; ?></th><?php
			}
			?></tr>
	</thead>
<?php
		}
		
		if (isset($data['rows']) && count($data['rows'])) {
			?>
	<tbody>
<?php
			foreach ($data['rows'] as $row) {
				?>
	<tr><?php
				foreach ($row as $text) {
					?><td><?php echo $text; ?></td><?php
				}
				?></tr>
<?php
			}
			?>
	</tbody>
<?php
		}
	?>
	</table>
<?php

	}
	
	
	
	function admin_menu () {
		// Returns HTML suitable for putting in the sidebar on Admin pages.
		global $this_page, $DATA;
		
		$pages = array ('admin_home', 
                'admin_comments','admin_trackbacks', 'admin_searchlogs', 'admin_failedsearches',
                'admin_statistics', 
                'admin_commentreports', 'admin_glossary', 'admin_glossary_pending', 'admin_badusers',
		'admin_alerts',
                );
		
		$links = array();
	
		foreach ($pages as $page) {
			$title = $DATA->page_metadata($page, 'title');
			
			if ($page != $this_page) {
				$URL = new URL($page);
				$title = '<a href="' . $URL->generate() . '">' . $title . '</a>';
			} else {
				$title = '<strong>' . $title . '</strong>';
			}

			$links[] = $title;
		}
		
		$html = "<ul>\n";
		
		$html .= "<li>" . implode("</li>\n<li>", $links) . "</li>\n";
		
		$html .= "</ul>\n";
		
		return $html;
	}
	
	
	
	function _mt_javascript () {
		// This javascript is included in the head of the page if we're 
		// on an individual archive page in the sitenews section.
		// It does the 'Remember me?' stuff for comment forms.
		?>
	<script type="text/javascript" language="javascript">
	<!--
	
	var HOST = '<?php echo DOMAIN; ?>';
	
	// Copyright (c) 1996-1997 Athenia Associates.
	// http://www.webreference.com/js/
	// License is granted if and only if this entire
	// copyright notice is included. By Tomer Shiran.
	
	function setCookie (name, value, expires, path, domain, secure) {
		var curCookie = name + "=" + escape(value) + ((expires) ? "; expires=" + expires.toGMTString() : "") + ((path) ? "; path=" + path : "") + ((domain) ? "; domain=" + domain : "") + ((secure) ? "; secure" : "");
		document.cookie = curCookie;
	}
	
	function getCookie (name) {
		var prefix = name + '=';
		var c = document.cookie;
		var nullstring = '';
		var cookieStartIndex = c.indexOf(prefix);
		if (cookieStartIndex == -1)
			return nullstring;
		var cookieEndIndex = c.indexOf(";", cookieStartIndex + prefix.length);
		if (cookieEndIndex == -1)
			cookieEndIndex = c.length;
		return unescape(c.substring(cookieStartIndex + prefix.length, cookieEndIndex));
	}
	
	function deleteCookie (name, path, domain) {
		if (getCookie(name))
			document.cookie = name + "=" + ((path) ? "; path=" + path : "") + ((domain) ? "; domain=" + domain : "") + "; expires=Thu, 01-Jan-70 00:00:01 GMT";
	}
	
	function fixDate (date) {
		var base = new Date(0);
		var skew = base.getTime();
		if (skew > 0)
			date.setTime(date.getTime() - skew);
	}
	
	function rememberMe (f) {
		var now = new Date();
		fixDate(now);
		now.setTime(now.getTime() + 365 * 24 * 60 * 60 * 1000);
		setCookie('mtcmtauth', f.author.value, now, '/', HOST, '');
		setCookie('mtcmtmail', f.email.value, now, '/', HOST, '');
		setCookie('mtcmthome', f.url.value, now, '/', HOST, '');
	}
	
	function forgetMe (f) {
		deleteCookie('mtcmtmail', '/', HOST);
		deleteCookie('mtcmthome', '/', HOST);
		deleteCookie('mtcmtauth', '/', HOST);
		f.email.value = '';
		f.author.value = '';
		f.url.value = '';
	}
	
	//-->
	</script>
<?php
	
	
	}



}


$PAGE = new PAGE;

function display_stats_line($category, $blurb, $type, $inwhat, $afterstuff, $extra_info, $minister = false, $Lminister = false) {
	$return = false;
	if (isset($extra_info[$category]))
		$return = display_stats_line_house(1, $category, $blurb, $type, $inwhat, $extra_info, $minister, $afterstuff);
	if (isset($extra_info["L$category"]))
		$return = display_stats_line_house(2, "L$category", $blurb, $type, $inwhat, $extra_info, $Lminister, $afterstuff);
	return $return;
}
function display_stats_line_house($house, $category, $blurb, $type, $inwhat, $extra_info, $minister, $afterstuff) {
	if ($extra_info[$category]==0) $blurb = preg_replace('#<a.*?>#', '', $blurb);
	if ($house==2) $inwhat = str_replace('MP', 'Lord', $inwhat);
	print '<li>' . $blurb;
	print '<strong>' . $extra_info[$category];
	if ($type) print ' ' . make_plural($type, $extra_info[$category]);
	print '</strong>';
	print $inwhat;
	if ($minister)
		print ' &#8212; Ministers do not ask written questions';
	else {
		if (isset($extra_info[$category . '_quintile'])) {
			print ' &#8212; ';
			$q = $extra_info[$category . '_quintile'];
			if ($q == 0) {
				print 'well above average';
			} elseif ($q == 1) {
				print 'above average';
			} elseif ($q == 2) {
				print 'average';
			} elseif ($q == 3) {
				print 'below average';
			} elseif ($q == 4) {
				print 'well below average';
			} else {
				print '[Impossible quintile!]';
			}
			print ' amongst ';
			print ($house==1?'MP':'Lord') . 's';
		} elseif (isset($extra_info[$category . '_rank'])) {
			print ' &#8212; ';
			#if (isset($extra_info[$category . '_rank_joint']))
			#	print 'joint ';
			print make_ranking($extra_info[$category . '_rank']) . ' out of ' . $extra_info[$category . '_rank_outof'];
			print ' ' . ($house==1?'MP':'Lord') . 's';
		}
	}
	print ".$afterstuff</li>";
	return true;
}

?>
