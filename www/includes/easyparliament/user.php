<?php

/*

NO HTML IN THIS FILE!!

This file contains USER and THEUSER classes.

It automatically instantiates a $THEUSER global object. This refers to the person actually viewing the site. If they have a valid cookie set, $THEUSER's data will be fetched from the DB and they will be logged in. Otherwise they will have a minimum access level and will not be logged in.


The USER class allows us to fetch and alter data about any user (rather than $THEUSER).

To create a new user do:
	$USER = new USER;
	$USER->init($user_id);

You can then access all the user's variables with appropriately named functions, such as:
	$USER->user_id();
	$USER->email();
etc. Don't access the variables directly because I think that's bad.



USER is extended into the THEUSER class which is used only for the person currently using the site. ie, it adds functions for logging in and out, checking log in status, etc.


GUESTUSER:
In the database there should be a user with an id of 0 and a status of 'Viewer' (and probably a name of 'Guest').



The cookie set to indicate a logged in user is called "epuser_id". More on that in THEUSER().


Functions here:

USER
	init()				Send it a user id to fetch data from DB.
	add()				Add a new user to the DB.
	send_confirmation_email()	Done after add()ing the user.
	update_other_user()	Update the data of another user.
	change_password()	Generate a new password and put in DB.
	id_exists()			Checks if a user_id is valid.
	email_exists()		Checks if a user exists with a certain email address.
	is_able_to()		Is the user allowed to perform this action?
	possible_statuses()	Return an array of the possible security statuses for users.
	Accessor functions for each object variable (eg, user_id()  ).
	_update()			Private function that updates a user's data in DB.

THEUSER
	THEUSER()			Constructor that logs in if the cookie is correct.
	isloggedin()		Check if the user is logged in or not.
	isvalid()			Check to see if the user's login form details are OK.
	login()				Log the user in.
	logout()			Log the user out.
	confirm()			With the correct token, confirms the user then logs them in.
	update_self()		Update the user's own data in the DB.
	check_user_access()	Check a the user is allowed to view this page.

*/

class USER {

	var $user_id = "0";			// So we have an ID for non-logged in users reporting comments etc.
	var $firstname = "Guest";	// So we have something to print for non-logged in users.
	var $lastname = "";
	var $password = "";			// This will be a crypt()ed version of a plaintext pw.
	var $email = "";
	var $emailpublic = "";		// boolean - can other users see this user's email?
	var $postcode = "";
	var $url = "";
	var $lastvisit = "";		// Last time the logged-in user loaded a page (GMT).
	var $registrationtime = "";	// When they registered (GMT).
	var $registrationip = "";	// Where they registered from.
	var $optin = "";			// boolean - Do they want emails from us?
	var $deleted = "";			// User can't log in or have their info displayed.
	var $confirmed = '';		// boolean - Has the user confirmed via email?
	// Don't use the status to check access privileges - use the is_able_to() function.
	var $status = "Viewer";

	// If you add more user variables above you should also:
	// 		Add the approrprate code to $this->add()
	//		Add the appropriate code to $this->_update()
	// 		Add accessor functions way down below...
	//		Alter THEUSER->update_self() to update with the new vars, if appropriate.
	// 		Change things in the add/edit/view user page.


	function USER () {
		$this->db = new ParlDB;
	}

