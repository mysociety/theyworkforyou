<?php

include_once "../../includes/easyparliament/init.php";

$db = new ParlDB;

$this_page = "epvote";

// For when the user votes on whether a Hansard things is interesting or not.
// Doing everything within the page, 'cos it's simpler and a one-off.

// We should have:
// v=1 or v=0 			(yes or no)
// id=37 				(epobject_id)
// ret=/fawkes/blah.php (The return URL)


// We first check the browser accepts cookies.
// We set a 'testcookie' and redirect the user to this same URL
// with 'testing=true' on the end.
// If we come to this page with testing=true we know we need to 
// check for the presence of the cookie. If the cookie's there
// then the user can vote.


if (get_http_var('testing') == 'true') {
	// We tried to set a cookie already, let's see if it's there...
	
	$testcookie = get_cookie_var('testcookie');

	if ($testcookie != true) {
		voteerror("Your browser must be able to accept cookies before you can register a vote.");
	} else {
		// Delete the test cookie.
		setcookie ('testcookie', '');
	}
	// On with the voting...!
	
	
} else {
	// We need to check the user can accept cookies, so...
	
	// Set a cookie
	setcookie ('testcookie', 'true');

	$ret = get_http_var('ret');
	$id = get_http_var('id');
	$v = get_http_var('v');
	
	$URL = new URL($this_page);
	$URL->reset();
	$URL->insert(array(
		'v'		=> $v,
		'id'	=> $id,
		'ret' 	=> $ret,
		'testing' => 'true'
	));
	
	// Redirect to this same URL with 'testing=true' on the end.
	header("Location: " . $URL->generate('none'));
	exit;
}



function voteerror($text) {
	global $PAGE;
	
	$PAGE->page_start();
	
	$message = array (
		'title'	=> 'Sorry',
		'text'	=> $text
	);
	
	if (get_http_var('ret') != '') {
		$message['linkurl'] = get_http_var('ret');
		$message['linktext'] = 'Back to previous page';
	}

	$PAGE->message($message);
	
	$PAGE->page_end();
	exit;
}


if (is_numeric(get_http_var('id')) && is_numeric(get_http_var('v'))) {
	// We have the id of a Hansard item and a vote.
	
	$epobject_id = get_http_var('id');
	$vote = get_http_var('v');
	
	// Make sure user is allowed to vote.
	if (!$THEUSER->is_able_to('voteonhansard')) {
		voteerror("You are not allowed to rate Hansard items");
	}
	
	// Make sure the vote is a valid format.
	if ($vote != '1' && $vote != '0') {
		voteerror("That is not a valid vote.");
	}
		
	// Make sure it's a valid epobject_id.
	$q = $db->query("SELECT epobject_id FROM epobject WHERE epobject_id='" . addslashes($epobject_id) . "'");
	if ($q->rows() != 1) {
		voteerror("We need a valid epobject id.");
	}
	
	// Check the user hasn't voted on this before.

	if (!$THEUSER->isloggedin()) {
		// User isn't logged in, so try to get the user's previously
		// voted on epobjects from their cookie.
		
		$votecookie = get_cookie_var("epvotes");
		
		// $votecookie will be a string of integers (epobject_ids) separated
		// by '+' symbols.
		
		if ($votecookie != '') {
			// We're not checking the validity of the contents of $votecookie,
			// just doing it.
			$prev_epvotes = explode('+', $votecookie);
		} else {
			$prev_epvotes = array();
		}
		
		if (in_array($epobject_id, $prev_epvotes)) {
			voteerror("You have already rated this item. You can only rate something once.");
		}
		
		// Vote!
		$q = $db->query("SELECT epobject_id FROM anonvotes WHERE epobject_id = '" . addslashes($epobject_id) . "'");
		
		if ($q->rows() == 1) {
			if ($vote == 1) {
				$q = $db->query("UPDATE anonvotes SET yes_votes = yes_votes + 1 WHERE epobject_id = '" . addslashes($epobject_id) . "'");
			} else {
				$q = $db->query("UPDATE anonvotes SET no_votes = no_votes + 1 WHERE epobject_id = '" . addslashes($epobject_id) . "'");
			}
		} else {
			if ($vote == 1) {
				$q = $db->query("INSERT INTO anonvotes (epobject_id, yes_votes) VALUES ('" . addslashes($epobject_id) . "', '1')");
			} else {
				$q = $db->query("INSERT INTO anonvotes (epobject_id, no_votes) VALUES ('" . addslashes($epobject_id) . "', '1')");
			}
		}
		if (!$q->success()) {
			voteerror("Something went wrong and we couldn't register your vote");
		}
		
		// Update cookie.
		// We remove the oldest (first) epobject id, and put the new one on the end.
		// Only keep 50 in there at a time - should be enough?
		if (count($prev_epvotes) >= 50) {
			$discard = array_shift($prev_epvotes);
		}
		$prev_epvotes[] = $epobject_id;
		$new_cookie = implode('+', $prev_epvotes);

		setcookie ("epvotes", $new_cookie, time()+60*60*24*365, "/", COOKIEDOMAIN);	
		
				
	} else {
		// User is logged in.

		// See if the user's already voted for this.
		$q = $db->query("SELECT vote FROM uservotes WHERE epobject_id = '" . addslashes($epobject_id) . "' AND user_id = '" . addslashes($THEUSER->user_id()) . "'");
		if (!$q->success()) {
			voteerror("Something went wrong and we couldn't register your vote");
		}

		if ($q->rows() == 1) {
			voteerror("You have already rated this item. You can only rate something once.");			
		} else {
			// Add the vote.
			$q = $db->query("INSERT INTO uservotes (user_id, epobject_id, vote) VALUES ('" . addslashes($THEUSER->user_id()) . "', '" . addslashes($epobject_id) . "', '" . addslashes($vote) . "')");
			if (!$q->success()) {
				voteerror("Something went wrong and we couldn't register your vote");
			}
		}
	}
	
} else {
	voteerror("We weren't able to register your vote");
}

// If we've got this far, the vote's been registered!

$PAGE->page_start();

$message = array (
	'title'	=> "Thanks for your vote",
	'text'	=> ''
);

if (get_http_var('ret') != '') {
	$message['linkurl'] = get_http_var('ret');
	$message['linktext'] = 'Back to previous page';
}

$PAGE->message($message);


$PAGE->page_end();


?>
