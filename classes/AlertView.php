<?php

namespace MySociety\TheyWorkForYou;

class AlertView {

    const ALERT_EXISTS = -2;
    const CREATE_FAILED = -1;

    private $user;
    private $db;
    private $alert;

    public function __construct($THEUSER = NULL) {
        $this->user = $THEUSER;
        $this->db = new \ParlDB;
        $this->alert = new \ALERT;
    }

    public function display() {
        $data = array();
        $data['recent_election'] = True;
        if ( $this->user->loggedin() ) {
            $data['user_signed_in'] = true;
        }

        if (get_http_var('add-alert')) {

            $data['email'] = get_http_var('email');
            $data['postcode'] = get_http_var('postcode');

            $result = $this->createAlertForPostCode($data['email'], $data['postcode']);
            $data = array_merge( $data, $result );
        } elseif (get_http_var('confirmed')) {
            $success = $this->confirmAlert( get_http_var('confirmed') );
            $data['confirmation_received'] = $success;
        } else {
            $data['email'] = $this->user->email() ? $this->user->email() : '';
            $data['postcode'] = $this->user->postcode_is_set() ? $this->user->postcode() : '';

            if ( $this->isEmailSignedUpForPostCode( $data['email'], $data['postcode'] ) ) {
                $data['already_signed_up'] = True;
                $mp = $this->getPersonFromPostcode($data['postcode']);
                $data['mp_name'] = $mp->first_name . ' ' . $mp->last_name;
            }
        }

        return $data;
    }

    private function getPersonFromPostcode($postcode) {
        $args = array(
            'postcode' => $postcode,
            'house' => 1
        );

        $member = new Member($args);
        return $member;
    }

    private function validateDetails($email, $postcode) {
        $valid = true;

        if (!$email || !validate_email($email)) {
            $valid = false;
        }

        if (!$postcode || !validate_postcode($postcode)) {
            $valid = false;
        }

        return $valid;
    }

    private function createAlertForPostCode($email, $postcode) {
        if ( !$this->validateDetails($email, $postcode) ) {
            return array('invalid-postcode-or-email' => True);
        }

        try {
            $person = $this->getPersonFromPostcode($postcode);
        } catch ( MemberException $e ) {
            return array('bad-constituency' => True);
        }

        $details = array(
            'email' => $email,
            'pid' => $person->person_id,
            'pc' => $postcode,
            'confirm_base' => 'http://' . DOMAIN . '/alert/by-postcode?confirmed=',
        );

        $data = array();
        $not_logged_in = $this->user->loggedin ? false : true;
        $result = $this->alert->add($details, $not_logged_in);

        switch ($result) {
            case self::ALERT_EXISTS:
                if ( $not_logged_in ) {
                    // no logged in user so send them an email to let them
                    // know someone tried to create an alert
                    $this->alert->send_already_signedup_email($details);
                    $data['confirmation_sent'] = True;
                } else {
                    $data['already_signed_up'] = True;
                }
                break;
            case self::CREATE_FAILED:
                $data['error'] = True;
                break;
            default: // alert created
                if ( $not_logged_in ) {
                    $data['confirmation_sent'] = True;
                } else {
                    $data['signedup_no_confirm'] = True;
                }
        }

        return $data;
    }

    private function confirmAlert($token) {
        return $this->alert->confirm($token);
    }

    private function isEmailSignedUpForPostCode($email, $postcode) {
        $is_signed_up = false;

        if ( $email && $postcode ) {
            try {
                $person = $this->getPersonFromPostcode($postcode);
                $is_signed_up = $this->alert->fetch_by_mp($email, $person->person_id);
            } catch ( MemberException $e ) {
                $is_signed_up = false;
            }
        }
        return $is_signed_up;
    }
}
