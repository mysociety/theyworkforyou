<?php

include_once "../../includes/easyparliament/init.php";

$this_page = "addcomment";

// For previewing and adding a comment.
// We should have post args of 'body' and 'epobject_id'.



if (get_http_var("submitcomment") != '') {
	// We're submitting a comment.
			
	$data = array (
		'epobject_id' => get_http_var('epobject_id'),
		'body'	=> get_http_var('body')
	);

	$COMMENT = new COMMENT;

	$success = $COMMENT->create($data);
	
	if ($success) {
		// $success will be the last_insert_id().
		
		// Redirect user to the location of their new comment.
		
		// 'return_page' will be something like 'debate', so we know what page 
		// to return to.
		$URL = new URL(get_http_var('return_page'));
		// That c=blah we're putting on the URL does nothing on the page, 
		// BUT it makes picky browsers like Opera think it's a whole new page
		// so it reloads it, rather than being clever and thinking no refresh
		// is required.
		$URL->insert(array('id'=>get_http_var('gid'), 'c'=>$success));
				
		header("Location: http://" . DOMAIN . $URL->generate('none') . "#c" . $success);
		exit;
		
	} else {
		// Else, $COMMENT will have printed an error message.
		$PAGE->page_end();
	}
	

} else {
	// We're previewing a comment.

	$PAGE->page_start();
	
	$PAGE->stripe_start();

	if (is_numeric(get_http_var('epobject_id'))) {
		
		$body = get_http_var('body');
		$body = filter_user_input($body, 'comment'); // In init.php

	
		// Preview the comment.
		// Mock up a data array for the comment listing template.
		$data['comments'][0] = array (
			'body' => $body,
			'firstname' => $THEUSER->firstname(),
			'lastname' => $THEUSER->lastname(),
			'user_id' => $THEUSER->user_id(),
			'posted' => date('Y-m-d H:i:s', time()),
			'modflagged' => NULL,
			'visible' => 1,
			'preview' => true	// Extra tag so we know this is just a preview.
		);
	
		$COMMENTLIST = new COMMENTLIST;
		
		$COMMENTLIST->render($data, 'html');		
			
	
		// Show the populated comment form.
		
		$commendata = array (
			'epobject_id' => get_http_var('epobject_id'),
			'gid' => get_http_var('gid'),
			'body'	=> get_http_var('body'),
			'return_page' => get_http_var('return_page')
		);
		$PAGE->comment_form($commendata);
	
	
		// Show all comments for this epobject.
		$args = array (
			'epobject_id' => get_http_var('epobject_id')
		);	
	
		$COMMENTLIST->display('ep', $args);
	}
	$PAGE->stripe_end();
	$PAGE->page_end();
}





?>
