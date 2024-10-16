<?php

namespace MySociety\TheyWorkForYou\AlertView;

include_once '../../../www/includes/easyparliament/init.php';
include_once INCLUDESPATH . "easyparliament/member.php";
include_once INCLUDESPATH . "easyparliament/searchengine.php";
include_once INCLUDESPATH . '../../commonlib/phplib/auth.php';
include_once INCLUDESPATH . '../../commonlib/phplib/crosssell.php';

class Standard extends \MySociety\TheyWorkForYou\AlertView\Common {
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
}
