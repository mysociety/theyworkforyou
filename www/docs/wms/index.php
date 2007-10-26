<?php

include_once "../../includes/easyparliament/init.php";
include_once "../../includes/easyparliament/glossary.php";


// For displaying all the WMS on a day, or a single WMS. 

if (get_http_var("d") != "") {
	// We have a date. so show all WMS on this day.
	$this_page = "wmsday";
	$args = array (
		'date' => get_http_var('d')
	);
	$LIST = new WMSLIST;
	$LIST->display('date', $args);
	
} elseif (get_http_var('y') != '') {
	
	// Show a calendar for a particular year's WMS.
	
	$this_page = 'wmsyear';

	if (is_numeric(get_http_var('y'))) {
		$pagetitle = $DATA->page_metadata($this_page, 'title');
		$DATA->set_page_metadata($this_page, 'title', $pagetitle.' '.get_http_var('y'));
	}
	
	$PAGE->page_start();

	$PAGE->stripe_start();

	$args = array (
		'year' => get_http_var('y')
	);

	$LIST = new WMSLIST;
	
	$LIST->display('calendar', $args);

	
	$PAGE->stripe_end(array(
		array (
			'type' => 'nextprev'
		),
		array (
			'type' => 'include',
			'content' => "wms"
		)
	));
} elseif (get_http_var('id') != '') {
	$this_page = 'wms';
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

	
	$WMSLIST = new WMSLIST;
	
	$result = $WMSLIST->display('gid', $args);
	if (is_string($result)) {
		$URL = new URL('wms');
		$URL->insert( array('id'=>$result) );
		header('Location: http://' . DOMAIN . $URL->generate('none'), true, 301);
		exit;
	}

	$PAGE->stripe_start('side', 'comments');
	$COMMENTLIST = new COMMENTLIST;
	$args['user_id'] = get_http_var('u');
	$args['epobject_id'] = $WMSLIST->epobject_id();
	$COMMENTLIST->display('ep', $args);
	$PAGE->stripe_end();
	$PAGE->stripe_start('side', 'addcomment');
	$commendata = array(
		'epobject_id' => $WMSLIST->epobject_id(),
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
#	$TRACKBACK = new TRACKBACK;
#	$TRACKBACK->display('epobject_id', $commendata);
} else {
	// No date or debate id. Show recent WMS.

	$this_page = "wmsfront";
	
	$PAGE->page_start();

	$PAGE->stripe_start();
	?>
				<h4>Some recent written ministerial statements</h4>
<?php
	
	$WMSLIST = new WMSLIST;
	$WMSLIST->display('recent_wms', array('days'=>7, 'num'=>20));

	$rssurl = $DATA->page_metadata($this_page, 'rss');
	$PAGE->stripe_end(array(
		array (
			'type' => 'nextprev'
		),
		array (
			'type' => 'include',
			'content' => 'calendar_wms'
		),
		array (
			'type' => 'include',
			'content' => "wms"
		),
		array (
			'type' => 'html',
			'content' => '<div class="block">
<h4>RSS feed</h4>
<p><a href="' . WEBPATH . $rssurl . '"><img border="0" alt="RSS feed" align="middle" src="http://www.theyworkforyou.com/images/rss.gif"></a>
<a href="' . WEBPATH . $rssurl . '">RSS feed of recent statements</a></p>
</div>'

		)
	));
	
}


$PAGE->page_end();



?>
