<?php

namespace MySociety\TheyWorkForYou\AlertView;

include_once '../../../www/includes/easyparliament/init.php';
include_once INCLUDESPATH . "easyparliament/member.php";
include_once INCLUDESPATH . "easyparliament/searchengine.php";
include_once INCLUDESPATH . '../../commonlib/phplib/auth.php';
include_once INCLUDESPATH . '../../commonlib/phplib/crosssell.php';

class Standard extends \MySociety\TheyWorkForYou\AlertView {
    public $data;

    public function __construct($THEUSER = null) {
        parent::__construct($THEUSER);
        $this->data = [];
    }

    public function display() {
        global $this_page;
        $this_page = "alert";

        $this->processAction();
        $this->getBasicData();
        $this->checkInput();
        $this->searchForConstituenciesAndMembers();

        if (!sizeof($this->data['errors']) && ($this->data['keyword'] || $this->data['pid'])) {
            $this->addAlert();
        }

        $this->formatSearchTerms();
        $this->checkForCommonMistakes();
        $this->formatSearchMemberData();
        $this->setUserData();

        return $this->data;
    }

    private function processAction() {
        $token = get_http_var('t');
        $alert = $this->alert->check_token($token);

        $this->data['results'] = false;
        if ($action = get_http_var('action')) {
            $success = true;
            if ($action == 'Confirm') {
                $success = $this->confirmAlert($token);
                if ($success) {
                    $this->data['results'] = 'alert-confirmed';
                    $this->data['criteria'] = \MySociety\TheyWorkForYou\Utility\Alert::prettifyCriteria($this->alert->criteria);
                }
            } elseif ($action == 'Suspend') {
                $success = $this->suspendAlert($token);
                if ($success) {
                    $this->data['results'] = 'alert-suspended';
                }
            } elseif ($action == 'Resume') {
                $success = $this->resumeAlert($token);
                if ($success) {
                    $this->data['results'] = 'alert-resumed';
                }
            } elseif ($action == 'Delete') {
                $success = $this->deleteAlert($token);
                if ($success) {
                    $this->data['results'] = 'alert-deleted';
                }
            } elseif ($action == 'Delete All') {
                $success = $this->deleteAllAlerts($token);
                if ($success) {
                    $this->data['results'] = 'all-alerts-deleted';
                }
            }
            if (!$success) {
                $this->data['results'] = 'alert-fail';
            }
        }

        $this->data['alert'] = $alert;
    }


    private function getBasicData() {
        global $this_page;

        if ($this->user->loggedin()) {
            $this->data['email'] = $this->user->email();
            $this->data['email_verified'] = true;
        } elseif ($this->data['alert']) {
            $this->data['email'] = $this->data['alert']['email'];
            $this->data['email_verified'] = true;
        } else {
            $this->data["email"] = trim(get_http_var("email"));
            $this->data['email_verified'] = false;
        }
        $this->data['keyword'] = trim(get_http_var("keyword"));
        $this->data['pid'] = trim(get_http_var("pid"));
        $this->data['alertsearch'] = trim(get_http_var("alertsearch"));
        $this->data['pc'] = get_http_var('pc');
        $this->data['submitted'] = get_http_var('submitted') || $this->data['pid'] || $this->data['keyword'];
        $this->data['token'] = get_http_var('t');
        $this->data['sign'] = get_http_var('sign');
        $this->data['site'] = get_http_var('site');
        $this->data['message'] = '';

        $ACTIONURL = new \MySociety\TheyWorkForYou\Url($this_page);
        $ACTIONURL->reset();
        $this->data['actionurl'] = $ACTIONURL->generate();
    }

