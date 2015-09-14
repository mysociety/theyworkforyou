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

class ALERT {

    public $token_checked = null;
    private $alert_id = "";
    public $email = "";
    public $criteria = "";		// Sets the terms that are used to produce the search results.

    private $db;

    public function __construct() {
        $this->db = new ParlDB;
    }

// FUNCTION: fetch_between

    public function fetch_between($confirmed, $deleted, $start_date, $end_date) {
      // Return summary data on all the alerts that were created between $start_date
      // and $end_date (inclusive) and whose confirmed and deleted values match the booleans
      // passed in $confirmed and $deleted
        $q = $this->db->query("SELECT   criteria, count(*) as cnt
            FROM     alerts
            WHERE    confirmed = :confirmed
            AND      deleted = :deleted
            AND      created >= :start_date
            AND      created <= :end_date
            GROUP BY criteria", array(
            ':confirmed' => $confirmed,
            ':deleted' => $deleted,
            ':start_date' => $start_date,
            ':end_date' => $end_date
            ));
        $data = array();
    for ($row=0; $row<$q->rows(); $row++) {
      $contents = array('criteria' => $q->field($row, 'criteria'), 'count' => $q->field($row, 'cnt'));
          $data[] = $contents;
    }
    $data = array ('alerts' => $data);

    return $data;
    }

