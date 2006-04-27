<?php

// For a non-logged-in user changing their postcode.

include_once "../../../includes/easyparliament/init.php";

$this_page = "userchangepc";

if ($THEUSER->isloggedin()) {
	// They can't change their postcode here, so send them to the editing page.
	$URL = new URL('useredit');
	header("Location: http://" . DOMAIN . $URL->generate());
}

if (get_http_var('forget') == 't') {
	// The user clicked the 'Forget this postcode' link.
	$THEUSER->unset_postcode_cookie();

	// The cookie will have already been read for this page, so we need to reload.
	$URL = new URL($this_page);
	header("Location: http://" . DOMAIN . $URL->generate());
}

if (!$THEUSER->postcode_is_set()) {
	// Change it from 'Change your postcode'.
	$DATA->set_page_metadata($this_page, 'title', 'Enter your postcode');
}


$PAGE->page_start();

$PAGE->stripe_start();


$PAGE->postcode_form();


$PAGE->stripe_end();

$PAGE->page_end();

?>
