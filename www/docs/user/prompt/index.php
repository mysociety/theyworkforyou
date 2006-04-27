<?php

// The user comes to this page after clicking an 'add a comment' link
// in hansard, if they aren't logged in.

// This page will expect a 'ret' value with the URL of the page
// the user should return to after logging in.

// And a 'type' value of 1 to indicate the user was going to enter a comment.
// (Use other types for, say, glossary entries.)


$this_page = "userprompt";

include_once "../../../includes/easyparliament/init.php";


$type = get_http_var('type');
$returl = get_http_var('ret');

if ($type == 2) {
	// Glossary.
	$message = "Sorry, you must be logged in to add a glossary item.";
	$message2 = "You'll be able to add your glossary item straight after.";
	
	$URL = new URL('glossary_addterm');
	$URL->insert(array('g' => get_http_var('g')));
	$glossary_returl = $URL->generate();
	$anchor = '';
	
} else {
	// Comment.
	$message = "Sorry, you must be logged in to post a comment.";
	$message2 = "You'll be able to post your comment straight after.";
	$anchor = '#addcomment';
}

$URL = new URL('userjoin');
$URL->insert(array('ret'=>$returl.$anchor));
$joinurl = $URL->generate();


// GET THAT PAGE STARTED!

$PAGE->page_start();

$PAGE->stripe_start();

?>

<p><strong><?php echo $message; ?></strong></p>

<p>If you're not yet a member, then <a href="<?php echo $joinurl; ?>"><strong>join now</strong></a>.</p>

<p>Otherwise, please log in... <?php echo $message2; ?></p>

<?php

$PAGE->login_form();


$PAGE->stripe_end();

$PAGE->page_end();

?>
