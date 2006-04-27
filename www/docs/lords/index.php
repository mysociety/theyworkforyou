<?php

include_once "../../includes/easyparliament/init.php";
include_once "../../includes/easyparliament/glossary.php";

// For displaying all the Lords debates on a day, or a single debate. 

if (get_http_var("d") != "") {
	if (get_http_var('c') != '') {
		$this_page = 'lordsdebatescolumn';
		$args = array(
			'date' => get_http_var('d'),
			'column' => get_http_var('c')
		);
		$LIST = new LORDSDEBATELIST;
		$LIST->display('column', $args);
	} else {
	// We have a date. so show all debates on this day.
	
	$this_page = "lordsdebatesday";
	
	$args = array (
		'date' => get_http_var('d')
	);
	
	$LIST = new LORDSDEBATELIST;
	
	$LIST->display('date', $args);
	}
	
} elseif (get_http_var('id') != "") {
	// We have an id so show that item.
	// Could be a section id (so we get a list of all the subsections in it),
	// or a subsection id (so we'd get the whole debate),
	// or an item id within a debate in which case we just get that item and some headings.
	
	$this_page = "lordsdebates";

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

	$LIST = new LORDSDEBATELIST;
	
	$result = $LIST->display('gid', $args);
	// If it is a redirect, change URL
	if (is_string($result)) {
		$URL = new URL('lordsdebates');
		$URL->insert( array('id'=>$result) );
		header('Location: http://' . DOMAIN . $URL->generate('none'));
		exit;
	}

	// We show trackbacks on this page.
#	$args = array (
#		'epobject_id' => $LIST->epobject_id()
#	);
#	$TRACKBACK = new TRACKBACK;
#	$TRACKBACK->display('epobject_id', $args);
	
} elseif (get_http_var('y') != '') {
	
	// Show a calendar for a particular year's debates.
	
	$this_page = 'lordsdebatesyear';

	if (is_numeric(get_http_var('y'))) {
		$pagetitle = $DATA->page_metadata($this_page, 'title');
		$DATA->set_page_metadata($this_page, 'title', $pagetitle.' '.get_http_var('y'));
	}
	
	$PAGE->page_start();

	$PAGE->stripe_start();

	$args = array (
		'year' => get_http_var('y')
	);

	$LIST = new LORDSDEBATELIST;
	
	$LIST->display('calendar', $args);

	
	$PAGE->stripe_end(array(
		array (
			'type' => 'nextprev'
		),
		array (
			'type' => 'include',
			'content' => "holdebates"
		)
	));
	
} elseif (get_http_var('gid') != '') {
	$this_page = 'lordsdebate';
	$args = array('gid' => get_http_var('gid') );
	$LORDSDEBATELIST = new LORDSDEBATELIST;
	$result = $LORDSDEBATELIST->display('gid', $args);
	// If it is a redirect, change URL
	if (is_string($result)) {
		$URL = new URL('lordsdebate');
		$URL->insert( array('gid'=>$result) );
		header('Location: http://' . DOMAIN . $URL->generate('none'));
		exit;
	}
	if ($LORDSDEBATELIST->htype() == '12' || $LORDSDEBATELIST->htype() == '13') {
		$PAGE->stripe_start('side', 'comments');
		$COMMENTLIST = new COMMENTLIST;
		$args['user_id'] = get_http_var('u');
		$args['epobject_id'] = $LORDSDEBATELIST->epobject_id();
		$COMMENTLIST->display('ep', $args);
		$PAGE->stripe_end();
		$PAGE->stripe_start('side', 'addcomment');
		$commendata = array(
			'epobject_id' => $LORDSDEBATELIST->epobject_id(),
			'gid' => get_http_var('id'),
			'return_page' => $this_page
		);
		$PAGE->comment_form($commendata);
		if ($THEUSER->isloggedin()) {
			$sidebar = array(
				array(
					'type' => 'include',
					'content' => 'comment'
				)
			);
			$PAGE->stripe_end($sidebar);
		} else {
			$PAGE->stripe_end();
		}
#		$TRACKBACK = new TRACKBACK;
#		$TRACKBACK->display('epobject_id', $commendata);
	}
} else {
	// No date or debate id. Show recent years with debates on.

	$this_page = "lordsdebatesfront";
	
	$PAGE->page_start();

	$PAGE->stripe_start();
	?>
				<h4>Busiest debates from the most recent week</h4>
<?php
	
	$LORDSDEBATELIST = new LORDSDEBATELIST;
	$LORDSDEBATELIST->display('biggest_debates', array('days'=>7, 'num'=>20));

	$rssurl = $DATA->page_metadata($this_page, 'rss');
	$PAGE->stripe_end(array(
		array (
			'type' => 'nextprev'
		),
		array (
			'type' => 'include',
			'content' => 'calendar_holdebates'
		),
		array (
			'type' => 'include',
			'content' => "holdebates"
		),
		array (
			'type' => 'html',
			'content' => '<div class="block"><h4><a href="/' . $rssurl . '">RSS feed of most recent debates</a></h4></div>'
		)
	));
	
}


$PAGE->page_end();

debug_timestamp("page end");

?>
