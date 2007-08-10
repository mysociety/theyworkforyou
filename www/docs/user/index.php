<?php

// We use this same text file for editing a user's details (both THEUSER's and
// a different user's), and letting a new user join.
// This means we can keep all the form validation and display code in one place.

/* What happens?

	1) We check what $this_page is going to be. This depends on:
		* the value of $pg, if anything;
		* whether $THEUSER is logged in or not;
		* whether $THEUSER has appropriate security privileges;
		* and whether we've been passed the ID of a user to view/edit.
	
	2) If we've come to this page after submitting its form, we check the form data.
		If the data is OK, we either edit or add the user and display the new info.
		If there were errors, the same form is displayed again with error messages.
	
	3) On the other hand, if the form hasn't been submitted we've just arrived here.
		In that case the form (or the user info, if just viewing) is displayed.
		
		
	After the first part of working out which page we're on, and munging any data
		various functions in this file are used:
		
		check_input()	Validates the edited or added user data and creates error messages.
		add_user()		Calls $THEUSER->add() and displays the results, depending on success.
		update_user()	Calls the appropriate functions and updates, displays results.
		display_form()	Displays the form for editing or adding a user.
		display_user()	Displays a user's details.

*/
		


include_once "../../includes/easyparliament/init.php";
include_once "../../includes/easyparliament/member.php";

// Which page we're on all depends on the value of the "pg" variable...
switch (get_http_var("pg")) {

	case "join":	// A new user signing up.
		
		$this_page = "userjoin";
		break;
	
	case "editother":	// Editing someone else's info.

		// We need a user_id. So make sure that exists. 
		// And make sure the user is allowed to do this!
		
		if (is_numeric( get_http_var("u") ) && $THEUSER->is_able_to("edituser")) {

			$this_page = "otheruseredit";
	
		} else {
			// Revert to editing THEUSER's own info.
			$this_page = "useredit";

		}
		
		break;	
	
	case "edit":	// Edit this user's owninfo.
		
		if ($THEUSER->isloggedin()) {
			$this_page = "useredit";	
		
		} else {
			// Unlikely to get to this page without being logged in,
			// but just in case, show them the blank form.
			$this_page = "userjoin";
		
		}
		break;
		
	default:
	
		if ($THEUSER->isloggedin() && 
			(get_http_var('u') == '' || get_http_var('u') == $THEUSER->user_id())
			) {
			// Logged in user viewing their own details.
			$this_page = 'userviewself';
		} else {
			// Viewing someone else's details.
			$this_page = "userview";
		}
}


// A little detail... we want to change text in the page depending on whose 
// info is being changed or added.
$who = $this_page == "otheruseredit" ? "the user's" : "your";


