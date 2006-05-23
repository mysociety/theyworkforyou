<?php

// This is the main file allowing users to manage email alerts.
// It is based on the file /user/index.php.
// The alerts depend on the class ALERT which is established in /includes/easyparliament/alert.php
// .

/* What happens?

There is only one function here which is to add an alert.

Alerts are deleted through a confirmation token similar to that used to add alerts.

A link at the bottom of the page will send you a list of all your alerts with links to delete them if you wish.
	
FUNCTIONS		
check_input()	Validates the edited or added alert data and creates error messages.
add_alert()	Adds alert to database depending on success.
display_form()	Shows the form to enter alert data.
set_criteria()	Sets search criteria from information in MP and Keyword fields.
*/
		
include_once "../../includes/easyparliament/init.php";
include_once "../../includes/easyparliament/people.php";
include_once "../../includes/easyparliament/member.php";
include_once INCLUDESPATH . '../../../phplib/auth.php';

$this_page = "alert";

$args = array( 'action' => $this_page);

// Put all the user-submitted data in an array.
$details = array();
if ($THEUSER->loggedin()) {
	$details['email'] = $THEUSER->email();
} else {
	$details["email"] = trim(get_http_var("email"));
}
$details['keyword'] = trim(get_http_var("keyword"));
$details['pid'] = trim(get_http_var("pid"));
if ($details['pid'] == 'Any') $details['pid'] = '';

// Check the input.
// If there are any errors with the submission, $errors (an array)
// will have elements. The keys will be the name of form elements,
// and the values will be text to display when we show the form again.
$errors = check_input($details);

if (!sizeof($errors) && ( (get_http_var('submitted') && ($details['keyword'] || $details['pid']))
                       || (get_http_var('only') && ($details['keyword'] || $details['pid']))
		       || ($details['keyword'] && $details['pid']))) {
	add_alert( $details );
} else {
	$PAGE->page_start();
	$PAGE->stripe_start();
	$PAGE->block_start(array ('id'=>'alerts', 'title'=>'Request a TheyWorkForYou.com Email Alert'));
	display_form($details, $errors);
	$PAGE->block_end();	
	$end = array();
	if (!$details['pid']) {
		$end[] = array('type' => 'include', 'content' => 'search');
	}
	$PAGE->stripe_end($end);
	$PAGE->page_end(); 
}


function check_input ($details) {
	
	global $ALERT, $this_page;
	
	$errors = array();

	// Check each of the things the user has input.
	// If there is a problem with any of them, set an entry in the $errors array.
	// This will then be used to (a) indicate there were errors and (b) display
	// error messages when we show the form again.
	
	// Check email address is valid and unique.
	if ($details["email"] == "") {
		$errors["email"] = "Please enter your email address";
	
	} elseif (!validate_email($details["email"])) {
		// validate_email() is in includes/utilities.php
		$errors["email"] = "Please enter a valid email address";
	
	} 
	
	if ($details['pid'] != 'Any' && !ctype_digit($details['pid']))
		$errors['pid'] = 'Please choose a valid person';
#	if (!$details['keyword'])
#		$errors['keyword'] = 'Please enter a search term';

	if (get_http_var('submitted') && !$details['pid'] && !$details['keyword'])
		$errors['keyword'] = 'Please choose a person and/or enter a keyword';
	// Send the array of any errors back...
	return $errors;
}


function add_alert ($details) {

	global $ALERT, $PAGE, $THEUSER, $this_page;

	$extra = null;

	// Instantiate an instance of ALERT
	$ALERT = new ALERT;

	$external_auth = auth_verify_with_shared_secret($details['email'], OPTION_AUTH_SHARED_SECRET, get_http_var('sign'));
	if ($external_auth) {
		$extra = 'from_hfymp=1';
		$confirm = false;
	} elseif ($THEUSER->loggedin()) {
		$confirm = false;
	} else {
		$confirm = true;
	}

	// If this goes well, the alert will be added to the database and a confirmation email 
	// will be sent to them.
	$success = $ALERT->add ( $details, $confirm );
	
	// Display results message on blank page for both success and failure
	
	$this_page = 'alertwelcome';
	$URL = new URL('alertwelcome');
	$backlink = $URL->generate();
	$PAGE->page_start();
	$PAGE->stripe_start();
	
	if ($success>0 && !$confirm) {
		if ($details['pid']) {
			$MEMBER = new MEMBER(array('person_id'=>$details['pid']));
			$criteria = $MEMBER->full_name();
			if ($details['keyword']) {
				$criteria .= ' says \'' . $details['keyword'] . '\'';
			} else {
				$criteria .= ' speaks';
			}
		} elseif ($details['keyword']) {
			$criteria = '\'' . $details['keyword'] . '\' is spoken';
		}
		$message = array(
			'title' => 'Your alert has been added',
			'text' => 'You will now receive email alerts on any day when ' . $criteria . ' in Hansard.'
		);
	} elseif ($success>0) {
		$message = array(
			'title' => "We're nearly done...",
			'text' => "You should receive an email shortly which will contain a link. You will need to follow that link to confirm your email address to receive the alert. Thanks."
		);
	} elseif ($success == -2) {
		$message = array('title' => 'You already have this alert',
		'text' => 'You already appear to be subscribed to this email alert, so we have not signed you up to it again.'
		);
	} else {
		$message = array ('title' => "This alert has not been accepted",
		'text' => "Sorry, we were unable to create this alert. Please <a href=\"mailto:". CONTACTEMAIL . "\">let us know</a>. Thanks."
		);
	}
	$PAGE->message($message);
	$PAGE->stripe_end();
	$PAGE->page_end($extra);
}

