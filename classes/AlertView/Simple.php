<?php

namespace MySociety\TheyWorkForYou\AlertView;

class Simple extends \MySociety\TheyWorkForYou\AlertView {
    public function display() {
        $data = [];
        $data['recent_election'] = false;
        if ($this->user->loggedin()) {
            $data['user_signed_in'] = true;
        }

        if (get_http_var('add-alert')) {

            $data['email'] = get_http_var('email');
            $data['postcode'] = trim(get_http_var('postcode'));

            $result = $this->createAlertForPostCode($data['email'], $data['postcode']);
            $data = array_merge($data, $result);
        } elseif (get_http_var('update')) {
            $result = $this->getNewMP(get_http_var('update'));
            $data = array_merge($data, $result);
        } elseif (get_http_var('update-alert')) {
            $success = $this->replaceAlert(get_http_var('confirmation'));
            $data['confirmation_received'] = $success;
        } elseif (get_http_var('confirmed')) {
            $success = $this->confirmAlert(get_http_var('confirmed'));
            $data['confirmation_received'] = $success;
        } else {
            $data['email'] = $this->user->email() ? $this->user->email() : '';
            $data['postcode'] = $this->user->postcode_is_set() ? $this->user->postcode() : '';

            if ($this->isEmailSignedUpForPostCode($data['email'], $data['postcode'])) {
                $data['already_signed_up'] = true;
                $mp = $this->getPersonFromPostcode($data['postcode']);
                $data['mp_name'] = $mp->full_name();
            }
        }

        return $data;
    }

    private function getPersonFromPostcode($postcode) {
        $args = [
            'postcode' => $postcode,
            'house' => 1,
        ];

        $member = new \MySociety\TheyWorkForYou\Member($args);
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
        if (!$this->validateDetails($email, $postcode)) {
            return ['invalid-postcode-or-email' => true];
        }

        try {
            $person = $this->getPersonFromPostcode($postcode);
        } catch (\MySociety\TheyWorkForYou\MemberException $e) {
            return ['bad-constituency' => true];
        }

        $details = [
            'email' => $email,
            'pid' => $person->person_id,
            'pc' => $postcode,
            'confirm_base' => 'https://' . DOMAIN . '/alert/by-postcode?confirmed=',
        ];

        $data = [];
        $not_logged_in = $this->user->loggedin ? false : true;
        $result = $this->alert->add($details, $not_logged_in);

        switch ($result) {
            case self::ALERT_EXISTS:
                if ($not_logged_in) {
                    // no logged in user so send them an email to let them
                    // know someone tried to create an alert
                    $this->alert->send_already_signedup_email($details);
                    $data['confirmation_sent'] = true;
                } else {
                    $data['already_signed_up'] = true;
                }
                break;
            case self::CREATE_FAILED:
                $data['error'] = true;
                break;
            default: // alert created
                if ($not_logged_in) {
                    $data['confirmation_sent'] = true;
                } else {
                    $data['signedup_no_confirm'] = true;
                }
        }

        return $data;
    }

    private function replaceAlert($confirmation) {
        $existing = $this->alert->fetch_by_token($confirmation);
        preg_match('/speaker:(\d+)/', $existing['criteria'], $matches);
        $old_mp_id = $matches[1];
        $old_mp = new \MySociety\TheyWorkForYou\Member([ 'person_id' => $old_mp_id ]);
        $new_mp = new \MySociety\TheyWorkForYou\Member([ 'constituency' => $old_mp->constituency, 'house' => 1 ]);

        $q = $this->db->query(
            "SELECT alert_id, criteria, registrationtoken FROM alerts
             WHERE email = :email
             AND criteria LIKE :criteria
             AND confirmed = 1
             AND deleted = 0",
            [
                ':email' => $existing['email'],
                ':criteria' => '%speaker:' . $old_mp_id . '%',
            ]
        );

        foreach ($q as $row) {
            // need to reset this otherwise delete does not work
            $this->alert->token_checked = null;
            $other_criteria = trim(preg_replace('/speaker:\d+/', '', $row['criteria']));

            $details = [
                'email' => $existing['email'],
                'pid' => $new_mp->person_id,
                'pc' => '',
            ];
            if ($other_criteria) {
                $details['keyword'] = $other_criteria;
            }

            $this->alert->delete($row['alert_id'] . '::' . $row['registrationtoken']);
            $this->alert->add($details, false);
        }

        return [
            'signedup_no_confirm' => true,
            'new_mp' => $new_mp->full_name(),
        ];
    }

    private function isEmailSignedUpForPostCode($email, $postcode) {
        $is_signed_up = false;

        if ($email && $postcode) {
            try {
                $person = $this->getPersonFromPostcode($postcode);
                $is_signed_up = $this->alert->fetch_by_mp($email, $person->person_id);
            } catch (\MySociety\TheyWorkForYou\MemberException $e) {
                $is_signed_up = false;
            }
        }
        return $is_signed_up;
    }

    private function getNewMP($confirmation) {
        if (!$confirmation) {
            return [];
        }

        $existing = $this->alert->fetch_by_token($confirmation);
        preg_match('/speaker:(\d+)/', $existing['criteria'], $matches);
        $criteria = $matches[1];

        $old_mp = new \MySociety\TheyWorkForYou\Member([ 'person_id' => $criteria ]);
        $new_mp = new \MySociety\TheyWorkForYou\Member([ 'constituency' => $old_mp->constituency, 'house' => 1 ]);

        if ($this->alert->fetch_by_mp($existing['email'], $new_mp->person_id)) {
            $data = [
                'already_signed_up' => true,
                'old_mp' => $old_mp->full_name(),
                'mp_name' => $new_mp->full_name(),
            ];
        } else {
            $data = [
                'old_mp' => $old_mp->full_name(),
                'new_mp' => $new_mp->full_name(),
            ];
        }

        $data['update'] = true;
        $data['confirmation'] = $confirmation;

        return $data;
    }
}