if (get_http_var("submitted") == "true") {
	// The edit or join form has been submitted, so check input.

	// Put all the user-submitted data in an array.
	$details = array();
	$details["firstname"]		= trim(get_http_var("firstname"));
	$details["lastname"]		= trim(get_http_var("lastname"));
	$details["email"]			= trim(get_http_var("email"));
	// We use boolean true/false internally. Convert the text from the form to boolean.
	$details["emailpublic"]		= get_http_var("emailpublic") == "true" ? true : false;
	$details["password"]		= trim(get_http_var("password"));
	$details["password2"]		= trim(get_http_var("password2"));
	$details["optin"] = get_http_var("optin") == "true" ? true : false;
	$details['mp_alert'] = get_http_var('mp_alert') == 'true' ? true : false;
	if (get_http_var("remember") != "") {
		$remember				= get_http_var("remember");
		$details["remember"] = $remember[0] == "true" ? true : false;
	}

	$details["postcode"]		= trim(get_http_var("postcode"));
	$details["url"]				= trim(get_http_var("url"));
	if ($details['url'] != '' && !preg_match('/^http/', $details['url'])) {
		$details['url'] = 'http://' . $details['url'];
	}
	
	if ($this_page == "otheruseredit") {
		$details["user_id"]			= trim(get_http_var("u"));
		$details["status"]			= trim(get_http_var("status"));
		if (get_http_var("deleted") != "") {
			$deleted				= get_http_var("deleted");
			$details["deleted"] = $deleted[0] == "true" ? true : false;
		} else {
			$details['deleted'] = false;
		}
		if (get_http_var("confirmed") != "") {
			$confirmed				= get_http_var("confirmed");
			$details["confirmed"] = $confirmed[0] == "true" ? true : false;
		} else {
			$details['confirmed'] = false;
		}
	}
	
	//$details['status'] = $THEUSER->status();

	// Check the input.
	// If there are any errors with the submission, $errors (an array)
	// will have elements. The keys will be the name of form elements,
	// and the values will be text to display when we show the form again.
	$errors = check_input($details);

	if (sizeof($errors) > 0) {
		// Validation errors. Print form again.
		
		$PAGE->page_start();
		
		display_form($details, $errors);
		
		$PAGE->page_end(); 
		
	} elseif ($this_page == "userjoin") {
		// No errors so far, so try to sign up and log in.
		
		add_user( $details );

	} else {
		// No errors so far, editing an existing user, 
		// $this_page == "useredit" or "otheruseredit".
	
		update_user( $details );

	}
	
	

} else {
	// THEUSER has just arrived at this page, no form submitted.

	
	if ($this_page == "userview" || $this_page == 'userviewself') {

		display_user ();
	
	} else {
			

		$PAGE->page_start();
			
		if ($this_page == "useredit") {
			
			// We're editing THEUSER's own info, so set all the vars.
			$details = array();			
			$details["firstname"]		= $THEUSER->firstname();
			$details["lastname"]		= $THEUSER->lastname();
			$details["email"]			= $THEUSER->email();
			$details["emailpublic"]		= $THEUSER->emailpublic();
			$details["password"]		= $THEUSER->password();
			$details["optin"]			= $THEUSER->optin();	
			$details["postcode"]		= $THEUSER->postcode();
			$details["url"]				= $THEUSER->url();
			$details["status"]			= $THEUSER->status();
				
			// display the form with this user's info.
			display_form ($details);
			
		} elseif ($this_page == "otheruseredit") {
	
			// We're editing the info of a different user.
			// So set up a new user object with the id supplied
			// and get the user's info.
			
			$USER = new USER;
			$USER->init( get_http_var("u") );
			
			$details = array();
			
			$details["user_id"]			= $USER->user_id();
			$details["firstname"]		= $USER->firstname();
			$details["lastname"]		= $USER->lastname();
			$details["email"]			= $USER->email();
			$details["emailpublic"]		= $USER->emailpublic();
			$details["password"]		= $USER->password();
			$details["optin"]			= $USER->optin();
			$details["postcode"]		= $USER->postcode();
			$details["url"]				= $USER->url();
			$details["status"]			= $USER->status();
			$details["deleted"]			= $USER->deleted();
			$details["confirmed"]		= $USER->confirmed();
			
			// Display the form with the other user's info.
			display_form ($details);
		
		} else {
			
			// $this_page == "userjoin".
			// Display a blank form.
			display_form ();
			
		}
		
		
		$PAGE->page_end();
	}
	
}



