<?php

namespace MySociety\TheyWorkForYou\AlertView;

include_once '../../../../www/includes/easyparliament/init.php';
include_once INCLUDESPATH . "easyparliament/member.php";
include_once INCLUDESPATH . "easyparliament/searchengine.php";
include_once INCLUDESPATH . '../../commonlib/phplib/auth.php';
include_once INCLUDESPATH . '../../commonlib/phplib/crosssell.php';

class NewAlert extends \MySociety\TheyWorkForYou\AlertView\Common {
    public function display() {
        global $this_page;
        $this_page = "alertnew";

        $this->processAction();
        $this->getBasicData();
        $this->checkInput();
        $this->searchForConstituenciesAndMembers();

        if ($this->data['step'] == 'confirm' && !sizeof($this->data['errors']) && ($this->data['keyword'] || $this->data['pid'])) {
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

    protected function searchForConstituenciesAndMembers() {
        // Do the search
        if ($this->data['representative']) {
            $this->data['members'] = \MySociety\TheyWorkForYou\Utility\Search::searchMemberDbLookupWithNames($this->data['representative'], true);

            if (count($this->data['members']) == 0) {
                if (sizeof($this->data["errors"])) {
                    $this->data["errors"]["representative"] = "No matching representative found";
                } else {
                    $this->data["errors"] = ["representative" => "No matching representative found"];
                }
            }
        } else {
            $this->data['members'] = [];
        }

        error_log(print_r($this->data['members'], true));
    }

    private function wrap_phrase_in_quotes($phrase) {
        if (strpos($phrase, ' ') > 0) {
            $phrase = '"' . trim($phrase, '"') . '"';
        }

        return $phrase;
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
        $words = get_http_var('words', [], true);
        $this->data['words'] = [];

        $this->data['keywords'] = $words;
        foreach ($words as $word) {
            $this->data['words'][] = $this->wrap_phrase_in_quotes($word);
        }

        $this->data['addword'] = trim(get_http_var("addword"));
        $this->data['step'] = trim(get_http_var("step"));
        $this->data['exclusions'] = trim(get_http_var("exclusions"));
        $this->data['representative'] = trim(get_http_var("representative"));
        $this->data['search_section'] = trim(get_http_var("search_section"));
        $this->data['pid'] = trim(get_http_var("pid"));
        $this->data['token'] = get_http_var('t');
        $this->data['alertsearch'] = get_http_var('alertsearch');
        $this->data['pc'] = get_http_var('pc');

        $this->data['message'] = '';

        $this->data['keyword'] = implode(' ', $this->data['words']);
        if ($this->data['exclusions']) {
            $this->data['keyword'] .= " -" . $this->data["exclusions"];
        }

        $this->data['submitted'] = get_http_var('submitted') || $this->data['pid'] || $this->data['keyword'];

        $this->getSearchSections();

        $ACTIONURL = new \MySociety\TheyWorkForYou\Url($this_page);
        $ACTIONURL->reset();
        $this->data['actionurl'] = $ACTIONURL->generate();
    }

    private function getRecentResults($text) {
        global $SEARCHENGINE;
        $se = new \SEARCHENGINE($text);
        $this->data['search_result_count'] = $se->run_count(0,10);
        $se->run_search(0,1, 'date');
    }

    private function getSearchSections() {
        $sections = [
          "uk" => gettext('All UK'),
          "debates" => gettext('House of Commons debates'),
          "whall" => gettext('Westminster Hall debates'),
          "lords" => gettext('House of Lords debates'),
          "wrans" => gettext('Written answers'),
          "wms" => gettext('Written ministerial statements'),
          "standing" => gettext('Bill Committees'),
          "future" => gettext('Future Business'),
          "ni" => gettext('Debates'),
          "scotland" => gettext('All Scotland'),
          "sp" => gettext('Debates'),
          "spwrans" => gettext('Written answers'),
          "wales" => gettext('Debates'),
          "lmqs" => gettext('Questions to the Mayor'),
        ];

        $this->data['sections'] = [];
        if ($this->data['search_section']) {
            foreach (explode(' ', $this->data['search_section']) as $section) {
                $this->data['sections'][] = $sections[$section];
            }
        }
    }

    protected function addAlert() {
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


    protected function setUserData() {
        $criteria = $this->data['keyword'];
        if ($this->data['search_section']) {
            $criteria .= " section:" . $this->data['search_section'];
        }

        $this->getRecentResults($criteria);

        $this->data['criteria'] = $criteria;
        $this->data['current_mp'] = false;
        $this->data['alerts'] = [];
        $this->data['keyword_alerts'] = [];
        $this->data['speaker_alerts'] = [];
        $this->data['own_member_alerts'] = [];
        $this->data['all_keywords'] = [];
        $own_mp_criteria = '';
        if ($this->data['email_verified']) {
            if ($this->user->postcode()) {
                $current_mp = new \MEMBER(['postcode' => $this->user->postcode()]);
                if ($current_mp_alert = !$this->alert->fetch_by_mp($this->data['email'], $current_mp->person_id())) {
                    $this->data['current_mp'] = $current_mp;
                    $own_mp_criteria = sprintf('speaker:%s', $current_mp->person_id());
                }
            }
            $this->data['alerts'] = \MySociety\TheyWorkForYou\Utility\Alert::forUser($this->data['email']);
            foreach ($this->data['alerts'] as $alert) {
                if (array_key_exists('spokenby', $alert) and sizeof($alert['spokenby']) == 1 and $alert['spokenby'][0] == $own_mp_criteria) {
                    $this->data['own_member_alerts'][] = $alert;
                } elseif (array_key_exists('spokenby', $alert)) {
                    $this->data['spoken_alerts'][] = $alert;
                } else {
                    $this->data['all_keywords'][] = implode(' ', $alert['words']);
                    $this->data['keyword_alerts'][] = $alert;
                }
            }
        }

        if (sizeof($this->data["errors"])) {
            $this->data["step"] = get_http_var('this_step');
        }
    }
}