/*  This function creates the form for displaying an alert, prompts the user for input and creates
    the alert when submitted.
*/

function display_form ( $details = array(), $errors = array() ) {
	global $this_page, $ALERT, $PAGE, $THEUSER;
	$ACTIONURL = new URL($this_page);
	$ACTIONURL->reset();
?>

<p>This page allows you to request an email alert from TheyWorkForYou.com.</p>

<ul>
<li>To receive an alert <strong>every time a particular MP or Peer appears</strong>,
select their name from the drop-down list of MPs and Peers, and
leave the word/phrase box blank.</li>

<li>To receive an alert <strong>every time a particular keyword or phrase appears</strong>,
select "Any MP/Peer" from the drop-down list of MPs and Peers, and enter your search term in
the box underneath.  The results are selected using the same rules as for a
normal search (see the box to the right for help on setting your criteria).</li>

<li>You can also <strong>combine</strong> both types of criteria to be alerted
<strong>only</strong> when a particular MP or Peer uses the keywords you have defined.
To do this, select the MP or Peer from the drop-down list <em>and</em> enter the keyword(s) as
above.</li>
</ul>

<p>Please note that you should only enter one topic per alert - if you wish to receive alerts on more than one topic, or for more than one MP or Peer, simply fill in this form as many times as you need.</p>

	<form method="post" action="<?php echo $ACTIONURL->generate(); ?>">
	
	<?php	if (!$THEUSER->loggedin()) {
			if (isset($errors["email"]) && (get_http_var('submitted') || get_http_var('only'))) {
				$PAGE->error_message($errors["email"]);
			}
	?>
				<div class="row">
				<span class="label"><label for="email">Your email address:</label></span>
				<span class="formw"><input type="text" name="email" id="email" value="<?php if (isset($details["email"])) { echo htmlentities($details["email"]); } ?>" maxlength="255" size="30" class="form" /></span>
				</div>
	<?php	}
		if (!get_http_var('only') || $details['pid']) {
			if (isset($errors['pid'])) {
				$PAGE->error_message($errors['pid']);
			}
	?>
				<div class="row">
				<span class="label"><label for="pid">MP or Peer you wish to receive alerts for:</label></span>
				<span class="formw"><?
				if (get_http_var('only') && $details['pid']) {
					$MEMBER = new MEMBER(array('person_id'=>$details['pid']));
					print $MEMBER->full_name();
					print '<input type="hidden" name="pid" value="' . htmlspecialchars($details['pid']) . '">';
				} else { ?><select name="pid">
				<option value="Any">Any MP/Peer</option>
				<?php 
				// Get a list of MPs/Peers for displaying in the form using the PEOPLE class
				$LIST = new PEOPLE;
				$args['order'] = 'last_name';
				if ($details['pid']) $args['pid'] = $details['pid'];
				$LIST->listoptions($args);
				?>
				</select>
			<? } ?>
				</span>
				</div>
	<?php	}
		if (!get_http_var('only') || $details['keyword']) {
			if (isset($errors["keyword"])) {
				$PAGE->error_message($errors["keyword"]);
			}
	?>
				<div class="row"> 
				<span class="label"><label for="keyword">Word or phrase you wish to receive alerts for:</label></span>
				<span class="formw"><input type="text" name="keyword" id="keyword" value="<?php if ($details['keyword']) { echo htmlentities($details['keyword']); } ?>" maxlength="255" size="30" class="form" /></span>
				</div>
	<?php	}
		$submittext = "Request Email Alert";
	?>
						
				<div class="row">
				<span class="label">&nbsp;</span>
				<span class="formw"><input type="submit" class="submit" value="<?php echo $submittext; ?>" /><!-- this space makes the submit button appear on Mac IE 5! --> </span>
				</div>
	<?php	if (!$THEUSER->loggedin()) { ?>
				<div class="row">
				If you join or log in, you won't need to confirm your email address for every alert you set.
				</div>
	<?php	} ?>
				<input type="hidden" name="submitted" value="true" />
				<input type="hidden" name="pg" value="alert" />

	</form>
	
	<?php
} 

?>