// FUNCTION: fetch

    public function fetch($confirmed, $deleted) {
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

    public function add($details, $confirmation_email=false, $instantly_confirm=true) {

        // Adds a new alert's info into the database.
        // Then calls another function to send them a confirmation email.
        // $details is an associative array of all the alert's details, of the form:
        // array (
        //		"email" => "user@foo.com",
        //		"criteria"	=> "speaker:521",
        //		etc... using the same keys as the object variable names.
        // )

        $criteria = \MySociety\TheyWorkForYou\Utility\Alert::detailsToCriteria($details);

        $q = $this->db->query("SELECT * FROM alerts
            WHERE email = :email
            AND criteria = :criteria
            AND confirmed=1", array(
                ':email' => $details['email'],
                ':criteria' => $criteria
            ));
        if ($q->rows() > 0) {
            $deleted = $q->field(0, 'deleted');
            if ($deleted) {
                $this->db->query("UPDATE alerts SET deleted=0
                    WHERE email = :email
                    AND criteria = :criteria
                    AND confirmed=1", array(
                        ':email' => $details['email'],
                        ':criteria' => $criteria
                    ));
                return 1;
            } else {
                return -2;
            }
        }

        $q = $this->db->query("INSERT INTO alerts (
                email, criteria, postcode, deleted, confirmed, created
            ) VALUES (
                :email,
                :criteria,
                :pc,
                '0', '0', NOW()
            )
        ", array(
            ':email' => $details['email'],
            ':criteria' => $criteria,
            ':pc' => $details['pc'],
            ));

        if ($q->success()) {

            // Get the alert id so that we can perform the updates for confirmation

            $this->alert_id = $q->insert_id();
            $this->criteria = $criteria;

            // We have to set the alert's registration token.
            // This will be sent to them via email, so we can confirm they exist.
            // The token will be the first 16 characters of a hash.

            // This gives a code for their email address which is then joined
            // to the timestamp so as to provide a unique ID for each alert.

            $token = substr( password_hash($details["email"] . microtime(), PASSWORD_BCRYPT), 29, 16 );

            // Full stops don't work well at the end of URLs in emails,
            // so replace them. We won't be doing anything clever with the hash
            // stuff, just need to match this token.

            $this->registrationtoken = strtr($token, '.', 'X');

            // Add that to the database.

            $r = $this->db->query("UPDATE alerts
                        SET registrationtoken = :registration_token
                        WHERE alert_id = :alert_id
                        ", array(
                            ':registration_token' => $this->registrationtoken,
                            ':alert_id' => $this->alert_id
                        ));

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
                        WHERE alert_id = :alert_id
                        ", array(
                            ':alert_id' => $this->alert_id
                        ));
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

    public function send_confirmation_email($details) {

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

        if ( isset($details['confirm_base']) && $details['confirm_base'] !== '' ) {
            $confirmurl = $details['confirm_base'] . $urltoken;
        } else {
            $confirmurl = 'http://' . DOMAIN . '/A/' . $urltoken;
        }

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

    public function send_already_signedup_email($details) {
        $data = array (
            'to' 		=> $details['email'],
            'template' 	=> 'alert_already_signedup'
        );

        $criteria = \MySociety\TheyWorkForYou\Utility\Alert::detailsToCriteria($details);
        $this->criteria = $criteria;

        $merge = array (
            'FIRSTNAME' 	=> 'THEY WORK FOR YOU',
            'LASTNAME' 		=> ' ALERT ALREADY SIGNED UP',
            'CRITERIA'	=> $this->criteria_pretty()
        );

        $success = send_template_email($data, $merge);
        if ($success) {
            return true;
        } else {
            return false;
        }
    }

    public function fetch_by_mp($email, $pid) {
        $q = $this->db->query("SELECT alert_id FROM alerts
            WHERE confirmed AND NOT deleted
            AND email = :email
            AND criteria = :criteria", array(
                ':email' => $email,
                ':criteria' => 'speaker:' . $pid
            ));
        if ($q->rows() > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function email_exists($email) {
        // Returns true if there's a user with this email address.

        if ($email != "") {
            $q = $this->db->query("SELECT alert_id FROM alerts
                WHERE email = :email", array(
                    ':email' => $email
                ));
            if ($q->rows() > 0) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }

    }

    public function check_token($token) {
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
                        WHERE alert_id = :alert_id
                        AND registrationtoken = :registration_token
                        ", array(
                            ':alert_id' => $alert_id,
                            ':registration_token' => $registrationtoken
                        ));
        if (!$q->rows()) {
            $this->token_checked = false;
        } else {
            $this->token_checked = array(
                'id' => $q->field(0, 'alert_id'),
                'email' => $q->field(0, 'email'),
                'criteria' => $q->field(0, 'criteria'),
            );
        }

        return $this->token_checked;
    }

    public function fetch_by_token($confirmation) {
        $q = $this->db->query("SELECT alert_id, email, criteria
                        FROM alerts
                        WHERE registrationtoken = :registration_token
                        ", array(
                            ':registration_token' => $confirmation
                        )
                    );

        if (!$q->rows()) {
            return false;
        } else {
            return array(
                'id' => $q->field(0, 'alert_id'),
                'email' => $q->field(0, 'email'),
                'criteria' => $q->field(0, 'criteria'),
            );
        }
    }

    // The user has clicked the link in their confirmation email
    // and the confirm page has passed the token from the URL to here.
    // If all goes well the alert will be confirmed.
    // The alert will be active when scripts run each day to send the actual emails.
    public function confirm($token) {
        if (!($alert = $this->check_token($token))) return false;
        $this->criteria = $alert['criteria'];
        $this->email = $alert['email'];
        $r = $this->db->query("UPDATE alerts SET confirmed = 1, deleted = 0 WHERE alert_id = :alert_id", array(
            ':alert_id' => $alert['id']
            ));

        return $r->success();
    }

    // The user has clicked the link in their delete confirmation email
    // and the deletion page has passed the token from the URL to here.
    // If all goes well the alert will be flagged as deleted.
    public function delete($token) {
        if (!($alert = $this->check_token($token))) return false;
        $r = $this->db->query("UPDATE alerts SET deleted = 1 WHERE alert_id = :alert_id", array(
            ':alert_id' => $alert['id']
            ));

        return $r->success();
    }

    public function suspend($token) {
        if (!($alert = $this->check_token($token))) return false;
        $r = $this->db->query("UPDATE alerts SET deleted = 2 WHERE alert_id = :alert_id", array(
            ':alert_id' => $alert['id']
            ));

        return $r->success();
    }

    public function resume($token) {
        if (!($alert = $this->check_token($token))) return false;
        $r = $this->db->query("UPDATE alerts SET deleted = 0 WHERE alert_id = :alert_id", array(
            ':alert_id' => $alert['id']
            ));

        return $r->success();
    }

    // Getters
    public function email() { return $this->email; }
    public function criteria() { return $this->criteria; }
    public function criteria_pretty($html = false) {
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
        if (count($words)) $criteria .= ($html?'<li>':'* ') . 'Mentions of [' . join(' ', $words) . ']' . ($html?'</li>':'') . "\n";
        if ($spokenby) $criteria .= ($html?'<li>':'* ') . "Things by $spokenby" . ($html?'</li>':'') . "\n";
        return $criteria;
    }

}
