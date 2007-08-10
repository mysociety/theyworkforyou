<?php
// Some sketchy crap for displaying pending glossary additions

include_once "../../includes/easyparliament/init.php";
include_once (INCLUDESPATH."easyparliament/editqueue.php");
include_once (INCLUDESPATH."easyparliament/glossary.php");

$this_page = "admin_glossary_pending";

$EDITQUEUE = new GLOSSEDITQUEUE();

$args = array (
	'sort' => "regexp_replace"
);
$GLOSSARY = new GLOSSARY($args);

// This will build a list of pending requests for everything by default.
$EDITQUEUE->get_pending();

// If we're coming back here from a recent action we will have
// an http POST var of 'approve' or 'decline'.
// 'approve' can be an array or a single value depending on whether or not it was a form submission.
// 'decline' will always be a single value.
if (get_http_var('approve')) {
	$approve = get_http_var('approve');
	if (!is_array($approve)) {
		$approve = array ( $approve );
	}
	// Add all approved items 
	$data = array (
		'approvals' => $approve,
		'epobject_type' => 2
	);
	$EDITQUEUE->approve($data);
}
elseif (get_http_var('decline')) {
	$decline = array (get_http_var('decline'));
	// Dump all declined items 
	$data = array (
		'declines' => $decline,
		'epobject_type' => 2
	);
	$EDITQUEUE->decline($data);
}

$PAGE->page_start();

$PAGE->stripe_start();


// If we're modifying something, show a form to do so with (unless we've just submitted it!)
if (get_http_var('modify') && (!get_http_var('submitterm'))) {

	$glossary_id = get_http_var('modify');	

	// Mock up a "current term" to send to the display function
	if (get_http_var('previewterm')) {
		$body	= get_http_var('definition');
		$title	= get_http_var('g');
	}
	else {
		$body = $EDITQUEUE->pending[$glossary_id]['body'];
		$title = $EDITQUEUE->pending[$glossary_id]['title'];
	}
	if (get_http_var('wikiguess')) {
		$checked = " checked";
	}
	else {
		$checked = "";
	}

	$user_id = $EDITQUEUE->pending[$glossary_id]['user_id'];
	
	$GLOSSARY->current_term['body'] = filter_user_input($body, 'comment'); // In init.php
	$GLOSSARY->current_term['title'] = filter_user_input($title, 'comment'); // In init.php

	// They'll be needing to edit it...
	$args['action'] = "admin_glossary";
	$args['glossary_id'] = $glossary_id;
	
	$URL = new URL('admin_glossary_pending');
	$form_action = $URL->generate('url');
	
	?>
	
	<div class="glossaryaddbox">
		<form action="<? echo $form_action; ?>" method="post">
		<input type="hidden" name="modify" value="<?php echo $glossary_id; ?>">
		<input type="hidden" name="userid" value="<?php echo $user_id; ?>">
		<input type="text" name="g" value="<?php echo $title; ?>" size="80">
		<label for="definition"><p><textarea name="definition" id="definition" rows="10" cols="40"><?php echo htmlentities($body); ?></textarea></p>

	<?
	
	// Wiki woo!
	// We need to work out how best to work this...
	$wiki_link = "http://en.wikipedia.org/wiki/" . strtr($title, " ", "_");
?>
		<p>Guessing the wikipedia link - give it a go:<br>
		<a href="<?php echo $wiki_link; ?>" target="_blank"><?php echo $wiki_link; ?></a></p>
		<p>Tick here if it worked <input type="checkbox" name="wikiguess"$checked></p>
		<p><input type="submit" name="previewterm" value="Preview" class="submit">
		<input type="submit" name="submitterm" value="Post" class="submit"></p></label>
	</div>

<?
	
	// Off it goes...
	print "<p>This is what it was going to look like:</p>";
	print "<h3>$title</h3>";
		
	$PAGE->glossary_display_term($GLOSSARY);

}
else {
	
	// add a modification to the database
	if (get_http_var('submitterm') && get_http_var('modify')) {
		$data = array (
			'user_id'	=> get_http_var('userid'),
			'title'		=> get_http_var('g'),
			'body'		=> get_http_var('definition')
		);
		vardump($data);
		// $success = $EDITQUEUE->modify($data);
	}

	if ($EDITQUEUE->pending_count) {
		print "<h3>" . $EDITQUEUE->pending_count . " Pending Glossary terms</h3>";
		$EDITQUEUE->display("pending");
	}
	else {
		print "<h3>Nothing pending, tap your fingers awhile.</h3>";
	}
}

// Now that's easy :)
// Even easier when you copy it :p

$menu = $PAGE->admin_menu();

$PAGE->stripe_end(array(
	array(
		'type'		=> 'html',
		'content'	=> $menu
	)
));

$PAGE->page_end();

?>