	function init ($user_id) {
		// Pass it a user id and it will fetch the user's data from the db
		// and put it all in the appropriate variables.
		// Returns true if we've found user_id in the DB, false otherwise.

		// Look for this user_id's details.
		$q = $this->db->query("SELECT firstname,
								lastname,
								password,
								email,
								emailpublic,
								postcode,
								url,
								lastvisit,
								registrationtime,
								registrationip,
								optin,
								status,
								deleted,
								confirmed
						FROM 	users
						WHERE 	user_id='" . mysql_escape_string($user_id) . "'");


		if ($q->rows() == 1) {
			// We've got a user, so set them up.

			$this->user_id 				= $user_id;
			$this->firstname 			= $q->field(0,"firstname");
			$this->lastname 			= $q->field(0,"lastname");
			$this->password 			= $q->field(0,"password");
			$this->email 				= $q->field(0,"email");
			$this->emailpublic = $q->field(0,"emailpublic") == 1 ? true : false;
			$this->postcode 			= $q->field(0,"postcode");
			$this->url 					= $q->field(0,"url");
			$this->lastvisit 			= $q->field(0,"lastvisit");
			$this->registrationtime 	= $q->field(0,"registrationtime");
			$this->registrationip		= $q->field(0,"registrationip");
			$this->optin = $q->field(0,"optin") == 1 ? true : false;
			$this->status	 			= $q->field(0,"status");
			$this->deleted = $q->field(0,"deleted") == 1 ? true : false;
			$this->confirmed = $q->field(0,"confirmed") == 1 ? true : false;

			return true;

		} elseif ($q->rows() > 1) {
			// And, yes, if we've ended up with more than one row returned
			// we're going to show an error too, just in case.
			// *Should* never happen...

			return false;
			twfy_debug("USER", "There is more than one user with an id of '".htmlentities($user_id)."'");

		} else {
			return false;
			twfy_debug("USER", "There is no user with an id of '".htmlentities($user_id)."'");
		}

	}



	function add ($details, $confirmation_required=true) {
		// Adds a new user's info into the db.
		// Then optionally (and usually) calls another function to
		// send them a confirmation email.

		// $details is an associative array of all the user's details, of the form:
		// array (
		//		"firstname" => "Fred",
		//		"lastname"	=> "Bloggs",
		//		etc... using the same keys as the object variable names.
		// )
		// The BOOL variables (eg, optin) will be true or false and will need to be
		// converted to 1/0 for MySQL.
		global $REMOTE_ADDR;

		$registrationtime = gmdate("YmdHis");

		// We crypt all passwords going into DB.
		$passwordforDB = crypt($details["password"]);

		if (!isset($details["status"])) {
			$details["status"] = "User";
		}

		$optin = $details["optin"] == true ? 1 : 0;

		$emailpublic = $details["emailpublic"] == true ? 1 : 0;

		$q = $this->db->query("INSERT INTO users (
				firstname,
				lastname,
				email,
				emailpublic,
				postcode,
				url,
				password,
				optin,
				status,
				registrationtime,
				registrationip,
				deleted
			) VALUES (
				'" . mysql_escape_string($details["firstname"]) . "',
				'" . mysql_escape_string($details["lastname"]) . "',
				'" . mysql_escape_string($details["email"]) . "',
				'" . mysql_escape_string($emailpublic) . "',
				'" . mysql_escape_string($details["postcode"]) . "',
				'" . mysql_escape_string($details["url"]) . "',
				'" . mysql_escape_string($passwordforDB) . "',
				'" . mysql_escape_string($optin) . "',
				'" . mysql_escape_string($details["status"]) . "',
				'" . mysql_escape_string($registrationtime) . "',
				'" . mysql_escape_string($REMOTE_ADDR) . "',
				'0'
			)
		");

		if ($q->success()) {
			// Set these so we can log in.
			// Except we no longer automatically log new users in, we
			// send them an email. So this may not be required.
			$this->user_id = $q->insert_id();
			$this->password = $passwordforDB;

			// We have to set the user's registration token.
			// This will be sent to them via email, so we can confirm they exist.
			// The token will be the first 16 characters of a crypt.

			$token = substr( crypt($details["email"] . microtime()), 12, 16 );
			// Full stops don't work well at the end of URLs in emails,
			// so replace them. We won't be doing anything clever with the crypt
			// stuff, just need to match this token.
			$this->registrationtoken = strtr($token, '.', 'X');
			

			// Add that to the DB.
			$r = $this->db->query("UPDATE users
							SET	registrationtoken = '" . mysql_escape_string($this->registrationtoken) . "'
							WHERE	user_id = '" . mysql_escape_string($this->user_id) . "'
							");

			if ($r->success()) {
				// Updated DB OK.

				if ($details['mp_alert'] && $details['postcode']) {
					$MEMBER = new MEMBER(array('postcode'=>$details['postcode']));
					$pid = $MEMBER->person_id();
					# No confirmation email, but don't automatically confirm
					$ALERT = new ALERT;
					$ALERT->add(array(
						'email' => $details['email'],
						'pid' => $pid), false, false);
				}

				if ($confirmation_required) {
					// Right, send the email...
					$success = $this->send_confirmation_email($details);

					if ($success) {
						// All is good in the world!
						return true;
					} else {
						// Couldn't send the email.
						return false;
					}
				} else {
					// No confirmation email needed.
					return true;
				}
			} else {
				// Couldn't add the registration token to the DB.
				return false;
			}

		} else {
			// Couldn't add the user's data to the DB.
			return false;
		}
	}



	function send_confirmation_email($details) {
		// After we've add()ed a user we'll probably be sending them
		// a confirmation email with a link to confirm their address.

		// $details is the array we just sent to add(), and which it's
		// passed on to us here.

		// A brief check of the facts...
		if (!is_numeric($this->user_id) ||
			!isset($details['email']) ||
			$details['email'] == '') {
			return false;
		}

		// We prefix the registration token with the user's id and '::'.
		// Not for any particularly good reason, but we do.

		$urltoken = $this->user_id . '::' . $this->registrationtoken;

		$URL = new URL('userconfirm');
		$URL->insert( array('t'=>$urltoken) );
		$confirmurl = 'http://' . DOMAIN . $URL->generate();

		// Arrays we need to send a templated email.
		$data = array (
			'to' 		=> $details['email'],
			'template' 	=> 'join_confirmation'
		);

		$merge = array (
			'FIRSTNAME' 	=> $details['firstname'],
			'LASTNAME' 		=> $details['lastname'],
			'CONFIRMURL'	=> $confirmurl
		);

		$success = send_template_email($data, $merge);

		if ($success) {
			return true;
		} else {
			return false;
		}
	}


	function update_other_user ($details) {
		// If someone (like an admin) is updating another user, call this
		// function. It checks their privileges before letting them.

		// $details is an array like that in $this->add().
		// It must include a 'user_id' element!

		global $THEUSER;

		if (!isset($details["user_id"])) {
			return false;

		} else if ($THEUSER->is_able_to("edituser")) {

			// If the user doing the updating has appropriate privileges...

			$newdetails = $this->_update($details);

			// $newdetails will be an array of details if all went well,
			// false otherwise.
			if ($newdetails) {
				return true;
			} else {
				return false;
			}

		} else {

			return false;

		}
	}



	function change_password ($email) {

		// This function is called from the Change Password page.
		// It will create a new password for the user with $email address.
		// If all goes OK it will return the plaintext version of the password.
		// Otherwise it returns false.

		if ($this->email_exists($email)) {
			
			$this->email = $email;

		// Start password generating stuff from
		// http://uk2.php.net/manual/en/function.crypt.php

			// $digits = amount of chars the password should have (between 4 and 29)
			// $c = if true, I,i,L,l will be changed to 1 and O or o will be changed
			// 		to 0 (Zero) to prevent mistakes by an userinput
			// $st = string to "U" = upper, "L" = lower, null=casesensitive

			$digits = 6;
			$c = true;
			$st = "U";


			if(!ereg("^([4-9]|((1|2){1}[0-9]{1}))$",$digits)) {
				// 4-29 chars allowed
				$digits=4;
			}

			for(;;) {

				$pwd=null;
				$o=null;

				// Generates the password ....
				for ($x=0; $x < $digits;) {
					$y = rand(1,1000);
					if($y>350 && $y<601) $d=chr(rand(48,57));
					if($y<351) $d=chr(rand(65,90));
					if($y>600) $d=chr(rand(97,122));
					if($d!=$o) {
						$o=$d; $pwd.=$d; $x++;
					}
				}

				// if you want that the user will not be confused by O or 0 ("Oh" or "Null")
				// or 1 or l ("One" or "L"), set $c=true;
				if($c) {
					$pwd=eregi_replace("(l|i)","1",$pwd);
					$pwd=eregi_replace("(o)","0",$pwd);
				}

				// If the PW fits your purpose (e.g. this regexpression) return it, else make a new one
				// (You can change this regular-expression how you want ....)
				if(ereg("^[a-zA-Z]{1}([a-zA-Z]+[0-9][a-zA-Z]+)+",$pwd)) {
					break;
				}

			}
			if($st=="L") $pwd=strtolower($pwd);
			if($st=="U") $pwd=strtoupper($pwd);

		// End password generating stuff.

		} else {

			// Email didn't exist.

			return false;

		}

		$passwordforDB = crypt($pwd);

		$q = $this->db->query ("UPDATE users SET password = '" . mysql_escape_string($passwordforDB) . "' WHERE email='" . mysql_escape_string($email) . "'");

		if ($q->success()) {
			$this->password = $pwd;
			return $pwd;

		} else {

			return false;
		}

	}
	


	function send_password_reminder () {
		global $PAGE;
		
		// You'll probably have just called $this->change_password().
		
		if ($this->email() == '') {
			$PAGE->error_message("No email set for this user, so can't send a password reminder.");
			return false;
		}
		
		$data = array (
			'to' 			=> $this->email(),
			'template' 		=> 'new_password'
		);
		
		$URL = new URL("userlogin");
		
		$merge = array (
			'EMAIL'			=> $this->email(),
			'LOGINURL' 		=> "http://" . DOMAIN . $URL->generate(),
			'PASSWORD' 		=> $this->password()
		);
		
		// send_template_email in utility.php.
		$success = send_template_email($data, $merge);
		
		return $success;

	}




	function id_exists ($user_id) {
		// Returns true if there's a user with this user_id.

		if (is_numeric($user_id)) {
			$q = $this->db->query("SELECT user_id FROM users WHERE user_id='" . mysql_escape_string($user_id) . "'");
			if ($q->rows() > 0) {
				return true;
			} else {
 				return false;
			}
		} else {
			return false;
		}

	}


	function email_exists ($email) {
		// Returns true if there's a user with this email address.

		if ($email != "") {
			$q = $this->db->query("SELECT user_id FROM users WHERE email='" . mysql_escape_string($email) . "'");
			if ($q->rows() > 0) {
				return true;
			} else {
 				return false;
			}
		} else {
			return false;
		}

	}


	function is_able_to ( $action ) {
		// Call this function to find out if a user is allowed to do something.
		// It uses the user's status to return true or false.
		// Possible actions:
		//	"addcomment"
		// 	"reportcomment"
		//	"edituser"
		global $PAGE;
		
		$status = $this->status();

		switch ($action) {

			// You can add more below as they're needed...
			// But keep them in alphabetical order!

			case "addcomment":	// Post comments.

				switch ($status) {
					case "User":			return true;
					case "Moderator":		return true;
					case "Administrator":	return true;
					case "Superuser":		return true;
					default: /* Viewer */	return false;
				}

			case "addterm": // Add Glossary terms.

				switch ($status) {
					case "User":			return true;
					case "Moderator":		return true;
					case "Administrator":	return true;
					case "Superuser":		return true;
					default: /* Viewer */	return false;
				}

			case "deletecomment": // Delete comments.

				switch ($status) {
					case "User":			return false;
					case "Moderator":		return true;
					case "Administrator":	return true;
					case "Superuser":		return true;
					default: /* Viewer */	return false;
				}

			case "edituser":

				switch ($status) {
					case "User":			return false;
					case "Moderator":		return false;
					case "Administrator":	return false;
					case "Superuser":		return true;
					default: /* Viewer */	return false;
				}

			case "reportcomment":	// Report a comment for moderation.

				switch ($status) {
					case "User":			return true;
					case "Moderator":		return true;
					case "Administrator":	return true;
					case "Superuser":		return true;
					default: /* Viewer */	return true;
				}

			case "viewadminsection":	// Access pages in the Admin section.

				switch ($status) {
					case "User":			return false;
					case "Moderator":		return false;
					case "Administrator":	return true;
					case "Superuser":		return true;
					default: /* Viewer */	return false;
				}

			case "voteonhansard":	// Rate hansard things interesting/not.
				/* Everyone */				return true;

			default:
				$PAGE->error_message ("You need to set permissions for '$action'!");
				return false;


		}



	}

	// Same for every user...
	// Just returns an array of the possible statuses a user could have.
	// Handy for forms where you edit/view users etc.
	function possible_statuses () {
		// Maybe there's a way of fetching these from the DB,
		// so we don't duplicate them here...?

		$statuses = array ("Viewer", "User", "Moderator", "Administrator", "Superuser");

		return $statuses;

	}



	// Functions for accessing the user's variables.

	function user_id() 				{ return $this->user_id; }
	function firstname() 			{ return $this->firstname; }
	function lastname() 			{ return $this->lastname; }
	function password() 			{ return $this->password; }
	function email() 				{ return $this->email; }
	function emailpublic() 			{ return $this->emailpublic; }
	function postcode() 			{ return $this->postcode; }
	function url() 					{ return $this->url; }
	function lastvisit() 			{ return $this->lastvisit; }

	function registrationtime() 	{ return $this->registrationtime; }
	function registrationip() 		{ return $this->registrationip; }
	function optin() 				{ return $this->optin; }
	// Don't use the status to check access privileges - use the is_able_to() function.
	// But you might use status() to return text to display, describing a user.
	// We can then change what status() does in the future if our permissions system
	// changes.
	function status() 				{ return $this->status; }
	function deleted() 				{ return $this->deleted; }
	function confirmed() 			{ return $this->confirmed; }


	function postcode_is_set () {
		// So we can tell if the, er, postcode is set or not.
		// Could maybe put some validation in here at some point.
		if ($this->postcode != '') {
			return true;
		} else {
			return false;
		}
	}
	

/////////// PRIVATE FUNCTIONS BELOW... ////////////////

	function _update ($details) {
		// Update a user's info.
		// DO NOT call this function direct.
		// Call either $this->update_other_user() or $this->update_self().

		// $details is an array like that in $this->add().
		global $PAGE;

		// Update email alerts if email address changed
		if ($this->email != $details['email']) {
			$this->db->query('UPDATE alerts SET email = "' . mysql_escape_string($details['email']) . '" WHERE email = "' . mysql_escape_string($this->email) . '"');
		}

		// These are used to put optional fragments of SQL in, depending
		// on whether we're changing those things or not.
		$passwordsql = "";
		$deletedsql = "";
		$confirmedsql = "";
		$statussql = "";

		if (isset($details["password"]) && $details["password"] != "") {
			// The password is being updated.
			// If not, the password fields on the form will be left blank
			// so we don't want to overwrite the user's pw in the DB!

			// We crypt all passwords going into DB.
			$passwordforDB = crypt($details["password"]);

			$passwordsql = "password	= '" . mysql_escape_string($passwordforDB) . "', ";
		}

		if (isset($details["deleted"])) {
			// 'deleted' won't always be an option (ie, if the user is updating
			// their own info).
			if ($details['deleted'] == true) {
				$del = '1';
			} elseif ($details['deleted'] == false) {
				$del = '0';
			}
			if (isset($del)) {
				$deletedsql = "deleted	= '$del', ";
			}
		}

		if (isset($details["confirmed"])) {
			// 'confirmed' won't always be an option (ie, if the user is updating
			// their own info).
			if ($details['confirmed'] == true) {
				$con = '1';
			} elseif ($details['confirmed'] == false) {
				$con = '0';
			}
			if (isset($con)) {
				$confirmedsql = "confirmed	= '$con', ";
			}
		}

		if (isset($details["status"]) && $details["status"] != "") {
			// 'status' won't always be an option (ie, if the user is updating
			// their own info.
			$statussql = "status	= '" . mysql_escape_string($details["status"]) . "', ";

		}

		// Convert internal true/false variables to MySQL BOOL 1/0 variables.
		$emailpublic = $details["emailpublic"] == true ? 1 : 0;
		$optin = $details["optin"] == true ? 1 : 0;

		$q = $this->db->query("UPDATE users
						SET		firstname 	= '" . mysql_escape_string($details["firstname"]) . "',
								lastname 	= '" . mysql_escape_string($details["lastname"]) . "',
								email		= '" . mysql_escape_string($details["email"]) . "',
								emailpublic	= '" . $emailpublic . "',
								postcode	= '" . mysql_escape_string($details["postcode"]) . "',
								url			= '" . mysql_escape_string($details["url"]) . "',"
								. $passwordsql
								. $deletedsql
								. $confirmedsql
								. $statussql . "
								optin 		= '" . $optin . "'
						WHERE 	user_id 	= '" . mysql_escape_string($details["user_id"]) . "'
						");

		// If we're returning to
		// $this->update_self() then $THEUSER will have its variables
		// updated if everything went well.
		if ($q->success()) {

			return $details;

		} else {
			$PAGE->error_message ("Sorry, we were unable to update user id '" . htmlentities($details["user_id"]) . "'");
			return false;
		}


	}





} // End USER class






class THEUSER extends USER {

	// Handles all the login/out functionality and checking for the user
	// who is using the site right NOW. Yes, him, over there.

	// This will become true if all goes well...
	var $loggedin = false;


	function THEUSER () {
		// This function is run automatically when a THEUSER
		// object is instantiated.
		
		$this->db = new ParlDB;

		// We look at the user's cookie and see if it's valid.
		// If so, we're going to log them in.

		// A user's cookie is of the form:
		// 123.blahblahblah
		// Where '123' is a user id, and 'blahblahblah' is an md5 hash of the
		// encrypted password we've stored in the db.
		// (Maybe we could just put the encrypted pw in the cookie and md5ing
		// it is overkill? Whatever, it works.)

		$cookie = get_cookie_var("epuser_id"); // In includes/utility.php.

		if ($cookie == '') {
			twfy_debug("THEUSER init FAILED", "No cookie set");
			$this->loggedin = false;

		} elseif (ereg ("([[:alnum:]]*)\.([[:alnum:]]*)", $cookie, $matches)) {

			if (is_numeric($matches[1])) {

				$success = $this->init($matches[1]);

				if ($success) {
					// We got all the user's data from the DB.

					// But we need to check the password before we log them in.
					// And make sure the user hasn't been "deleted".

					if (md5($this->password()) == $matches[2] && $this->deleted() == false) {
						// The correct password is in the cookie,
						// and the user isn't deleted, so set the user to be logged in.

						// This would be an appropriate place to call other functions
						// that might set user info that only a logged-in user is going
						// to need. Their preferences and saved things or something.


						twfy_debug ("THEUSER init SUCCEEDED", "setting as logged in");
						$this->loggedin = true;

					} elseif (md5 ($this->password()) != $matches[2]) {
						twfy_debug ("THEUSER init FAILED", "Password doesn't match cookie");
						$this->loggedin = false;
					} else {
						twfy_debug ("THEUSER init FAILED", "User is deleted");
						$this->loggedin = false;
					}

				} else {
					twfy_debug ("THEUSER init FAILED", "didn't get 1 row from db");
					$this->loggedin = false;
				}

			} else {
				twfy_debug ("THEUSER init FAILED", "cookie's user_id is not numeric");
				$this->loggedin = false;
			}

		} else {
			twfy_debug ("THEUSER init FAILED", "cookie is not of the correct form");
			$this->loggedin = false;
		}

		// If a user is logged in they *might* have set their own postcode.
		// If they aren't logged in, or they haven't set one, then we may
		// have set a postcode for them when they searched for their MP.
		// If so, we'll use that as $this->postcode.
		if ($this->postcode == '') {
			if (get_cookie_var(POSTCODE_COOKIE) != '') {
				$pc = get_cookie_var(POSTCODE_COOKIE);
				
				$this->set_postcode_cookie($pc);
			}
		}
		
		$this->update_lastvisit();


	} // End THEUSER()
	
	
	function update_lastvisit() {
		
		if ($this->isloggedin()) {
			// Set last_visit to now.
			$date_now = gmdate("Y-m-d H:i:s");
			$q = $this->db->query("UPDATE users 
							SET 	lastvisit = '$date_now'
							WHERE 	user_id = '" . $this->user_id() . "'");	
	
			$this->lastvisit = $date_now;
		}
	}


	// For completeness, but it's better to call $this->isloggedin()
	// if you want to check the log in status.
	function loggedin () 		{ return $this->loggedin; }


	function isloggedin () {
		// Call this function to check if the user is successfully logged in.

		if ($this->loggedin()) {
			twfy_debug("THEUSER", "isloggedin: true");
			return true;
		} else {
			twfy_debug("THEUSER", "isloggedin: false");
			return false;
		}
	}


	function isvalid ($email, $userenteredpassword) {
		// Returns true if this email and plaintext password match a user in the db.
		// If false returns an array of form error messages.

		// We use this on the log in page to check if the details the user entered
		// are correct. We can then continue with logging the user in (taking into
		// account their cookie remembering settings etc) with $this->login().

		$q = $this->db->query("SELECT user_id, password, deleted, confirmed FROM users WHERE email='" . mysql_escape_string($email) . "'");

		if ($q->rows() == 1) {
			// OK.
			// The password in the DB is crypted.
			$dbpassword = $q->field(0,"password");
			if (crypt($userenteredpassword, $dbpassword) == $dbpassword) {
				$this->user_id 	= $q->field(0,"user_id");
				$this->password	= $dbpassword;
				// We'll need these when we're going to log in.
				$this->deleted 	= $q->field(0,"deleted") == 1 ? true : false;
				$this->confirmed = $q->field(0,"confirmed") == 1 ? true : false;
				return true;

			} else {
				// Failed.
				return array ("invalidpassword"=>"This is not the correct password for " . htmlentities($email));

			}

		} else {
			// Failed.
			return array ("invalidemail"=>"There is no user registered with an email of " . htmlentities($email) . '. If you are subscribed to email alerts, you are not necessarily registered on the website. If you register, you will be able to manage your email alerts, as well as leave comments.');
		}

	}



	function login ($returl="", $expire) {

		// This is used to log the user in. Duh.
		// You should already have checked the user's email and password using
		// $this->isvalid()
		// That will have set $this->user_id and $this->password, allowing the
		// login to proceed...

		// $expire is either 'session' or 'never' - for the cookie.

		// $returl is the URL to redirect the user to after log in, generally the
		// page they were on before. But if it doesn't exist, they'll just go to
		// the front page.
		global $PAGE;
		
		if ($returl == "") {
			$URL = new URL("home");
			$returl = $URL->generate();
		}

		// Various checks about the user - if they fail, we exit.
		if ($this->user_id() == "" || $this->password == "") {
			$PAGE->error_message ("We don't have the user_id or password to make the cookie.", true);
			return;
		} elseif ($this->deleted) {
			$PAGE->error_message ("This user has been deleted.", true);
			return;
		} elseif (!$this->confirmed) {
			$PAGE->error_message ("this user has not been confirmed yet.", true);
			return;
		}
		
		// Unset any existing postcode cookie.
		// This will be the postcode the user set for themselves as a non-logged-in
		// user. We don't want it hanging around as it causes confusion.
		$this->unset_postcode_cookie();

		// Reminder: $this->password is actually a crypted version of the plaintext pw.
		$cookie = $this->user_id() . "." . md5 ($this->password());

		if ($expire == 'never') {
			Header("Location: $returl\nSet-Cookie:epuser_id=$cookie;expires=Friday, 16-Jan-2037 00:00:00 GMT;domain=".COOKIEDOMAIN.";path=/\n\n");
		} else {
			Header("Location: $returl\nSet-Cookie:epuser_id=$cookie;domain=".COOKIEDOMAIN.";path=/\n\n");
		}
	}



	function logout ($returl) {

		// $returl is the URL to redirect the user to after log in, generally the
		// page they were on before. But if it doesn't exist, they'll just go to
		// the front page.

		if ($returl == '') {
			$URL = new URL("home");
			$returl = $URL->generate();
		}

		// get_cookie_var() is in includes/utility.php
		if (get_cookie_var("epuser_id") != "") {
			// They're logged in, so set the cookie to empty.
			Header("Location: $returl\nSet-Cookie:epuser_id=;expires=Friday, 16-Jan-2037 00:00:00 GMT;domain=".COOKIEDOMAIN.";path=/\n\n");

	// I have no idea what this is for, so I'm removing it for the moment. Phil.
	//	} else {
	//		setcookie("epuser_id","",time()+14400,"/",COOKIEDOMAIN,0);
	//		setcookie("epid_lastlog","",time()+14400,"/",COOKIEDOMAIN,0);
		}
	}


	function confirm ($token) {
		// The user has clicked the link in their confirmation email
		// and the confirm page has passed the token from the URL to here.
		// If all goes well they'll be confirmed and then logged in.

		// Split the token into its parts.
		list($user_id, $registrationtoken) = explode("::", $token);

		if (!is_numeric($user_id) || $registrationtoken == '') {
			return false;
		}

		$q = $this->db->query("SELECT email, password, postcode
						FROM	users
						WHERE	user_id = '" . mysql_escape_string($user_id) . "'
						AND		registrationtoken = '" . mysql_escape_string($registrationtoken) . "'
						");

		if ($q->rows() == 1) {

			// We'll need these to be set before logging the user in.
			$this->user_id 	= $user_id;
			$this->email 	= $q->field(0, 'email');
			$this->password = $q->field(0, 'password');

			// Set that they're confirmed in the DB.
			$r = $this->db->query("UPDATE users
							SET		confirmed = '1'
							WHERE	user_id = '" . mysql_escape_string($user_id) . "'
							");

			if ($q->field(0, 'postcode')) {
				$MEMBER = new MEMBER(array('postcode'=>$q->field(0, 'postcode')));
				$pid = $MEMBER->person_id();
				# This should probably be in the ALERT class
				$this->db->query('update alerts set confirmed=1 where email="' .
					mysql_escape_string($this->email) . '" and criteria="speaker:' .
					mysql_escape_string($pid) . '"');
			}

			if ($r->success()) {

				$this->confirmed = true;

				// Log the user in, redirecting them to the confirm page
				// where they should get a nice welcome message.
				$URL = new URL('userconfirmed');
				$URL->insert(array('welcome'=>'t'));
				$redirecturl = $URL->generate();

				$this->login($redirecturl, 'session');

			} else {
				// Couldn't set them as confirmed in the DB.
				return false;
			}

		} else {
			// Couldn't find this user in the DB. Maybe the token was
			// wrong or incomplete?
			return false;
		}
	}
	
	
	function set_postcode_cookie ($pc) {
		// Set the user's postcode.
		// Doesn't change it in the DB, as it's probably mainly for 
		// not-logged-in users.
		
		$this->postcode = $pc;
        if (!headers_sent()) // if in debug mode
            setcookie (POSTCODE_COOKIE, $pc, time()+7*86400, "/", COOKIEDOMAIN);
		
		twfy_debug('USER', "Set the cookie named '" . POSTCODE_COOKIE . " to '$pc' for " . COOKIEDOMAIN . " domain");
	}
	
	
	function unset_postcode_cookie () {
        if (!headers_sent()) // if in debug mode
            setcookie (POSTCODE_COOKIE, '', time() - 3600, '/', COOKIEDOMAIN);
	}
	
	
	function update_self ($details) {
		// If the user wants to update their details, call this function.
		// It checks that they're logged in before letting them.


		// $details is an array like that in $this->add().

		global $THEUSER;

		if ($this->isloggedin()) {

			$details["user_id"] = $this->user_id;

			$newdetails = $this->_update($details);

			// $newdetails will be an array of details if all went well,
			// false otherwise.

			if ($newdetails) {
				// The user's data was updated, so we'll change the object
				// variables accordingly.

				$this->firstname 		= $newdetails["firstname"];
				$this->lastname 		= $newdetails["lastname"];
				$this->email 			= $newdetails["email"];
				$this->emailpublic 		= $newdetails["emailpublic"];
				$this->postcode 		= $newdetails["postcode"];
				$this->url 				= $newdetails["url"];
				$this->optin 			= $newdetails["optin"];
				if ($newdetails["password"] != "") {
					$this->password = $newdetails["password"];
				}

				return true;
			} else {
				return false;
			}

		} else {
			return false;
		}

	}
	
	
	


}

// Yes, we instantiate a new global $THEUSER object when every page loads.
$THEUSER = new THEUSER;

?>
