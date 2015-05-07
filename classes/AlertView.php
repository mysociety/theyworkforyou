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
        } elseif (get_http_var('update')) {
            $result = $this->getNewMP(get_http_var('update'));
            $data = array_merge( $data, $result );
        } elseif (get_http_var('update-alert')) {
            $success = $this->replaceAlert( get_http_var('confirmation') );
            $data['confirmation_received'] = $success;
        } elseif (get_http_var('confirmed')) {
            $success = $this->confirmAlert( get_http_var('confirmed') );
            $data['confirmation_received'] = $success;
        } else {
            $data['email'] = $this->user->email() ? $this->user->email() : '';
            $data['postcode'] = $this->user->postcode_is_set() ? $this->user->postcode() : '';

            if ( $this->isEmailSignedUpForPostCode( $data['email'], $data['postcode'] ) ) {
                $data['already_signed_up'] = True;
                $mp = $this->getPersonFromPostcode($data['postcode']);
                $data['mp_name'] = $mp->full_name();
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

    private function replaceAlert($confirmation) {
        $existing = $this->alert->fetch_by_token($confirmation);
        preg_match('/speaker:(\d+)/', $existing['criteria'], $matches);
        $old_mp_id = $matches[1];
        $old_mp = new Member(array( 'person_id' => $old_mp_id ) );
        $new_mp = new Member(array( 'constituency' => $old_mp->constituency ));

        $q = $this->db->query(
            "SELECT alert_id, criteria, registrationtoken FROM alerts
             WHERE email = :email
             AND criteria LIKE :criteria
             AND confirmed = 1
             AND deleted = 0",
            array(
                ':email' => $existing['email'],
                ':criteria' => '%speaker:' . $old_mp_id . '%'
            )
        );

        for ( $i = 0; $i < $q->rows; $i++ ) {
            // need to reset this otherwise delete does not work
            $this->alert->token_checked = null;
            $other_criteria = trim(preg_replace('/speaker:\d+/', '', $q->field($i, 'criteria')));

            $details = array(
                'email' => $existing['email'],
                'pid' => $new_mp->person_id,
                'pc' => '',
            );
            if ( $other_criteria ) {
                $details['keyword'] = $other_criteria;
            }

            $this->alert->delete($q->field($i, 'alert_id') . '::' . $q->field($i ,'registrationtoken'));
            $result = $this->alert->add($details, False);
        }

        return array(
            'signedup_no_confirm' => True,
            'new_mp' => $new_mp->full_name(),
        );
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

    private function getNewMP($confirmation) {
        if (!$confirmation) {
            return array();
        }

        $existing = $this->alert->fetch_by_token($confirmation);
        preg_match('/speaker:(\d+)/', $existing['criteria'], $matches);
        $criteria = $matches[1];
        $data = array();

        $old_mp = new Member(array( 'person_id' => $criteria ) );
        $new_mp = new Member(array( 'constituency' => $old_mp->constituency ));

        if ( $this->alert->fetch_by_mp( $existing['email'], $new_mp->person_id) ) {
            $data = array(
                'already_signed_up' => True,
                'old_mp' => $old_mp->full_name(),
                'mp_name' => $new_mp->full_name(),
            );
        } else {
            $data = array(
                'old_mp' => $old_mp->full_name(),
                'new_mp' => $new_mp->full_name(),
            );
        }

        $data['update'] = True;
        $data['confirmation'] = $confirmation;

        return $data;
    }
}
