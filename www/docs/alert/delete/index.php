<?php

// Name: /alert/delete/index.php
// Author:  Richard Allan richard@sheffieldhallam.org.uk
// Version: 0.5 beta
// Date: 6th Jan 2005
// Description:  This file contains functions to delete an alert.

// This the page users come to when they click the link requesting that an 
// alert is deleted in the alert email itself.

// What happens? They will come here with t=23::adsf7897fd78d9sfsd200501021500
// where the value of 't' is a form of their registration token.
// This token is a salted version of their email address concatenated
// with the time the alert was created.

// We check this exists in the database and if so we run the delete
// function of class ALERT to set the field deleted in the table
// alerts to true.

// We then print a confirmation message.

// This depends on there being page definitions in metadata.php

// FUNCTIONS
// delete_success()		Displays a page with a success confirmation message
// delete_error()		Displays a page with an error message

// INITIALISATION

include_once "../../../includes/easyparliament/init.php";

// Instantiate an instance of ALERT

$ALERT = new ALERT;

$success = $ALERT->delete( get_http_var('t') );
	
if ($success) {
	delete_success();}
else {
	delete_error();
}

// FUNCTION:  delete_success

function delete_success () {

	global $PAGE, $this_page;
	
	$this_page = 'alertdeletesucceeded';
	
	$PAGE->page_start();
	
	$PAGE->stripe_start();
	
	?>
	
	<p>Your alert has been deleted.</p>
	
	<p>You will no longer receive this alert though any others you have requested will be unaffected. If you wish to delete any more
	alerts you will have to do this individually.  If you wish to set new alerts then please visit theyworkforyou again.</p>

	<p><strong>If you didn't mean to do this, <a href="/alert/undelete/?t=<?=get_http_var('t') ?>">undelete this alert</a></strong></p>


<?php

	$PAGE->stripe_end();

	$PAGE->page_end();
}


// FUNCTION:  delete_error

function delete_error() {

	// Friendly error, not a normal one!
	
	global $PAGE, $this_page;
	
	$this_page = 'alertdeletefailed';
	
	$PAGE->page_start();
	
	$PAGE->stripe_start();
	
	?>
	
	<p>The link you followed to reach this page appears to be incomplete.</p>
	
	<p>If you clicked a link in your alert email you may need to manually copy and paste the entire link to the 'Location' bar of the web browser and try again.</p>

	<p>If you still get this message, please do <a href="mailto:<?php echo CONTACTEMAIL; ?>">email us</a> and let us know, and we'll help out!</p>

<?php

	$PAGE->stripe_end();

	$PAGE->page_end();
}

?>
