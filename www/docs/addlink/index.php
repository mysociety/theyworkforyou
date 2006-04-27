	<?php

include_once "../../includes/easyparliament/init.php";
include_once INCLUDESPATH."easyparliament/glossary.php";
include_once INCLUDESPATH."easyparliament/glossarylist.php";


$this_page = "glossary_addlink";

$args = array( 'action' => $this_page);

// First things first...

if ((get_http_var('g') != '') && (get_http_var('previewterm') == '') ) {
	// We're searching for something.
	$args['s'] = filter_user_input(get_http_var('g'), 'strict');
	$GLOSSARY = new GLOSSARY($args);
}
else {
	$args['sort'] = "regexp_replace";
	$GLOSSARY = new GLOSSARY($args);
	$args['s'] = filter_user_input(get_http_var('g'), 'strict');
}

$PAGE->page_start();

$PAGE->stripe_start();

$data = array (
	'title' => get_http_var('g'),
	'body'	=> get_http_var('definition')
);

// For previewing and adding a Glossary term.
// We should have post args of 'body' and 'term'.

if (get_http_var("submitterm") != '') {

	// We're submitting a comment.
	$success = $GLOSSARY->create($data);
	
	if ($success) {
		// $success will be the editqueue_id().		
		print "<h4>All good so far...</h4><p>Your definition for <strong>&quot;" . $data['title'] . "&quot;</strong> now awaits moderator approval or somesuch thing...</p>";
		$PAGE->glossary_links();
	} else {
		$PAGE->error_message("Sorry, there was an error and we were unable to add your Glossary item.");
	}
} elseif (get_http_var("previewterm") != '') {
// We're previewing a Glossary definition.

	if (get_http_var('definition') != '') {

		// Mock up a "current term" to send to the display function		
		$body = get_http_var('definition');
		$title = get_http_var('g');
		
		$GLOSSARY->current_term['body'] = filter_user_input($body, 'comment'); // In init.php
		$GLOSSARY->current_term['title'] = filter_user_input($title, 'comment'); // In init.php

		// Off it goes...
		print "<p>Your entry should look something like this:</p>";
		
		print "<p>";
		$PAGE->glossary_display_term($GLOSSARY);
		print "</p>";
		
		// Then, in case they aren't happy with it, show them the form again
		$PAGE->glossary_add_definition_form($args)
;
	}
} elseif ($GLOSSARY->query != '') {
// Deal with all the various searching possiblities...
	
	if($GLOSSARY->num_search_matches >= 1) {
		// Offer a list of matching terms
		$PAGE->glossary_display_match_list($GLOSSARY);
	}
	// Eek! no results at all? Excellent...
	else {
		// Ok, so now we can see of the word(s) appear in Hansard at all.
		// The following query was modified from the hansardlist search.
		// However, no point checking, if the user can't add terms. 
		if ($THEUSER->is_able_to('addterm')) {
		
			$args['count'] = $GLOSSARY->hansard_count($args);
			if ($args['count']) {	
				// Display the Add definition form
				$PAGE->glossary_add_link_form($args);
			}
			else {
				print "<h4>No dice!</h4><p>Much as we'd love you to add a definition for <strong></strong>, it doesn't seem to appear in hansard at all...</p>";
				$PAGE->glossary_links();
			}
		}
		else {
			print "<h4>Humdinger!</h4><p>it would appear that you aren't allowed to add glossary terms. How odd...</p>";
			$PAGE->glossary_links();
		}
	}
} else {

	$PAGE->stripe_start();

	// We just arrived here empty handed...
	
	print "<p>Help make TheyWorkForYou.com better. Add an external URL to the parliamentary record. Webify parliament!</p>";
	print "<h3>Step 1: Search for a phrase</h3>";
	
	$PAGE->glossary_search_form($args);

	print "<p><small>Some examples:<br />";
	print "An external organisation e.g. &quot;Devon County Council&quot;<br />";
	print "An external web document e.g. &quot;Criminal Justice Bill 2003&quot;</small></p>";
	print "<p>Or browse the existing links:</p>";

	$PAGE->glossary_atoz($GLOSSARY);

	$PAGE->stripe_end(array (
		array (
			'type'		=> 'include',
			'content'	=> 'glossary_add'
		)
	));
	
	print "<div class=\"break\">&nbsp;</div>";
	print "<br />";
}

$PAGE->stripe_end();

print $this_page;

$PAGE->page_end();



?>