function check_input ($details) {
	global $THEUSER, $this_page, $who;
	
	// This may be a URL that will send the user back to where they were before they
	// wanted to join.
	$ret = get_http_var("ret");

	$errors = array();

	// Check each of the things the user has input.
	// If there is a problem with any of them, set an entry in the $errors array.
	// This will then be used to (a) indicate there were errors and (b) display
	// error messages when we show the form again.
	
	// Check first name.
	if ($details["firstname"] == "") {
		$errors["firstname"] = "Please enter $who first name";
	}
		
	// They don't need a last name. In case Madonna joins.

	// Check email address is valid and unique.
	if ($details["email"] == "") {
		$errors["email"] = "Please enter $who email address";
	
	} elseif (!validate_email($details["email"])) {
		// validate_email() is in includes/utilities.php
		$errors["email"] = "Please enter a valid email address";
	
	} else {

		$USER = new USER;
		$id_of_user_with_this_addresss = $USER->email_exists($details["email"]);
				
		if ($this_page == "useredit" && 
			get_http_var("u") == "" && 
			$THEUSER->isloggedin()) {
			// User is updating their own info.
			// Check no one else has this email.

			if ($id_of_user_with_this_addresss && 
				$id_of_user_with_this_addresss != $THEUSER->user_id()) {
				$errors["email"] = "Someone else has already joined with this email address";
			}
			
		} else {
			// User is joining. Check no one is already here with this email.
	 		if ($this_page == "userjoin" && $id_of_user_with_this_addresss) {
				$errors["email"] = "There is already a user with this email address";
			}
		}
	}
	
	
	// Check passwords.
	if ($this_page == "userjoin") {
		
		// Only *must* enter a password if they're joining.
		if ($details["password"] == "") {
			$errors["password"] = "Please enter $who password";
		
		} elseif (strlen($details["password"]) < 6) {
			$errors["password"] = "Please enter at least six characters";
		}
	
		if ($details["password2"] == "") {
			$errors["password2"] = "Please enter $who password again";
		}
		
		if ($details["password"] != "" && $details["password2"] != "" && $details["password"] != $details["password2"]) {
			$errors["password"] = ucfirst($who) . " passwords did not match. Please try again.";
		}
		
	} else {
	
		// Update details pages.
		
		if ($details["password"] != "" && strlen($details["password"]) < 6) {
			$errors["password"] = "Please enter at least six characters";
		}
		
		if ($details["password"] != $details["password2"]) {
			$errors["password"] = ucfirst($who) . " passwords did not match. Please try again.";
		}
	}
	
	// Check postcode (which is not a compulsory field).
	if ($details["postcode"] != "" && !validate_postcode($details["postcode"])) {
		$errors["postcode"] = "Sorry, this isn't a valid UK postcode.";
	}

	// No checking of URL.
	
	
	if ($this_page == "otheruseredit") {
		
		// We're editing another user's info.
		
		// Could check status here...?

		
	}
	
	// Send the array of any errors back...
	return $errors;
}



function add_user (	$details) {
	global $THEUSER, $PAGE, $this_page;


		
	// If this goes well, the user will have their data
	// added to the database and a confirmation email 
	// will be sent to them.
	$success = $THEUSER->add ( $details );


	if ($success) {
		// No validation errors.
		
		$this_page = 'userwelcome';
		
		$PAGE->page_start();
		
		$PAGE->stripe_start();
		
		$message = array(
			'title' => "We're nearly done...",
			'text' => "You should receive an email shortly which will contain a link. You will need to follow that link to confirm your email address before you can log in. Thanks."
		);
		
		$PAGE->message($message);

		$PAGE->stripe_end();

/*		We used to log the user in straight away.
		Now we send them a confirmation email. 
		
		Keeping this code here, just in case.
		Note that you'll probably have to add the 'remember' checkbox
		back into the sign-up form.


		// Does this user want to a long-term cookie?
		if (isset($details["remember"]) && $details["remember"]) {
			$expire = "never";
		} else {
			$expire ="session";
		}
		
		// Wherever the user ends up next, we'll display a welcome message to them
		// indicated by the 'newuser' element in the URL....		
		
		// $ret might be a URL that will take the user back to where they 
		// where before joining.
		if (get_http_var("ret") != "") {
			
			$url = get_http_var("ret");
			// We're now going to have to fudge things a bit. Want to add "newuser=1"
			// on to the end of wherever we were before, so a welcome message will
			// be displayed.
			if (preg_match("/\?.+/", $url)) {
				$url .= "&newuser=1";
			} else {
				$url .= "?newuser=1";
			}

		} else {
			// We'll send the user to the front page after they've joined.
			
			$URL = new URL("home");
			$URL->insert(array("newuser"=>"1"));
			$url = $URL->generate();
		}			

		// Log the new user in. They'll be sent off elsewhere, so we don't output any 
		// HTML here.
		$THEUSER->login($url, $expire);			

*/

	} else {
		
		// Something went wrong, so display the form (with error messages).
		
		$this_page = 'userjoin';
		
		$PAGE->page_start();
		
		$errors["db"] = "Sorry, we were unable to create an account for you. Please <a href=\"mailto:". CONTACTEMAIL . "\">let us know</a>. Thanks.";

		display_form( $details, $errors);
		
	}
	
	$PAGE->page_end();
}




