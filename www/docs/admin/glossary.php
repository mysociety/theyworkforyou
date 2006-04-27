<?php
// Some sketchy crap for displaying pending glossary additions

include_once "../../includes/easyparliament/init.php";
include_once (INCLUDESPATH."easyparliament/editqueue.php");
include_once (INCLUDESPATH."easyparliament/glossary.php");

$this_page = "admin_glossary";

$EDITQUEUE = new GLOSSEDITQUEUE();

$args = array (
	'sort' => "regexp_replace"
);

$GLOSSARY = new GLOSSARY($args);

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
elseif (get_http_var('delete_confirm')) {
	$delete_id = get_http_var('delete_confirm');
	// Delete the existing glossary entry
	$GLOSSARY->delete($delete_id);
}

$PAGE->page_start();

$PAGE->stripe_start();

// Display the results
if (isset($GLOSSARY->terms)) {

	foreach ($GLOSSARY->terms as $term) {
		$GLOSSARY->current_term = $term;
		$PAGE->glossary_display_term($GLOSSARY);
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