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

	fetch_between($confirmed, $deleted, $start_date, $end_date)	Fetch summary data on alerts created between the dates.
	fetch($confirmed, $deleted)					Fetch all alert data from DB.
	add($details, $confirmation_email)				Add a new alert to the DB.
	send_confirmation_email($details)				Done after add()ing the alert.
	email_exists($email)						Checks if an alert exists with a certain email address.
	confirm($token)							Confirm a new alert in the DB
	delete($token)							Remove an existing alert from the DB
	id_exists()							Checks if an alert_id is valid.
	
To create a new alert do:
	$ALERT = new ALERT;
	$ALERT->add();

You can then access all the alert's variables with appropriately named functions, such as:
	$ALERT->email();
etc.

*/

// CLASS:  ALERT

function alert_confirmation_advert($details) {
	global $THEUSER;

	$adverts = array(
		#array('hfymp0', '<h2 style="border-top: dotted 1px #999999; padding-top:0.5em; margin-bottom:0">Get email from your MP in the future</h2> <p style="font-size:120%;margin-top:0;">and have a chance to discuss what they say in a public forum [button]Sign up to HearFromYourMP[/button]'),
		array('hfymp1', '<h2 style="border-top: dotted 1px #999999; padding-top:0.5em; margin-bottom:0">Get email from your MP in the future</h2> <p style="font-size:120%;margin-top:0;">and have a chance to discuss what they say in a public forum [form]Sign up to HearFromYourMP[/form]'),
		#array('fms0', '<p>Got a local problem like potholes or flytipping in your street?<br><a href="http://www.fixmystreet.com/">Report it at FixMyStreet</a></p>'),
		#array('gny0', '<h2>Are you a member of a local group&hellip;</h2> <p>&hellip;which uses the internet to coordinate itself, such as a neighbourhood watch? If so, please help the charity that runs TheyWorkForYou by <a href="http://www.groupsnearyou.com/add/about/">adding some information about it</a> to our new site, GroupsNearYou.</p>'),
		#array('twfy_alerts0', ''),
	);

	if ($THEUSER->isloggedin()) {
		$advert_shown = crosssell_display_advert('twfy', $details['email'], $THEUSER->firstname() . ' ' . $THEUSER->lastname(), $THEUSER->postcode(), $adverts);
	} else {
		$advert_shown = crosssell_display_advert('twfy', $details['email'], '', '', $adverts);
	}
	if ($advert_shown == 'other-twfy-alert-type') {
		if ($details['pid']) {
			$advert_shown = 'twfy-alert-word';
?>
<p>Did you know that TheyWorkForYou can also email you when a certain word or phrases is mentioned in parliament? For example, it could mail you when your town is mentioned, or an issue you care about. Don't rely on the newspapers to keep you informed about your interests - find out what's happening straight from the horse's mouth.
<a href="/alert/"><strong>Sign up for an email alert</strong></a></p>
<?		} else {
			$advert_shown = 'twfy-alert-person';
?>
<p>Did you know that TheyWorkForYou can also email you when a certain MP or Lord contributes in parliament? Don't rely on the newspapers to keep you informed about someone you're interested in - find out what's happening straight from the horse's mouth.
<a href="/alert/"><strong>Sign up for an email alert</strong></a></p>
<?		}
	}
	return $advert_shown;
}

	
function alert_details_to_criteria($details) {
	$criteria = array();
	if (isset($details['keyword']) && $details['keyword']) $criteria[] = $details['keyword'];
	if ($details['pid']) $criteria[] = 'speaker:'.$details['pid'];
	$criteria = join(' ', $criteria);
	return $criteria;
}

class ALERT {

    var $token_checked = null;
	var $alert_id = "";
	var $email = "";
	var $criteria = "";		// Sets the terms that are used to produce the search results.

	function ALERT () {
		$this->db = new ParlDB;
	}

// FUNCTION: fetch_between

	function fetch_between ($confirmed, $deleted, $start_date, $end_date) {
	  // Return summary data on all the alerts that were created between $start_date 
	  // and $end_date (inclusive) and whose confirmed and deleted values match the booleans
	  // passed in $confirmed and $deleted
	  	$q = $this->db->query("SELECT   criteria, count(*) as cnt
                             FROM     alerts
	                	         WHERE    confirmed = ". $confirmed .
	          	             " AND      deleted = " . $deleted . 
	                	       " AND      created >= '" .  mysql_real_escape_string($start_date) . "'" .
	                         " AND      created <= '" .  mysql_real_escape_string($end_date) . "'" .
	                   	     " GROUP BY criteria" );
		$data = array();
    for ($row=0; $row<$q->rows(); $row++) {
   	  $contents = array('criteria' => $q->field($row, 'criteria'), 'count' => $q->field($row, 'cnt'));
		  $data[] = $contents;
    }
    $data = array ('alerts' => $data);
    return $data;
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

		$q = $this->db->query("SELECT * FROM alerts WHERE email='".mysql_real_escape_string($details['email'])."' AND criteria='".mysql_real_escape_string($criteria)."' AND confirmed=1");
		if ($q->rows() > 0) {
			$deleted = $q->field(0, 'deleted');
			if ($deleted) {
				$this->db->query("UPDATE alerts SET deleted=0 WHERE email='".mysql_real_escape_string($details['email'])."' AND criteria='".mysql_real_escape_string($criteria)."' AND confirmed=1");
				return 1;
			} else {
				return -2;
			}
		}

		$q = $this->db->query("INSERT INTO alerts (
				email, criteria, deleted, confirmed, created
			) VALUES (
				'" . mysql_real_escape_string($details["email"]) . "',
				'" . mysql_real_escape_string($criteria) . "',
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
						SET registrationtoken = '" . mysql_real_escape_string($this->registrationtoken) . "'
						WHERE alert_id = '" . mysql_real_escape_string($this->alert_id) . "'
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
						WHERE alert_id = '" . mysql_real_escape_string($this->alert_id) . "'
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
			$q = $this->db->query("SELECT alert_id FROM alerts WHERE email='" . mysql_real_escape_string($email) . "'");
			if ($q->rows() > 0) {
				return true;
			} else {
 				return false;
			}
		} else {
			return false;
		}

	}