    private function checkInput() {
        global $SEARCHENGINE;

        $errors = [];

        // Check each of the things the user has input.
        // If there is a problem with any of them, set an entry in the $errors array.
        // This will then be used to (a) indicate there were errors and (b) display
        // error messages when we show the form again.

        // Check email address is valid and unique.
        if (!$this->data['email']) {
            $errors["email"] = gettext("Please enter your email address");
        } elseif (!validate_email($this->data["email"])) {
            // validate_email() is in includes/utilities.php
            $errors["email"] = gettext("Please enter a valid email address");
        }

        if ($this->data['pid'] && !ctype_digit($this->data['pid'])) {
            $errors['pid'] = 'Invalid person ID passed';
        }

        $text = $this->data['alertsearch'];
        if (!$text) {
            $text = $this->data['keyword'];
        }

        if ($this->data['submitted'] && !$this->data['pid'] && !$text) {
            $errors['alertsearch'] = gettext('Please enter what you want to be alerted about');
        }

        if (strpos($text, '..')) {
            $errors['alertsearch'] = gettext('You probably don&rsquo;t want a date range as part of your criteria, as you won&rsquo;t be alerted to anything new!');
        }

        $se = new \SEARCHENGINE($text);
        if (!$se->valid) {
            $errors['alertsearch'] = sprintf(gettext('That search appears to be invalid - %s - please check and try again.'), $se->error);
        }

        if (strlen($text) > 255) {
            $errors['alertsearch'] = gettext('That search is too long for our database; please split it up into multiple smaller alerts.');
        }

        $this->data['errors'] = $errors;
    }

    private function searchForConstituenciesAndMembers() {
        // Do the search
        if ($this->data['alertsearch']) {
            $this->data['members'] = \MySociety\TheyWorkForYou\Utility\Search::searchMemberDbLookupWithNames($this->data['alertsearch'], true);
            [$this->data['constituencies'], $this->data['valid_postcode']] = \MySociety\TheyWorkForYou\Utility\Search::searchConstituenciesByQuery($this->data['alertsearch']);
        } else {
            $this->data['members'] = [];
        }

        # If the above search returned one result for constituency
        # search by postcode, use it immediately
        if (isset($this->data['constituencies']) && count($this->data['constituencies']) == 1 && $this->data['valid_postcode']) {
            $MEMBER = new \MEMBER(['constituency' => $this->data['constituencies'][0], 'house' => 1]);
            $this->data['pid'] = $MEMBER->person_id();
            $this->data['pc'] = $this->data['alertsearch'];
            unset($this->data['constituencies']);
            $this->data['alertsearch'] = '';
        }

        if (isset($this->data['constituencies'])) {
            $cons = [];
            foreach ($this->data['constituencies'] as $constituency) {
                try {
                    $MEMBER = new \MEMBER(['constituency' => $constituency, 'house' => 1]);
                    $cons[$constituency] = $MEMBER;
                } catch (\MySociety\TheyWorkForYou\MemberException $e) {
                    // do nothing
                }
            }
            $this->data['constituencies'] = $cons;
        }
    }

    private function addAlert() {
        $external_auth = auth_verify_with_shared_secret($this->data['email'], OPTION_AUTH_SHARED_SECRET, get_http_var('sign'));
        if ($external_auth) {
            $confirm = false;
        } elseif ($this->data['email_verified']) {
            $confirm = false;
        } else {
            $confirm = true;
        }

        // If this goes well, the alert will be added to the database and a confirmation email
        // will be sent to them.
        $success = $this->alert->add($this->data, $confirm);

        if ($success > 0 && !$confirm) {
            $result = 'alert-added';
        } elseif ($success > 0) {
            $result = 'alert-confirmation';
        } elseif ($success == -2) {
            // we need to make sure we know that the person attempting to sign up
            // for the alert has that email address to stop people trying to work
            // out what alerts they are signed up to
            if ($this->data['email_verified'] || ($this->user->loggedin && $this->user->email() == $this->data['email'])) {
                $result = 'alert-exists';
            } else {
                // don't throw an error message as that implies that they have already signed
                // up for the alert but instead pretend all is normal but send an email saying
                // that someone tried to sign them up for an existing alert
                $result = 'alert-already-signed';
                $this->alert->send_already_signedup_email($this->data);
            }
        } else {
            $result = 'alert-fail';
        }

        // don't need these anymore so get rid of them
        $this->data['keyword'] = '';
        $this->data['pid'] = '';
        $this->data['alertsearch'] = '';
        $this->data['pc'] = '';

        $this->data['results'] = $result;
        $this->data['criteria'] = \MySociety\TheyWorkForYou\Utility\Alert::prettifyCriteria($this->alert->criteria);
    }