function update_user ( $details ) {
	global $THEUSER, $this_page, $PAGE, $who;

	// There were no errors when the edit user form was submitted,
	// so make the changes in the DB.

	// Who are we updating? $THEUSER or someone else?
	if ($this_page == "otheruseredit") {
		// Someone else.
		
		$success = $THEUSER->update_other_user ( $details );
		
		// For displaying the altered info.
		$user_id = $details["user_id"];
	
	} else {
		// $this_page == "useredit"	
		
		$success = $THEUSER->update_self ( $details );	

		// For displaying the altered info.
		$user_id = $THEUSER->user_id;
	}
	

	if ($success) {
		// No errors, all updated, show results.
		
		if ($this_page == 'otheruseredit') {
			$this_page = "userview";
		} else {
			$this_page = "userviewself";
		}

		display_user( $user_id );

		
	} else {
		// Something went wrong.
		
		$PAGE->page_start();
		
		$errors["db"] = "Sorry, we were unable to update $who details. Please <a href=\"mailto:" . CONTACTEMAIL . "\">let us know</a> what you were trying to change. Thanks.";
		
		display_form($details, $errors);
		
		$PAGE->page_end();	
	}	
	

		
}




function display_form ( $details = array(), $errors = array() ) {
	global $this_page, $THEUSER, $who, $PAGE;
	
	$PAGE->stripe_start();
	
	if (isset($errors["db"])) {
		
		$PAGE->error_message($errors["db"]);
	
	} else {
		
		$URL = new URL("userlogin");
		
		if (!$THEUSER->isloggedin()) {
			?>
				<p>Already joined? <a href="<?php echo $URL->generate(); ?>">Then log in!</a></p>
<?php
		}
	}
	
	$ACTIONURL = new URL($this_page);
	$ACTIONURL->reset();
	?>

				<form method="post" action="<?php echo $ACTIONURL->generate(); ?>">
<?php
	if ($this_page == "otheruseredit") {
		?>
				<div class="row">
				<span class="label">User ID:</span>
				<span class="formw"><?php echo htmlentities($details["user_id"]); ?></span>
				</div>

<?php
	}

	if ($this_page == 'useredit' && isset($details['status'])) {
		?>
				<div class="row">
				<span class="label">Status:</span>
				<span class="formw"><?php echo htmlentities($details['status']); ?></span>
				</div>
<?php
	}
	
	if (isset($errors["firstname"])) {
		$PAGE->error_message($errors["firstname"]);
	}
?>
				<div class="row">
				<span class="label"><label for="firstname">Your first name:</label></span>
				<span class="formw"><input type="text" name="firstname" id="firstname" value="<?php if (isset($details["firstname"])) { echo htmlentities($details["firstname"]); } ?>" maxlength="255" size="30" class="form"></span>
				</div>

<?php
	if (isset($errors["lastname"])) {
		$PAGE->error_message($errors["lastname"]);
	}
?>
				<div class="row">
				<span class="label"><label for="lastname">Your last name:</label></span>
				<span class="formw"><input type="text" name="lastname" id="lastname" value="<?php if (isset($details["lastname"])) { echo htmlentities($details["lastname"]); } ?>" maxlength="255" size="30" class="form"></span>
				</div>

<?php
	if (isset($errors["email"])) {
		$PAGE->error_message($errors["email"]);
	}
?>
				<div class="row">
				<span class="label"><label for="email">Email address:</label></span>
				<span class="formw"><input type="text" name="email" id="email" value="<?php if (isset($details["email"])) { echo htmlentities($details["email"]); } ?>" maxlength="255" size="30" class="form"></span>
				</div>

<?php

	if ($this_page == "useredit" || $this_page == "otheruseredit") {
		// If not, the user's joining.
		?>
				<div class="row">
				&nbsp;<br><small>To change <?php echo $who; ?> password enter a new one twice below (otherwise, leave blank).</small>
				</div>
<?php
	}


	if (isset($errors["password"])) {
		$PAGE->error_message($errors["password"]);
	}
?>
				<div class="row">
				<span class="label"><label for="password">Password:</label></span>
				<span class="formw"><input type="password" name="password" id="password" value="" maxlength="30" size="20" class="form"> <small>At least six characters</small></span>
				</div>

<?php
	if (isset($errors["password2"])) {
		$PAGE->error_message($errors["password2"]);
	}
?>
				<div class="row">
				<span class="label"><label for="password2">Repeat password:</label></span>
				<span class="formw"><input type="password" name="password2" id="password2" value="" maxlength="30" size="20" class="form"></span>
				</div>


				<br style="clear: left;">&nbsp;<br>
<?php
	if (isset($errors["postcode"])) {
		$PAGE->error_message($errors["postcode"]);
	}
?>
				<div class="row">
				<span class="label"><label for="postcode">Your UK postcode:</label></span>
				<span class="formw"><input type="postcode" name="postcode" id="postcode" value="<?php if (isset($details["postcode"])) { echo htmlentities($details["postcode"]); } ?>" maxlength="10" size="10" class="form"> <small>Optional and not public</small></span>
				</div>

<?php
	if (isset($errors["url"])) {
		$PAGE->error_message($errors["url"]);
	}
?>
				<div class="row">
				<span class="label"><label for="url">Your website:</label></span>
				<span class="formw"><input type="url" name="url" id="url" value="<?php if (isset($details['url'])) { echo htmlentities($details['url']); } ?>" maxlength="255" size="20" class="form"> <small>Optional and public</small></span>
				</div>


				
				<div class="row">
				&nbsp;<br>Let other users see <?php echo $who; ?> email address?
				</div>

<?php
	if (isset($errors["emailpublic"])) {
		$PAGE->error_message($errors["emailpublic"]);
	}

?>
				<div class="row">
				<span class="label">&nbsp;</span>
				<span class="formw"><input type="radio" name="emailpublic" id="emailpublictrue" value="true"<?php
	if (isset($details["emailpublic"]) && $details["emailpublic"] == true) {
		print " checked";
	}	
	?>> <label for="emailpublictrue">Yes</label><br>
					<input type="radio" name="emailpublic" id="emailpublicfalse" value="false"<?php
	if (($this_page == "userjoin" && get_http_var("submitted") != "true") 
		|| 
		(isset($details["emailpublic"]) && $details["emailpublic"] == false)
		) {
		print " checked";
	}
	?>> <label for="emailpublicfalse">No</label></span>
				</div>
				
				
				
				<div class="row">
				&nbsp;<br>Do <?php if ($this_page == "otheruseredit") { echo "they"; } else { echo "you"; } ?> wish to receive occasional update emails about TheyWorkForYou.com?
				</div>

<?php
	if (isset($errors["optin"])) {
		$PAGE->error_message($errors["optin"]);
	}
?>
				<div class="row">
				<span class="label">&nbsp;</span>
				<span class="formw"><input type="radio" name="optin" id="optintrue" value="true"<?php
	if (isset($details["optin"]) && $details["optin"] == true) {
		print " checked";
	}
	?>> <label for="optintrue">Yes</label><br>
				<input type="radio" name="optin" id="optinfalse" value="false"<?php
	if (($this_page == "userjoin" && get_http_var("submitted") != "true")
		||
		(isset($details["optin"]) && $details["optin"] == false)
		) {
		print " checked";
	}
	?>> <label for="optinfalse">No</label></span>
				</div>

<?php	if ($this_page == 'userjoin') { ?>
				<div class="row">
				&nbsp;<br>Would <?php if ($this_page == "otheruseredit") { echo "they"; } else { echo "you"; } ?> like to receive an email whenever your MP does something in Parliament?
				</div>

				<div class="row">
				<span class="label">&nbsp;</span>
				<span class="formw"><input type="radio" name="mp_alert" id="mp_alerttrue" value="true"<?php
	if (isset($details["mp_alert"]) && $details["mp_alert"] == true) {
		print ' checked';
	}
	?>> <label for="mp_alerttrue">Yes</label><br>
				<input type="radio" name="mp_alert" id="mp_alertfalse" value="false"<?php
	if (($this_page == "userjoin" && get_http_var("submitted") != "true")
		||
		(isset($details["mp_alert"]) && $details["mp_alert"] == false)
		) {
		print ' checked';
	}
	?>> <label for="mp_alertfalse">No</label></span>
				</div>
<?php	}

	if ($this_page == "otheruseredit") {

		if (isset($errors["status"])) {
			$PAGE->error_message($errors["status"]);
		}
		?>
				<div class="row">
				<span class="label">Security status:</span>
				<span class="formw"><select name="status">
<?php
	$USER = new USER;
	$statuses = $USER->possible_statuses();
	foreach ($statuses as $n => $status) {
		print "\t<option value=\"$status\"";
		if ($status == $details["status"]) {
			print " checked";
		}
		print ">$status</option>\n";
	}
?>
				</select></span>
				</div>

				<div class="row">
				<span class="label"><label for="confirmed">Confirmed?</label></span>
				<span class="formw"><input type="checkbox" name="confirmed[]" id="confirmed" value="true"<?php
	if (isset($details["confirmed"]) && $details["confirmed"] == true) {
		print " checked";
	}
	?>></span>
				</div>
				
				<div class="row">
				<span class="label"><label for="deleted">"Deleted"?</label></span>
				<span class="formw"><input type="checkbox" name="deleted[]" id="deleted" value="true"<?php
	if (isset($details["deleted"]) && $details["deleted"] == true) {
		print " checked";
	}
	?>> <small>(No data will actually be deleted.)</small></span>
				</div>

<?php
	}
	
	
	if ($this_page == "useredit" || $this_page == "otheruseredit") {
		$submittext = "Update details";
	} else {
		$submittext = "Join TheyWorkForYou.com";
	}
	
	$TERMSURL = new URL('disclaimer');
?>
				<div class="row">
				<span class="label">&nbsp;</span>
				<span class="formw">&nbsp;<br><small>Read our <a href="<?php echo $TERMSURL->generate(); ?>">Terms of Use</a>.</small></span>
				</div>

				<div class="row">
				<span class="label">&nbsp;</span>
				<span class="formw"><input type="submit" class="submit" value="<?php echo $submittext; ?>"><!-- this space makes the submit button appear on Mac IE 5! --> </span>
				</div>

				<input type="hidden" name="submitted" value="true">

<?php
	if (get_http_var("ret") != "") {
		// The user was probably trying to add a writer but is joining first.
		?>
				<input type="hidden" name="ret" value="<?php echo htmlentities(get_http_var("ret")); ?>">
<?php
	}

	if (get_http_var("pg") != "") {
		// So that we get to the right page.
		?>
				<input type="hidden" name="pg" value="<?php echo htmlentities(get_http_var("pg")); ?>">
<?php
	}

	if ($this_page == "otheruseredit") {
		// Need to store the id of the user we're editing.
		?>
				<input type="hidden" name="u" value="<?php echo htmlentities($details["user_id"]); ?>">
<?php
	}
?>

				</form>
<?php

	if ($this_page == 'userjoin') {
	
		$PAGE->stripe_end(array (
			array (
				'type'		=> 'include',
				'content'	=> 'userjoin'
			)
		));
		
	} else {
		$PAGE->stripe_end();
	}


} // End display_form()






