<?php

// The login form page.

/*
	If the form hasn't been submitted, display_page() is called and the form shown.
	If the form has been submitted we check the input.
	If the input is OK, the user is logged in and taken to wherever they were before.
	If the input is not OK, the form is displayed again with error messages.
*/

include_once "../../../includes/easyparliament/init.php";

$this_page = "userlogin";

if (get_http_var("submitted") == "true") {
	// Form has been submitted, so check input.

	$email 		= get_http_var("email");
	$password 	= get_http_var("password");
	$remember 	= get_http_var("remember");
	
	// The user may have tried to do something that requires being logged in.
	// In which case we should arrive here with that page's URL in 'ret'.
	// We can then send the user there after log in.
	$returnurl 	= get_http_var("ret");
	
	$errors = array();

	if ($email == "") {
		$errors["email"] = "Please enter your email address";
	} elseif (!validate_email($email)) {
		$errors["email"] = "Please enter a valid email address";
	}
	if ($password == "") {
		$errors["password"] = "Please enter your password";
	}

	if (sizeof($errors) > 0) {
		// Validation errors. Print form again.
		display_page($errors);
		
	} else {
		// No errors so far, so try to log in.
		
		$valid = $THEUSER->isvalid($email, $password);

		if ($valid && !is_array($valid)) {
			// No validation errors.
			if ($remember == "true") {
				$expire = "never";
			} else {
				$expire ="session";
			}

			// $returnurl is the url of where we'll send the user after login.
			$THEUSER->login($returnurl, $expire);
			
		} else {
		
			// Merge the validation errors with any we already have.
			$errors = array_merge($errors, $valid);
	
			display_page($errors);	
		}
		
	}

} else {
	// First time to the page...
	display_page();
}


function display_page( $errors=array() ) {
	global $PAGE, $this_page, $THEUSER;
	
	$PAGE->page_start();
	
	$PAGE->stripe_start();
	
	if ($THEUSER->isloggedin()) {
		// Shouldn't really get here, but you never know.
		$URL = new URL('userlogout'); 
		?>
	<p><strong>You are already logged in. <a href="<?php echo $URL->generate(); ?>">Log out?</a></strong></p>
<?php
		$PAGE->stripe_end();
		$PAGE->page_end();
		return;
	}
	?>
	
				<p>Not yet a member? <a href="<?php $URL = new URL("userjoin"); echo $URL->generate(); ?>">Join now</a>!</p>

<?php

	$PAGE->login_form($errors);
	

	$PAGE->stripe_end(array(
		array (
			'type' => 'include',
			'content' => 'userlogin'
		)
	));

	$PAGE->page_end();

} // End display_page()


?>