    private function formatSearchTerms() {
        if ($this->data['alertsearch']) {
            $this->data['alertsearch_pretty'] = \MySociety\TheyWorkForYou\Utility\Alert::prettifyCriteria($this->data['alertsearch']);
            $this->data['search_text'] = $this->data['alertsearch'];
        } else {
            $this->data['search_text'] = $this->data['keyword'];
        }
    }

    private function checkForCommonMistakes() {
        $mistakes = [];
        if (strstr($this->data['alertsearch'], ',') > -1) {
            $mistakes['multiple'] = 1;
        }

        if (
            preg_match('#([A-Z]{1,2}\d+[A-Z]? ?\d[A-Z]{2})#i', $this->data['alertsearch'], $m) &&
            strlen($this->data['alertsearch']) > strlen($m[1]) &&
            validate_postcode($m[1])
        ) {
            $this->data['postcode'] = $m[1];
            $mistakes['postcode_and'] = 1;
        }

        $this->data['mistakes'] = $mistakes;
    }

    private function formatSearchMemberData() {
        if (isset($this->data['postcode'])) {
            try {
                $postcode = $this->data['postcode'];

                $MEMBER = new \MEMBER(['postcode' => $postcode]);
                // move the postcode to the front just to be tidy
                $tidy_alertsearch = $postcode . " " . trim(str_replace("$postcode", "", $this->data['alertsearch']));
                $alertsearch_display = str_replace("$postcode ", "", $tidy_alertsearch);

                $this->data['member_alertsearch'] = str_replace("$postcode", "speaker:" . $MEMBER->person_id, $tidy_alertsearch);
                $this->data['member_displaysearch'] = $alertsearch_display;
                $this->data['member'] = $MEMBER;

                if (isset($this->data['mistakes']['postcode_and'])) {
                    $constituencies = \MySociety\TheyWorkForYou\Utility\Postcode::postcodeToConstituencies($postcode);
                    if (isset($constituencies['SPC'])) {
                        $MEMBER = new \MEMBER(['constituency' => $constituencies['SPC'], 'house' => HOUSE_TYPE_SCOTLAND]);
                        $this->data['scottish_alertsearch'] = str_replace("$postcode", "speaker:" . $MEMBER->person_id, $tidy_alertsearch);
                        $this->data['scottish_member'] = $MEMBER;
                    } elseif (isset($constituencies['WAC'])) {
                        $MEMBER = new \MEMBER(['constituency' => $constituencies['WAC'], 'house' => HOUSE_TYPE_WALES]);
                        $this->data['welsh_alertsearch'] = str_replace("$postcode", "speaker:" . $MEMBER->person_id, $tidy_alertsearch);
                        $this->data['welsh_member'] = $MEMBER;
                    }
                }
            } catch (\MySociety\TheyWorkForYou\MemberException $e) {
                $this->data['member_error'] = 1;
            }
        }

        if ($this->data['pid']) {
            $MEMBER = new \MEMBER(['person_id' => $this->data['pid']]);
            $this->data['pid_member'] = $MEMBER;
        }

        if ($this->data['keyword']) {
            $this->data['display_keyword'] = \MySociety\TheyWorkForYou\Utility\Alert::prettifyCriteria($this->data['keyword']);
        }
    }

    private function setUserData() {
        $this->data['current_mp'] = false;
        $this->data['alerts'] = [];
        if ($this->data['email_verified']) {
            if ($this->user->postcode()) {
                $current_mp = new \MEMBER(['postcode' => $this->user->postcode()]);
                if (!$this->alert->fetch_by_mp($this->data['email'], $current_mp->person_id())) {
                    $this->data['current_mp'] = $current_mp;
                }
            }
            $this->data['alerts'] = \MySociety\TheyWorkForYou\Utility\Alert::forUser($this->data['email']);
        }
    }
}
