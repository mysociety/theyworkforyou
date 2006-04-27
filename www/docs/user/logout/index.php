<?php

// The logout page.

// When the logout has happened, we show a page that links to where the user was 
// when they clicked the 'Log out' link.

include_once "../../../includes/easyparliament/init.php";

$this_page = "userlogout";

$URL = new URL($this_page);
if (get_http_var("ret") != "") {
	// So we can send the user back to where they came from.
	$URL->insert(array("ret"=>get_http_var("ret")));
}
$THEUSER->logout( $URL->generate() );

$message['title'] = 'You are now logged out';

if (get_http_var("ret")) {
	$message['linkurl'] = htmlentities(get_http_var("ret"));
	$message['linktext'] = 'Back to previous page';
}

$PAGE->page_start();

$PAGE->message($message);

$PAGE->page_end();

?>