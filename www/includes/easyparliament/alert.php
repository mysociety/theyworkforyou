<?php

/*

NO HTML IN THIS FILE!!

// Name: alert.php
// Author:  Richard Allan richard@sheffieldhallam.org.uk
// Version: 0.5 beta
// Date: 6th Jan 2005
// Description:  This file contains ALERT class.

The ALERT class allows us to fetch and alter data about any email alert.
Functions here:

ALERT
	fetch($confirmed, $deleted)		Fetch all alert data from DB.
	listalerts()					Lists all live alerts
	add($details, $confirmation_email)	Add a new alert to the DB.
	send_confirmation_email($details)	Done after add()ing the alert.
	email_exists($email)			Checks if an alert exists with a certain email address.
	confirm($token)				Confirm a new alert in the DB
	delete($token)				Remove an existing alert from the DB
	id_exists()				Checks if an alert_id is valid.
	
	Accessor functions for each object variable (eg, alert_id()  ).

To create a new alert do:
	$ALERT = new ALERT;
	$ALERT->add();

You can then access all the alert's variables with appropriately named functions, such as:
	$ALERT->alert_id();
	$ALERT->email();
etc.

*/

// CLASS:  ALERT

function alert_details_to_criteria($details) {
	$criteria = array();
	if (isset($details['keyword']) && $details['keyword']) $criteria[] = $details['keyword'];
	if ($details['pid']) $criteria[] = 'speaker:'.$details['pid'];
	$criteria = join(' ', $criteria);
	return $criteria;
}

class ALERT {

	var $alert_id = "";
	var $email = "";
	var $criteria = "";		// Sets the terms that are used to prdduce the search results.
	var $deleted = "";		// Flag set when user requests deletion of alert.
	var $confirmed = "";		// boolean - Has the user confirmed via email?

	function ALERT () {
		$this->db = new ParlDB;
	}

// FUNCTION: fetch

	function fetch ($confirmed, $deleted) {
		// Pass it an alert id and it will fetch data about alerts from the db
		// and put it all in the appropriate variables.
		// Normal usage is for $confirmed variable to be set to true
		// and $deleted variable to be set to false
		// so that only live confirmed alerts are chosen.

		// Look for this alert_id's details.
		$q = $this->db->query("SELECT alert_id,
						email,
						criteria,
						registrationtoken,
						deleted,
						confirmed
						FROM alerts
						WHERE confirmed =" . $confirmed .
						" AND deleted=" . $deleted .
						' ORDER BY email');

		$data = array();
			
			for ($row=0; $row<$q->rows(); $row++) {
				$contents = array(
				'alert_id' 	=> $q->field($row, 'alert_id'),
				'email' 	=> $q->field($row, 'email'),
				'criteria' 	=> $q->field($row, 'criteria'),
				'registrationtoken' => $q->field($row, 'registrationtoken'),
				'confirmed' 	=> $q->field($row, 'confirmed'),
				'deleted' 	=> $q->field($row, 'deleted')
			);
				$data[] = $contents;
			}
			$info = "Alert";
			$data = array ('info' => $info, 'data' => $data);
		
			
			return $data;
	}

// FUNCTION: listalserts

	function listalerts () {
			
			// Lists all live alerts
			
			$tmpdata = array();
			$confirmed = '1';
			$deleted = '0';

			// Get all the data that's to be returned.
			$tmpdata = $this->fetch($confirmed, $deleted);
//			foreach ($tmpdata as $n => $data)
//				{
//					echo "Alert: " . $data['email'] . " and " . $data['criteria'];
//				}
	}

// FUNCTION: add