function display_user ($user_id="") {

	global $THEUSER, $PAGE, $DATA, $this_page, $who;
	

	// We're either going to be:
	//	Displaying the details of a user who's just been edited 
	//		(their user_id will be in $user_id now).
	//	Viewing THEUSER's own data.
	//	Viewing someone else's data (their id will be in the GET string 
	//		user_id variable).
	
	
	// We could do something cleverer so that if THEUSER has sufficient
	// privileges we display more data when they're viewing someone else's info
	// than what your average punter sees.
	
	
	// If $user_id is a user id, we've just edited that user's info.
	
	
	
	// FIRST: Work out whose info we're going to show.
	
	$edited = false; 	// Have we just edited someone's info?

	if (is_numeric($user_id) && $user_id == $THEUSER->user_id()) {
		// Display this user's just edited info.
		$display = "this user";
		$edited = true;
		
	} elseif (is_numeric($user_id)) {
		// Display someone else's just edited info.
		$display = "another user";
		$edited = true;
		
	} elseif (is_numeric(get_http_var("u"))) {
		// Display someone else's info.
		$user_id = get_http_var("u");
		$display = "another user";
	
	} elseif ($THEUSER->isloggedin()) {
		// Display this user's info.
		$display = "this user";
		$user_id = $THEUSER->user_id();
	
	} else {
		// Nothing to show!
		$URL = new URL('userlogin');
		$URL->insert(array('ret'=>'/user/'));
		$loginurl = $URL->generate();
		header("Location: $loginurl");
		exit;
		
	}
	
	

	// SECOND: Get the data for whoever we're going to show.
	
	if ($display == "another user") {

		// Viewing someone else's info.

		$USER = new USER;
		$valid = $USER->init($user_id);

		if ($valid && $USER->confirmed() && !$USER->deleted()) {
			// Don't want to display unconfirmed or deleted users.
		
			$name = $USER->firstname() . " " . $USER->lastname();
			$url = $USER->url();
			
			if ($USER->emailpublic() == true) {
				$email = $USER->email();
			}		
			
			$status 			= $USER->status();
			$registrationtime	= $USER->registrationtime();
	
			// Change the page title to reflect whose info we're viewing.
			$DATA->set_page_metadata($this_page, "title", "$name");
			
		} else {
			// This user_id doesn't exist.
			$display = "none";
		}
		
			
	} elseif ($display == "this user") {
		
		// Display THEUSER's info.	
		$name 			= $THEUSER->firstname() . " " . $THEUSER->lastname();
		$url 			= $THEUSER->url();
		if ($edited) {
			// We want to show all the info to the user.
			$email 			= $THEUSER->email();
			$emailpublic 	= $THEUSER->emailpublic() == true ? "Yes" : "No";
			$optin		 	= $THEUSER->optin() == true ? "Yes" : "No";
			$postcode		= $THEUSER->postcode();
		} else {
			// We're showing them how they're seen to other people.
			if ($THEUSER->emailpublic()) {
				$email 		= $THEUSER->email();
			}
			$registrationtime	= $THEUSER->registrationtime();
			$status			= $THEUSER->status();
		}
	
		// Change the page title to make it clear we're viewing THEUSER's
		// own info. Make them less worried about other people seeing some of the
		// info that shouldn't be public.
		$DATA->set_page_metadata($this_page, "title", "Your details");		
				
	} else {

		// There's nothing to display!
				
	}



	// THIRD: Print out what we've got.
	
	$PAGE->page_start();
	

	if ($display != "none") {	

		$PAGE->stripe_start();

		if (isset($registrationtime)) {
			// Make registration time more user-friendly.
			list($date, $time) = explode(' ', $registrationtime);
			$registrationtime = format_date ($date, LONGDATEFORMAT);
		}


			
		if ($edited) {
			print "\t\t\t\t<p><strong>" . ucfirst($who) . " details have been updated:</strong></p>\n";
		}

		if ($this_page == 'userviewself' && !$edited) {
			$EDITURL = new URL('useredit');
			?>
				<p><strong>This is how other people see you.</strong> <a href="<?php echo $EDITURL->generate(); ?>">Edit your details</a>.</p>
<?php	
		}

		?>
				<div class="row">
				<span class="label">Name</span>
				<span class="formw"><?php
				if (substr($name, -3) == ' MP') print '<a href="/mp/' . make_member_url(substr($name, 0, -3)) . '">';
				echo htmlentities($name);
				if (substr($name, -3) == ' MP') print '</a>';
			?></span>
				</div>

				<div class="row">
				<span class="label">Email</span>
				<span class="formw"><?php
		if (isset($email)) {
			$escaped_email = str_replace('@', '&#64;', htmlentities($email));
			?><a href="mailto:<?php echo $escaped_email . "\">" . $escaped_email; ?></a><?php
		} else {
			?>Not public<?php
		}
		?></span>
				</div>

<?php

		if (isset($postcode)) {
			if ($postcode == '') {
				$postcode = 'none';
			}
			?>
				<div class="row">&nbsp;<br>
				<span class="label">UK Postcode</span>
				<span class="formw"><?php echo htmlentities($postcode); ?> <small>(not public)</small></span>
				</div>

<?php
		}

		if (isset($url)) {
			if ($url == '') {
				$url = 'none';
			} else {
				$url = '<a href="' . htmlentities($url) . '">' . htmlentities($url) . '</a>';
			}
			?>
				<div class="row">
				<span class="label">Website</span>
				<span class="formw"><?php echo $url; ?></span>
				</div>

<?php
		}
		
		if (isset($emailpublic)) {
			?>
				<div class="row">&nbsp;<br>Let other people see your email address? <strong><?php echo htmlentities($emailpublic); ?></strong></div>

<?php
		}
		if (isset($optin)) {
			?>
				<div class="row">Receive TheyWorkForYou.com emails? <strong><?php echo htmlentities($optin); ?></strong></div>

<?php
		}

		if (isset($status)) {
			?>
				<div class="row">
				<span class="label">Status</span>
				<span class="formw"><?php echo htmlentities($status); ?></span>
				</div>
<?php
		}
		if (isset($registrationtime)) {
			?>
				<div class="row">
				<span class="label">Joined</span>
				<span class="formw"><?php echo htmlentities($registrationtime); ?></span>
				</div>
<?php
		}
		
		if ($edited && $this_page == 'userviewself') {
			$EDITURL = new URL('useredit');
			$VIEWURL = new URL('userviewself');
			?>
				<p>&nbsp;<br><a href="<?php echo $EDITURL->generate(); ?>">Edit again</a> or <a href="<?php echo $VIEWURL->generate(); ?>">see how others see you</a>.</p>
<?php
		}
		

		$PAGE->stripe_end();

		# Email alerts
		if ($this_page == 'userviewself') {
			$PAGE->stripe_start();
			print '<h3>Your email alerts</h3>';
			$db = new ParlDB;
			$q = $db->query('SELECT * FROM alerts WHERE email = "' . mysql_escape_string($THEUSER->email()).'" ORDER BY deleted,alert_id');
			$out = '';
			for ($i=0; $i<$q->rows(); ++$i) {
				$row = $q->row($i);
				$criteria = explode(' ',$row['criteria']);
				$ccc = array();
				foreach ($criteria as $c) {
					if (preg_match('#^speaker:(\d+)#',$c,$m)) {
						$MEMBER = new MEMBER(array('person_id'=>$m[1]));
						$ccc[] = $MEMBER->full_name();
					} else {
						$ccc[] = $c;
					}
				}
				$criteria = join(' ',$ccc);
				$token = $row['alert_id'] . '::' . $row['registrationtoken'];
				$confirmed = $row['confirmed'] ? 'Yes' : '<a href="/alert/confirm/?t='.$token.'">Confirm</a>';
				$deleted = $row['deleted'] ? 'Yes - <a href="/alert/undelete/?t='.$token.'">Undelete</a>' : 'No - <a href="/alert/delete/?t='.$token.'">Delete</a>';
				$out .= '<tr><td>'.$criteria.'</td><td>'.$deleted.'</td><td>'.$confirmed.'</td></tr>';
			}
			print '<p>To add a new alert, simply visit an MP or Peer\'s page or conduct a search &#8212; to be given the option of turning them into alerts automatically &#8212; or visit <a href="/alert/">the manual addition page</a>.</p>';
			if ($out) {
				print '<p>Here are your email alerts:</p>';
				print '<table cellpadding="3" cellspacing="0"><tr><th>Criteria</th><th>Deleted?</th><th>Confirmed</th></tr>' . $out . '</table>';
			} else {
				print '<p>You currently have no email alerts set up.</p>';
			}
			$PAGE->stripe_end();
		}

		if (!$edited) {
		
			$args = array(
				'user_id' => $user_id,
				'page' => get_http_var('p')
			);
	
			$COMMENTLIST = new COMMENTLIST();
			
			$COMMENTLIST->display('user', $args);
		}
		
	} else {
	
		$message = array (
			'title' => 'Sorry...',
			'text'	=> "We don't have a user ID, so we can't show you anyone's details."
		);
		
		$PAGE->message($message);
	
	}
	


	$PAGE->page_end();
		

} // end display_user()

		
?>
