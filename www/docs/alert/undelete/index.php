<?php

// This the page users come to when they undelete an alert

// What happens? They will come here with t=23-adsf7897fd78d9sfsd200501021500
// where the value of 't' is a form of their registration token.
// This token is a salted version of their email address concatenated
// with the time the alert was created.

// We check this exists in the database and if so we run the confirm
// function of class ALERT to set the field deleted in the table
// alerts to false.

// We then print a nice message.

// This depends on there being page definitions in metadata.php

// FUNCTIONS
// undelete_success()		Displays a page with a success message
// undelete_error()		Displays a page with an error message

// INITIALISATION

include_once "../../../includes/easyparliament/init.php";

// Instantiate an instance of ALERT

$ALERT = new ALERT;

$success = $ALERT->confirm( get_http_var('t') );
	
if ($success) {
	undelete_success();}
else {
	undelete_error();
}

// FUNCTION:  undelete_success

function undelete_success () {
	global $PAGE, $this_page;
	$this_page = 'alertundeletesucceeded';
	$PAGE->page_start();
	$PAGE->stripe_start();
	?>
	<p>Your alert has been undeleted.</p>
	<p>You will now receive email alerts on any day when there are entries in Hansard that match your criteria.</p>
<?php
	$PAGE->stripe_end();
	$PAGE->page_end();
}

// FUNCTION:  undelete_error

function undelete_error() {
	// Friendly error, not a normal one!
	global $PAGE, $this_page;
	$this_page = 'alertundeletefailed';
	$PAGE->page_start();
	$PAGE->stripe_start();
	?>
	<p>The link you followed to reach this page appears to be incomplete.</p>
	<p>Please do <a href="mailto:<?php echo CONTACTEMAIL; ?>">email us</a> and let us know, and we'll help out!</p>
<?php
	$PAGE->stripe_end();
	$PAGE->page_end();
}

?>