	function add ($details, $confirmation_email=false, $instantly_confirm=true) {
		
		// Adds a new alert's info into the database.
		// Then calls another function to send them a confirmation email.
		// $details is an associative array of all the alert's details, of the form:
		// array (
		//		"email" => "user@foo.com",
		//		"criteria"	=> "speaker:521",
		//		etc... using the same keys as the object variable names.
		// )
		
		// The BOOL variables confirmed and deleted will be true or false and will need to be
		// converted to 1/0 for MySQL.
		
		global $REMOTE_ADDR;

		$alerttime = gmdate("YmdHis");

		$criteria = alert_details_to_criteria($details);

		$q = $this->db->query("SELECT * FROM alerts WHERE email='".mysql_escape_string($details['email'])."' AND criteria='".mysql_escape_string($criteria)."' AND confirmed=1");
		if ($q->rows() > 0) {
			$deleted = $q->field(0, 'deleted');
			if ($deleted) {
				$this->db->query("UPDATE alerts SET deleted=0 WHERE email='".mysql_escape_string($details['email'])."' AND criteria='".mysql_escape_string($criteria)."' AND confirmed=1");
				return 1;
			} else {
				return -2;
			}
		}

		$q = $this->db->query("INSERT INTO alerts (
				email, criteria, deleted, confirmed, created
			) VALUES (
				'" . mysql_escape_string($details["email"]) . "',
				'" . mysql_escape_string($criteria) . "',
				'0', '0', NOW()
			)
		");

		if ($q->success()) {

			// Get the alert id so that we can perform the updates for confirmation

			$this->alert_id = $q->insert_id();
			$this->criteria = $criteria;

			// We have to set the alert's registration token.
			// This will be sent to them via email, so we can confirm they exist.
			// The token will be the first 16 characters of a crypt.

			// This gives a code for their email address which is then joined
			// to the timestamp so as to provide a unique ID for each alert.

			$token = substr( crypt($details["email"] . microtime() ), 12, 16 );

			// Full stops don't work well at the end of URLs in emails,
			// so replace them. We won't be doing anything clever with the crypt
			// stuff, just need to match this token.

			$this->registrationtoken = strtr($token, '.', 'X');
	
			// Add that to the database.

			$r = $this->db->query("UPDATE alerts
						SET registrationtoken = '" . mysql_escape_string($this->registrationtoken) . "'
						WHERE alert_id = '" . mysql_escape_string($this->alert_id) . "'
						");

			if ($r->success()) {
				// Updated DB OK.

				if ($confirmation_email) {
					// Right, send the email...
					$success = $this->send_confirmation_email($details);

					if ($success) {
						// Email sent OK
						return 1;
					} else {
						// Couldn't send the email.
						return -1;
					}
				} elseif ($instantly_confirm) {
					// No confirmation email needed.
					$s = $this->db->query("UPDATE alerts
						SET confirmed = '1'
						WHERE alert_id = '" . mysql_escape_string($this->alert_id) . "'
						");
					return 1;
				}
			} else {
				// Couldn't add the registration token to the DB.
				return -1;
			}

		} else {
			// Couldn't add the user's data to the DB.
			return -1;
		}
	}

// FUNCTION:  send_confirmation_email

	function send_confirmation_email($details) {

		// After we've add()ed an alert we'll be sending them
		// a confirmation email with a link to confirm their address.
		// $details is the array we just sent to add(), and which it's
		// passed on to us here.
		// A brief check of the facts...
		if (!is_numeric($this->alert_id) ||
			!isset($details['email']) ||
			$details['email'] == '') {
			return false;
		}

		// We prefix the registration token with the alert's id and '-'.
		// Not for any particularly good reason, but we do.

		$urltoken = $this->alert_id . '-' . $this->registrationtoken;

		$confirmurl = 'http://' . DOMAIN . '/A/' . $urltoken;
		
		// Arrays we need to send a templated email.
		$data = array (
			'to' 		=> $details['email'],
			'template' 	=> 'alert_confirmation'
		);

		$merge = array (
			'FIRSTNAME' 	=> 'THEY WORK FOR YOU',
			'LASTNAME' 		=> ' ALERT CONFIRMATION',
			'CONFIRMURL'	=> $confirmurl,
			'CRITERIA'	=> $this->criteria_pretty()
		);

		$success = send_template_email($data, $merge);
		if ($success) {
			return true;
		} else {
			return false;
		}
	}


// FUNCTION: email_exists

	function email_exists ($email) {
		// Returns true if there's a user with this email address.

		if ($email != "") {
			$q = $this->db->query("SELECT alert_id FROM alerts WHERE email='" . mysql_escape_string($email) . "'");
			if ($q->rows() > 0) {
				return true;
			} else {
 				return false;
			}
		} else {
			return false;
		}

	}

// FUNCTION: confirm

	function confirm ($token) {
		// The user has clicked the link in their confirmation email
		// and the confirm page has passed the token from the URL to here.
		// If all goes well the alert will be confirmed.
		// The alert will be active when scripts run each day to send the actual emails.

		// Split the token into its parts.
		if (strstr($token, '::')) $arg = '::';
		else $arg = '-';
		$token_parts = explode($arg, $token);
		if (count($token_parts)!=2)
			return false;
		list($alert_id, $registrationtoken) = $token_parts;

		if (!is_numeric($alert_id) || $registrationtoken == '') {
			return false;
		}

		$q = $this->db->query("SELECT email, criteria
						FROM alerts
						WHERE alert_id = '" . mysql_escape_string($alert_id) . "'
						AND registrationtoken = '" . mysql_escape_string($registrationtoken) . "'
						");

		if ($q->rows() == 1) {
			$this->criteria = $q->field(0, 'criteria');
			$this->email = $q->field(0, 'email');
			$r = $this->db->query("UPDATE alerts
						SET confirmed = '1', deleted = '0'
						WHERE	alert_id = '" . mysql_escape_string($alert_id) . "'
						");

			if ($r->success()) {
				$this->confirmed = true;
				return true;
			} else {
				return false;
			}
		} else {
			// Couldn't find this alert in the DB. Maybe the token was
			// wrong or incomplete?
			return false;
		}
	}

// FUNCTION:  delete	

	function delete ($token) {
		// The user has clicked the link in their delete confirmation email
		// and the deletion page has passed the token from the URL to here.
		// If all goes well the alert will be flagged as deleted.

		// Split the token into its parts.
		if (strstr($token, '::')) $arg = '::';
		else $arg = '-';
		$bits = explode($arg, $token);
		if (count($bits)<2)
			return false;
		list($alert_id, $registrationtoken) = $bits;

		if (!is_numeric($alert_id) || $registrationtoken == '') {
			return false;
		}

		$q = $this->db->query("SELECT email, criteria
						FROM alerts
						WHERE alert_id = '" . mysql_escape_string($alert_id) . "'
						AND registrationtoken = '" . mysql_escape_string($registrationtoken) . "'
						");

		if ($q->rows() == 1) {

			// Set that they're confirmed in the DB.
			$r = $this->db->query("UPDATE alerts
						SET deleted = '1'
						WHERE	alert_id = '" . mysql_escape_string($alert_id) . "'
						");

			if ($r->success()) {

				$this->deleted = true;
				return true;
				
			} else {
				// Couldn't delete this alert in the DB.
				return false;
			}

		} else {
			// Couldn't find this alert in the DB. Maybe the token was
			// wrong or incomplete?
			return false;
		}
	}


	// Functions for accessing the user's variables.

	function alert_id() 			{ return $this->alert_id; }
	function email() 			{ return $this->email; }
	function criteria() 			{ return $this->criteria; }
	function criteria_pretty($html = false) {
		$criteria = explode(' ',$this->criteria);
		$words = array(); $spokenby = '';
		foreach ($criteria as $c) {
			if (preg_match('#^speaker:(\d+)#',$c,$m)) {
				$MEMBER = new MEMBER(array('person_id'=>$m[1]));
				$spokenby = $MEMBER->full_name();
			} else {
				$words[] = $c;
			}
		}
		$criteria = '';
		if (count($words)) $criteria .= ($html?'<li>':'* ') . 'Containing the ' . make_plural('word', count($words)) . ': ' . join(' ', $words) . ($html?'</li>':'') . "\n";
		if ($spokenby) $criteria .= ($html?'<li>':'* ') . "Spoken by $spokenby" . ($html?'</li>':'') . "\n";
		return $criteria;
	}
	function deleted() 			{ return $this->deleted; }
	function confirmed() 			{ return $this->confirmed; }


/////////// PRIVATE FUNCTIONS BELOW... ////////////////


} // End USER class


?>
