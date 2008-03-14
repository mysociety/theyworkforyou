<?php

include '../404.php';

include_once "../../includes/easyparliament/init.php";
include_once INCLUDESPATH . "easyparliament/glossary.php";
include_once INCLUDESPATH . "easyparliament/member.php";

// For displaying written answers

if (get_http_var('d')) {
	// We have a date. so show all wrans on this day.
	$this_page = "spwransday";
	$args = array (
		'date' => get_http_var('d')
	);
	$LIST = new SPWRANSLIST;
	$LIST->display('date', $args);

} elseif (get_http_var('spid')) {
	# We have a Scottish Parliament ID, need to find the date
	$spid = get_http_var('spid');
	$SPWRANSLIST = new SPWRANSLIST;
	$gid = $SPWRANSLIST->get_gid_from_spid($spid);
    	if ($gid) {
        	$URL = new URL('spwrans');
		$URL->insert( array('id' => $gid) );
		header('Location: http://' . DOMAIN . $URL->generate('none'), true, 301);
        	exit;
	}
	$PAGE->error_message ("Couldn't match that Scottish Parliament ID to a GID.");

} elseif (get_http_var('id')) {
	// We have an id so show that item.
	// Could be a section id or a q/a id.
	// Either way, we'll get a section heading and the q/as beneath it.

	$this_page = "spwrans";
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

	$SPWRANSLIST = new SPWRANSLIST;
	
	$result = $SPWRANSLIST->display('gid', $args);
    // If it is a redirect, change URL
    if (is_string($result)) {
        $URL = new URL('spwrans');
		$URL->insert( array('id'=>$result) );
		header('Location: http://' . DOMAIN . $URL->generate('none'), true, 301);
        exit;
    }

	$PAGE->stripe_start('side', 'comments');

	// Display all comments for this ep object.
	$COMMENTLIST = new COMMENTLIST;
	
	$args['user_id'] = get_http_var('u');	// For highlighting their comments.
	$args['epobject_id'] = $SPWRANSLIST->epobject_id();
	
	$COMMENTLIST->display('ep', $args);
	
	$PAGE->stripe_end();
	
	
	$PAGE->stripe_start('side', 'addcomment');

	$commentdata = array (
		'epobject_id' 	=> $SPWRANSLIST->epobject_id(),
		'gid' 			=> $SPWRANSLIST->gid(),
		'return_page' 	=> $this_page
	);
	$PAGE->comment_form($commentdata);


	// We show trackbacks on this page.
	// We need that epobject_id for trackbacks too...	


	// Display comment-adding help if user is logged in.
	if ($THEUSER->isloggedin()) {
		$sidebar = array (
			array (
				'type'		=> 'include',
				'content' 	=> 'comment'
			)
		);
		$PAGE->stripe_end($sidebar);
	} else {
		$PAGE->stripe_end();
	}
	
#	$TRACKBACK = new TRACKBACK;
	
#	$TRACKBACK->display('epobject_id', $commentdata);
		
	
	
} elseif (get_http_var('y') != '') {

	// Show a calendar for a particular year's debates.

	// No date or wrans id. Show recent days with wrans on.

	$this_page = 'spwransyear';
	
	if (is_numeric(get_http_var('y'))) {
		$pagetitle = $DATA->page_metadata($this_page, 'title');
		$DATA->set_page_metadata($this_page, 'title', $pagetitle.' '.get_http_var('y'));
	}
	
	$PAGE->page_start();

	$PAGE->stripe_start();

	$args = array (
		'year' => get_http_var('y')
	);

	$LIST = new SPWRANSLIST;
	
	$LIST->display('calendar', $args);

	$PAGE->stripe_end(array(
		array (
			'type' => 'nextprev'
		),
		array (
			'type' => 'include',
			'content' => "spwrans"
		)
	));



} elseif (get_http_var('pid')) {
	$this_page = "spwransmp";
	$args = array (
		'person_id' => get_http_var('pid'),
		'page' => get_http_var('p')
	);
	$MEMBER = new MEMBER(array('person_id'=>$args['person_id']));
	if ($MEMBER->valid) {
		$pagetitle = $DATA->page_metadata($this_page, 'title');
		$DATA->set_page_metadata($this_page, 'title', $pagetitle . ' ' . $MEMBER->full_name() );
	}
	$LIST = new SPWRANSLIST;
	$LIST->display('mp', $args);
} else {

	// No date or wrans id. Show recent days with wrans on.
	
	$this_page = "spwransfront";

	$PAGE->page_start();

	$PAGE->stripe_start();
	?>
				<h3>Some recent written answers</h3>
<?php
	
	$SPWRANSLIST = new SPWRANSLIST;
	$SPWRANSLIST->display('recent_wrans', array('days'=>7, 'num'=>20));

	$PAGE->stripe_end(array(
		array (
			'type' => 'nextprev'
		),
		array (
			'type' => 'include',
			'content' => 'calendar_spwrans'
		),
		array (
			'type' => 'include',
			'content' => "spwrans"
		)
	));
}

$PAGE->page_end();

?>