    function check_token($token) {
        if (!is_null($this->token_checked))
            return $this->token_checked;

		$arg = strstr($token, '::') ? '::' : '-';
		$token_parts = explode($arg, $token);
		if (count($token_parts) != 2)
			return false;

		list($alert_id, $registrationtoken) = $token_parts;
		if (!is_numeric($alert_id) || !$registrationtoken)
			return false;

		$q = $this->db->query("SELECT alert_id, email, criteria
						FROM alerts
						WHERE alert_id = '" . mysql_real_escape_string($alert_id) . "'
						AND registrationtoken = '" . mysql_real_escape_string($registrationtoken) . "'
						");
        if (!$q->rows()) {
            $this->token_checked = false;
            return false;
        }

        $this->token_checked = true;
        return $q;
    }

	// The user has clicked the link in their confirmation email
	// and the confirm page has passed the token from the URL to here.
	// If all goes well the alert will be confirmed.
	// The alert will be active when scripts run each day to send the actual emails.
	function confirm ($token) {
		if (!($q = $this->check_token($token))) return false;
        $this->criteria = $q->field(0, 'criteria');
        $this->email = $q->field(0, 'email');
		$r = $this->db->query("UPDATE alerts SET confirmed = 1, deleted = 0 WHERE alert_id = " . mysql_real_escape_string($q->field(0, 'alert_id')));
        return $r->success();
	}

	// The user has clicked the link in their delete confirmation email
	// and the deletion page has passed the token from the URL to here.
	// If all goes well the alert will be flagged as deleted.
	function delete($token) {
		if (!($q = $this->check_token($token))) return false;
		$r = $this->db->query("UPDATE alerts SET deleted = 1 WHERE alert_id = " . mysql_real_escape_string($q->field(0, 'alert_id')));
        return $r->success();
	}

	function suspend($token) {
		if (!($q = $this->check_token($token))) return false;
		$r = $this->db->query("UPDATE alerts SET deleted = 2 WHERE alert_id = " . mysql_real_escape_string($q->field(0, 'alert_id')));
        return $r->success();
	}

	function resume($token) {
		if (!($q = $this->check_token($token))) return false;
		$r = $this->db->query("UPDATE alerts SET deleted = 0 WHERE alert_id = " . mysql_real_escape_string($q->field(0, 'alert_id')));
        return $r->success();
	}

	// Getters
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

}

function alerts_manage($email) {
	$db = new ParlDB;
	$q = $db->query('SELECT * FROM alerts WHERE email = "' . mysql_real_escape_string($email) . '"
        AND deleted!=1 ORDER BY confirmed, deleted, alert_id');
	$out = '';
	for ($i=0; $i<$q->rows(); ++$i) {
		$row = $q->row($i);
		$criteria = explode(' ',$row['criteria']);
		$ccc = array();
		foreach ($criteria as $c) {
			if (preg_match('#^speaker:(\d+)#',$c,$m)) {
				$MEMBER = new MEMBER(array('person_id'=>$m[1]));
				$ccc[] = 'spoken by ' . $MEMBER->full_name();
			} else {
				$ccc[] = $c;
			}
		}
		$criteria = join(' ',$ccc);
		$token = $row['alert_id'] . '-' . $row['registrationtoken'];
		$action = '<form action="/alert/" method="post"><input type="hidden" name="t" value="'.$token.'">';
		if (!$row['confirmed']) {
			$action .= '<input type="submit" name="action" value="Confirm">';
		} elseif ($row['deleted']==2) {
			$action .= '<input type="submit" name="action" value="Resume">';
		} else {
			$action .= '<input type="submit" name="action" value="Suspend"> <input type="submit" name="action" value="Delete">';
		}
        $action .= '</form>';
		$out .= '<tr><td>'.$criteria.'</td><td align="center">'.$action.'</td></tr>';
	}
	print '<p>To add a new alert, simply visit an MP or Peer\'s page or conduct a search &#8212; to be given the option of turning them into alerts automatically &#8212; or visit <a href="/alert/">the manual addition page</a>.</p>';
	if ($out) {
		print '<p>Here are your email alerts:</p>';
		print '<table cellpadding="3" cellspacing="0"><tr><th>Criteria</th><th>Action</th></tr>' . $out . '</table>';
	} else {
		print '<p>You currently have no email alerts set up.</p>';
	}
}

