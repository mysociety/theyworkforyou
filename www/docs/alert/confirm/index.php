<?php

// Name: /alert/confirm/index.php
// Author:  Richard Allan richard@sheffieldhallam.org.uk
// Version: 0.5 beta
// Date: 6th Jan 2005
// Description:  This file contains ALERT class.

// This the page users come to when they click the link in their
// confirmation email after joining the site.

// What happens? They will come here with t=23::adsf7897fd78d9sfsd200501021500
// where the value of 't' is a form of their registration token.
// This token is a salted version of their email address concatenated
// with the time the alert was created.

// We check this exists in the database and if so we run the confirm
// function of class ALERT to set the field confirmed in the table
// alerts to true.

// We then print a nice welcome message.

// This depends on there being page definitions in metadata.php

// FUNCTIONS
// confirm_success()		Displays a page with a success confirmation message
// confirm_error()		Displays a page with an error message

// INITIALISATION

include_once "../../../includes/easyparliament/init.php";
include_once "../../../includes/easyparliament/member.php";
include_once INCLUDESPATH . '../../../phplib/crosssell.php';

// Instantiate an instance of ALERT

$ALERT = new ALERT;

$success = $ALERT->confirm( get_http_var('t') );
	
if ($success) {
	confirm_success($ALERT);
} else {
	confirm_error();
}

// FUNCTION:  confirm_success

function confirm_success ($ALERT) {
	global $PAGE, $this_page, $THEUSER;
	$this_page = 'alertconfirmsucceeded';
	$criteria = $ALERT->criteria_pretty(true);
	$email = $ALERT->email();
	$extra = null;
	$PAGE->page_start();
	$PAGE->stripe_start();
	?>
	<p>Your alert has been confirmed.</p>
	<p>You will now receive email alerts for the following criteria:</p>
	<ul><?=$criteria?></ul>
	<p>This is normally the day after, but could conceivably be later due to issues at our or parliament.uk's end.</p>
<?php
	if ($THEUSER->isloggedin()) {
		$extra = crosssell_display_advert('twfy', $email, $THEUSER->firstname() . ' ' . $THEUSER->lastname(), $THEUSER->postcode());
	} else {
		$extra = crosssell_display_advert('twfy', $email, '', '');
	}
	if ($extra == 'other-twfy-alert-type') {
		if (strstr($ALERT->criteria(), 'speaker:')) { ?>
<p>Did you know that TheyWorkForYou can also email you when a certain word or phrases is spoken in parliament? For example, it could mail you when your town is mentioned, or an issue you care about. Don't rely on the newspapers to keep you informed about your interests - find out what's happening straight from the horse's mouth.
<a href="/alert/">Sign up for an email alert</a></p>
<?		} else { ?>
<p>Did you know that TheyWorkForYou can also email you when a certain MP or Lord speaks in parliament? Don't rely on the newspapers to keep you informed about someone you're interested in - find out what's happening straight from the horse's mouth.
<a href="/alert/">Sign up for an email alert</a></p>
<?		}
	}
	$PAGE->stripe_end();
	$PAGE->page_end($extra);
}

// FUNCTION:  confirm_error

function confirm_error() {
	// Friendly error, not a normal one!
	global $PAGE, $this_page;
	$this_page = 'alertconfirmfailed';
	$PAGE->page_start();
	$PAGE->stripe_start();
	?>
	<p>The link you followed to reach this page appears to be incomplete.</p>
	<p>If you clicked a link in your confirmation email you may need to manually copy and paste the entire link to the 'Location' bar of the web browser and try again.</p>
	<p>If you still get this message, please do <a href="mailto:<?php echo CONTACTEMAIL; ?>">email us</a> and let us know, and we'll help out!</p>
<?php
	$PAGE->stripe_end();
	$PAGE->page_end();
}

?>
