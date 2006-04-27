<?php

include_once "../../includes/easyparliament/init.php";
include_once INCLUDESPATH."easyparliament/glossary.php";
include_once INCLUDESPATH."easyparliament/glossarylist.php";


//$this_page = "glossary_addterm";
$this_page = "help_us_out";

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

// Check that people aren't trying to define silly words
if (
	in_array(strtolower($GLOSSARY->query), $GLOSSARY->stopwords)
) {
	$GLOSSARY->query = "";
	$args['blankform'] = 1;
	$URL = new URL('help_us_out');
	$backlink = $URL->generate();
	$error_message = "Sorry, that phrase appears too many times to be a useful as a link within the parliamentary record.";
}

// do a quick searchengine count
if ($GLOSSARY->query != "") {
	$SEARCHENGINE= new SEARCHENGINE('"'.$args['s'].'"');
	$args['count'] = $SEARCHENGINE->run_count();
	if (!$args['count']) {
		$GLOSSARY->query = "";
		$args['blankform'] = 1;
		$error_message = "Unfortunately <strong>" . $args['s'] . "</strong>, doesn't seem to appear in hansard at all...</p>";
	}
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
		print "<h4>Thank you for your help</h4><p>Your definition for <strong>&quot;" . $data['title'] . "&quot;</strong> has been submitted and awaits moderator approval. If every thing is well and good, it should appear on the site within the next day or so.</p>";
		print "<p>You can browse the exising glossary below:</p>";
		$PAGE->glossary_atoz($GLOSSARY);
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
		print "<h3>$title</h3>";
		
		$PAGE->glossary_display_term($GLOSSARY);

		
		// Then, in case they aren't happy with it, show them the form again
		$PAGE->glossary_add_definition_form($args);
	}
	
	$PAGE->stripe_end();
	
	
} elseif ($GLOSSARY->query != '') {
// Deal with all the various searching possiblities...
	
	if($GLOSSARY->num_search_matches >= 1) {
		// Offer a list of matching terms
		$PAGE->glossary_display_match_list($GLOSSARY);
		print "<p>Or you can browse the whole glossary:</p>";
		$PAGE->glossary_atoz($GLOSSARY);
	}
	// Eek! no results at all? Excellent...
	else {
		// Ok, so now we can see of the word(s) appear in Hansard at all.
		// The following query was modified from the hansardlist search.
		// However, no point checking, if the user can't add terms. 
		if ($THEUSER->is_able_to('addterm')) {
		
			// glossary matches should always be quoted.
			// Just to be sure, we'll strip away any quotes and add them again.
			if (preg_match("/^(\"|\')/", $args['s'])) {
				$args['s'] = preg_replace("/\"|\'/", "", $args['s']);
			}

			if ($args['count']) {	

				print "<h4>So far so good</h4><p>Just so you know, we found <strong>" . $args['count'] . "</strong> occurences of <strong>" . stripslashes($GLOSSARY->query) . "</strong> in Hansard.<br />Just to make sure that your definition will not appear out of context, please have a look at the <a href=\"#excerpts\">excerpts</a>. If you're happy that your definition will apply to the right thing, then carry on below:</p>";

				print "<a id='definition'></a>";
				print "<p>Please add your definition below:</p>";
				print "<h4>Add a definition for <em>" . $args['s'] . "</em></h4>";

				// Display the Add definition form
				$PAGE->glossary_add_definition_form($args);

				print "<a id=\"excerpts\"></a>";
				print "<h4>Contextual excerpts</h4>";
				// display some results so we can see what's happening
				// How many example results do we want to see?
				$args['num'] = 5;
				// force hansardlist to use the glossary search template,
				// while still performing a standard search.
				$args['view_override'] = "glossary_search";
				$LIST = new HANSARDLIST();				
				$LIST->display('search', $args);
				print "<p><a href=\"#definition\">Back to form</a></p>";
			}
		}
	}
} else {
	// We just arrived here empty handed...
	
	if (isset($error_message)) {
		$PAGE->error_message($error_message);
	}
	
	print "<p>Seen a piece of jargon or an external reference? By adding the phrase and definition to the glossary, you'll create a link for it everywhere an MP or Peer says it. Search for a phrase to add or browse the existing entries for inspiration.</p>";
	print "<h3>Step 1: Search for a phrase</h3>";
	
	$PAGE->glossary_search_form($args);
	
	$URL = new URL('glossary');

	// Early Day Motion
	$URL->insert(array("gl" => "90"));
	$earlyday_url = $URL->generate();

	// Black rod
	$URL->insert(array("gl" => "109"));
	$blackrod_url = $URL->generate();

	// Devon county council
	$URL->insert(array("gl" => "12"));
	$devoncc_url = $URL->generate();

	// Hutton Report
	$URL->insert(array("gl" => "7"));
	$hutton_url = $URL->generate();

	print "<p><small>Some examples:<br />";
	print "A technical term, or a piece of jargon e.g. <em>&quot;<a href=\"".$earlyday_url."\">Early Day Motion</a>&quot;(671 occurences)</em> or <em>&quot;<a href=\"".$blackrod_url."\">Black Rod</a>&quot;(12 occurences)</em><br />";
	print "An external organisation e.g. <em>&quot;<a href=\"".$devoncc_url."\">Devon County Council</a>&quot;(80 occurences)</em><br />";
	print "An external web document e.g. <em>&quot;<a href=\"".$hutton_url."\">Hutton Report</a>&quot;(104 occurences)</em></small></p>";
	print "<p>Or browse the existing entries:</p>";

	$PAGE->glossary_atoz($GLOSSARY);

	$PAGE->stripe_end(array (
		array (
			'type'		=> 'include',
			'content'	=> 'glossary_add'
		)
	));


}

$PAGE->page_end();



?>
