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

    /**
     * This file handles all actions on user alerts.
     *
     * It splits alerts into two types and they get different screens:
     *  * keyword alerts - a person wants an alert when a word or phrase is said
     *  * representative alerts - a person wants an alert when a representative speaks or votes
     *
     * There are several form variables that control what is displayed or what action is taken:
     * * action - this is used for things happening to alerts - creation/editing, deletion, suspension etc
     * * step - this determines which step of creating a keyword alert to display
     * * this_step - stores which step is currently being processed, also used for prev step button
     * * mp_step - show the representative alert screen
     * * results - stores the results of an action, used to display appropriate message
     *
     *  The steps in the keyword alert creation/editing process are:
     *  * define - Initial options where you can add a keyword, any exclusions plus select which chambers it applies to
     *  * add_vector_related - an optional related terms step which suggests extra terms if we find any in the database
     *  * review - a confirmation step
     *
     *  If the user is happy then they will click Confirm which will submit the form with action set to Confirm and the alert
     *  will be created or updated.
     *
     * If mp_step is one of the parameters set then the form to add a representative alert is displayed. This only has a creation
     * and a confirm step. As part of this the mp_search param will be submitted which will trigger checking detecting
     * which MP the user is trying to add. This will try the following steps:
     * * try to match the search text against a name
     * * check if the text contains a postcode and if so use that to look up the constituencies
     * * if there's not a postcode then try to match against a constituency name
     *
     * If there is more than one match then these are stored in the constituencies varaible and a choice is
     * presented to the user.
     *
     * The representive alert creation flow will also be entered if an alertsearch or pid parameter is submitted. This
     * is used for handling "create an alert when $rep speaks" links from elsewhere in the site (e.g. an MP page)
     *
     * Other actions such as suspend use a form with only an action value and the alert token if required.
     */
    public function display() {
        global $this_page;
        $this_page = "alert";

        $this->processAction();
        $this->getBasicData();
        $this->checkInput();
        $this->searchForConstituenciesAndMembers();

        if ($this->data['step'] || $this->data['addword']) {
            $this->processStep();
        } elseif (!$this->data['results'] == 'changes-abandoned' && !sizeof($this->data['errors']) && $this->data['submitted'] && ($this->data['keyword'] || $this->data['pid'])) {
            $this->addAlert();
        }

        $this->formatSearchTerms();
        $this->checkForCommonMistakes();
        $this->formatSearchMemberData();
        $this->setUserData();

        return $this->data;
    }

    # This only happens if we have an alert and want to do something to it.
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
                    $this->data['criteria'] = $this->alert->criteria;
                    $this->data['display_criteria'] = \MySociety\TheyWorkForYou\Utility\Alert::prettifyCriteria($this->alert->criteria, $this->alert->ignore_speaker_votes);
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
            } elseif ($action == 'ignore-votes') {
                $success = $this->ignoreVotesAlert($token);
                if ($success) {
                    $this->data['criteria'] = $this->alert->criteria;
                    $display = \MySociety\TheyWorkForYou\Utility\Alert::prettifyCriteria($this->alert->criteria, $this->alert->ignore_speaker_votes);
                    $this->data['display_criteria'] = str_replace('speaks', 'votes', $display);
                    $this->data['results'] = 'alert-ignore-votes';
                }
            } elseif ($action == 'include-votes') {
                $success = $this->includeVotesAlert($token);
                if ($success) {
                    $this->data['criteria'] = $this->alert->criteria;
                    $display = \MySociety\TheyWorkForYou\Utility\Alert::prettifyCriteria($this->alert->criteria, $this->alert->ignore_speaker_votes);
                    $this->data['display_criteria'] = str_replace('speaks', 'votes', $display);
                    $this->data['results'] = 'alert-include-votes';
                }
            } elseif ($action == 'Abandon') {
                $this->data['results'] = 'changes-abandoned';
            }
            if (!$success) {
                $this->data['results'] = 'alert-fail';
            }
        }

        $this->data['alert'] = $alert;
    }

    # Process a screen in the alert creation wizard
    private function processStep() {
        # fetch a list of suggested terms. Need this for the define screen so we can filter out the suggested terms
        # and not show them if the user goes back
        if (($this->data['step'] == 'review' || $this->data['step'] == 'define') && !$this->data['shown_related']) {
            $suggestions = [];
            foreach ($this->data['keywords'] as $word) {
                $terms = $this->alert->get_related_terms($word);
                $terms = array_diff($terms, $this->data['keywords']);
                if ($terms && count($terms)) {
                    $suggestions = array_merge($suggestions, $terms);
                }
            }

            if (count($suggestions) > 0) {
                $this->data['step'] = 'add_vector_related';
                $this->data['suggestions'] = $suggestions;
            }
            # confirm the alert. Handles both creating and editing alerts
        } elseif ($this->data['step'] == 'confirm') {
            $success = true;
            # if there's already an alert assume we are editing it and user must be logged in
            if ($this->data['alert']) {
                $success = $this->updateAlert($this->data['alert']['id'], $this->data);
                if ($success) {
                    # reset all the data to stop anything getting confused
                    $this->data['results'] = 'alert-confirmed';
                    $this->data['step'] = '';
                    $this->data['pid'] = '';
                    $this->data['alertsearch'] = '';
                    $this->data['pc'] = '';
                    $this->data['members'] = false;
                    $this->data['constituencies'] = [];
                    $this->data['member_constituencies'] = [];
                } else {
                    $this->data['results'] = 'alert-fail';
                    $this->data['step'] = 'review';
                }
            } else {
                $success = $this->addAlert();
                $this->data['step'] = '';
            }
        }
    }


    /*
     * set up the data we will be using in processing later steps
     */
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

        $this->data['token'] = get_http_var('t');
        $this->data['step'] = trim(get_http_var("step"));
        $this->data['mp_step'] = trim(get_http_var("mp_step"));
        $this->data['addword'] = trim(get_http_var("addword"));
        $this->data['this_step'] = trim(get_http_var("this_step"));
        $this->data['shown_related'] = get_http_var('shown_related');
        $this->data['match_all'] = get_http_var('match_all') == 'on';
        $this->data['keyword'] = trim(get_http_var("keyword"));
        $this->data['search_section'] = '';
        $this->data['alertsearch'] = trim(get_http_var("alertsearch"));
        $this->data['mp_search'] = trim(get_http_var("mp_search"));
        $this->data['pid'] = trim(get_http_var("pid"));
        $this->data['pc'] = get_http_var('pc');
        $this->data['submitted'] = get_http_var('submitted') || $this->data['pid'] || $this->data['keyword'] || $this->data['step'];
        $this->data['ignore_speaker_votes'] = get_http_var('ignore_speaker_votes');

        if ($this->data['addword'] || $this->data['step']) {
            $alert = $this->alert->check_token($this->data['token']);

            $criteria = '';
            $alert_ignore_speaker_votes = 0;
            if ($alert) {
                $criteria = $alert['criteria'];
                $alert_ignore_speaker_votes = $alert['ignore_speaker_votes'];
            }

            $ignore_speaker_votes = get_http_var('ignore_speaker_votes', $alert_ignore_speaker_votes);
            $this->data['ignore_speaker_votes'] = ($ignore_speaker_votes == 'on' || $ignore_speaker_votes == 1);

            $this->data['alert'] = $alert;

            $this->data['alert_parts'] = \MySociety\TheyWorkForYou\Utility\Alert::prettifyCriteria($criteria, $alert_ignore_speaker_votes, true);

            $existing_rep = '';
            if (isset($this->data['alert_parts']['spokenby'])) {
                $existing_rep = $this->data['alert_parts']['spokenby'][0];
            }

            $existing_section = '';
            if (count($this->data['alert_parts']['sections'])) {
                $existing_section = $this->data['alert_parts']['sections'][0];
            }

            # only want to load this the first time, otherwise we want to default to the form
            # value
            if (!$this->data['this_step'] && $this->data['alert_parts']['match_all']) {
                $this->data['match_all'] = true;
            }

            $words = get_http_var('words', $this->data['alert_parts']['words'], true);

            $this->data['words'] = [];
            $this->data['keywords'] = [];
            foreach ($words as $word) {
                if (trim($word) != '') {
                    $this->data['keywords'][] = $word;
                    $this->data['words'][] = $this->wrap_phrase_in_quotes($word);
                }
            }

            $add_all_related = get_http_var('add_all_related');
            $this->data['add_all_related'] = $add_all_related;
            $this->data['skip_keyword_terms'] = [];

            $selected_related_terms = get_http_var('selected_related_terms', [], true);
            $this->data['selected_related_terms'] = $selected_related_terms;

            if ($this->data['step'] !== 'define') {
                if ($add_all_related) {
                    $this->data['selected_related_terms'] = [];
                    $related_terms = get_http_var('related_terms', [], true);
                    foreach ($related_terms as $term) {
                        $this->data['skip_keyword_terms'][] = $term;
                        $this->data['keywords'][] = $term;
                        $this->data['words'][] = $this->wrap_phrase_in_quotes($term);
                    }
                } else {
                    $this->data['skip_keyword_terms'] = $selected_related_terms;
                    foreach ($selected_related_terms as $term) {
                        $this->data['keywords'][] = $term;
                        $this->data['words'][] = $this->wrap_phrase_in_quotes($term);
                    }
                }
            }
            $this->data['exclusions'] = trim(get_http_var("exclusions", implode(' ', $this->data['alert_parts']['exclusions'])));
            $this->data['representative'] = trim(get_http_var("representative", $existing_rep));

            $this->data['search_section'] = trim(get_http_var("search_section", $existing_section));

            $separator = ' OR ';
            if ($this->data['match_all']) {
                $separator = ' ';
            }
            $this->data['keyword'] = implode($separator, $this->data['words']);
            if ($this->data['exclusions']) {
                $this->data['keyword'] = '(' . $this->data['keyword'] . ') -' . implode(' -', explode(' ', $this->data["exclusions"]));
            }

            $this->data['results'] = '';

            $this->getSearchSections();
        } elseif ($this->data['mp_step'] == 'mp_alert') {
            $alert = $this->alert->check_token($this->data['token']);
            if ($alert) {
                $ignore_speaker_votes = get_http_var('ignore_speaker_votes', $alert['ignore_speaker_votes']);
                $this->data['ignore_speaker_votes'] = ($ignore_speaker_votes == 'on' || $ignore_speaker_votes == 1);

                $existing_rep = '';
                if (isset($alert['criteria'])) {
                    $alert_parts = \MySociety\TheyWorkForYou\Utility\Alert::prettifyCriteria($alert['criteria'], $alert['ignore_speaker_votes'], true);
                    $existing_rep = $alert_parts['spokenby'][0];
                    $this->data['pid'] = $alert_parts['pid'];
                }
                $this->data['keyword'] = get_http_var('mp_search', $existing_rep);
            } else {
                $this->data['ignore_speaker_votes'] = get_http_var('ignore_speaker_votes');
            }
        }

        $this->data['sign'] = get_http_var('sign');
        $this->data['site'] = get_http_var('site');
        $this->data['message'] = '';

        $ACTIONURL = new \MySociety\TheyWorkForYou\Url($this_page);
        $ACTIONURL->reset();
        $this->data['actionurl'] = $ACTIONURL->generate();

    }

    private function wrap_phrase_in_quotes($phrase) {
        if (strpos($phrase, ' ') > 0) {
            $phrase = '"' . trim($phrase, '"') . '"';
        }

        return $phrase;
    }

    /*
     * Check if the current alert has returned any results recently
     */
    private function getRecentResults($text) {
        global $SEARCHENGINE;
        $today = date_create();
        $one_week_ago = date_create('7 days ago');
        $restriction = date_format($one_week_ago, 'd/m/Y') . '..' . date_format($today, 'd/m/Y');
        $last_week_count = 0;
        $se = new \SEARCHENGINE($text . ' ' . $restriction);
        $last_week_count = $se->run_count(0, 10);
        $se = new \SEARCHENGINE($text);
        $count = $se->run_count(0, 10, 'date');
        $last_mention = null;
        if ($count > 0) {
            $se->run_search(0, 1, 'date');
            $gid = $se->get_gids()[0];

            $q = ['gid_to' => $gid];
            do {
                $gid = $q['gid_to'];
                $q = $this->db->query("SELECT gid_to FROM gidredirect WHERE gid_from = :gid", [':gid' => $gid])->first();
            } while ($q);

            $q = $this->db->query("SELECT hansard.gid, hansard.hdate
            FROM hansard
            WHERE hansard.gid = :gid", [':gid' => $gid])->first();

            if ($q) {
                $last_mention_date = date_create($q['hdate']);
                $last_mention = date_format($last_mention_date, 'd M Y'); //$se->get_gids()[0];
            }
        }
        return [
            "last_mention" => $last_mention,
            "last_week_count" => $last_week_count,
            "all_time_count" => $count,
        ];
    }

    private function getSearchSections() {
        $this->data['sections'] = [];
        if ($this->data['search_section']) {
            foreach (explode(' ', $this->data['search_section']) as $section) {
                $this->data['sections'][] = \MySociety\TheyWorkForYou\Utility\Alert::sectionToTitle($section);
            }
        }
    }

    private function updateAlert($token) {
        $success = $this->alert->update($token, $this->data);
        return $success;
    }

    private function checkInput() {
        global $SEARCHENGINE;

        $errors = [];

        # these are the initial screens and so cannot have any errors as we've not submitted
        if (!$this->data['submitted'] || $this->data['step'] == 'define' || $this->data['mp_step'] == 'mp_alert') {
            $this->data['errors'] = $errors;
            return;
        }

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
        if ($this->data['mp_search']) {
            $text = $this->data['mp_search'];
        }
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
        if ($this->data['results'] == 'changes-abandoned') {
            $this->data['members'] = false;
            return;
        }

        $text = $this->data['alertsearch'];
        if ($this->data['mp_search']) {
            $text = $this->data['mp_search'];
        }
        $errors = [];
        if ($text != '') {
            //$members_from_pids = array_values(\MySociety\TheyWorkForYou\Utility\Search::membersForIDs($this->data['alertsearch']));
            $members_from_names = [];
            $names_from_pids = array_values(\MySociety\TheyWorkForYou\Utility\Search::speakerNamesForIDs($text));
            foreach ($names_from_pids as $name) {
                $members_from_names = array_merge($members_from_names, \MySociety\TheyWorkForYou\Utility\Search::searchMemberDbLookupWithNames($name));
            }
            $members_from_words = \MySociety\TheyWorkForYou\Utility\Search::searchMemberDbLookupWithNames($text, true);
            $this->data['members'] = array_merge($members_from_words, $members_from_names);
            [$this->data['constituencies'], $this->data['valid_postcode']] = \MySociety\TheyWorkForYou\Utility\Search::searchConstituenciesByQuery($text, false);
            # if there's no postcode match then constituencies will only have a list of names of westminster constituencies
            # so do a lookup using the member table to get members in all constituency types
            if (!$this->data['valid_postcode']) {
                $this->data['member_constituencies'] = \MySociety\TheyWorkForYou\Utility\Search::searchMembersByConstituency($text);
            }
            $member_count = count($this->data['members']);
            $cons_count = count($this->data['constituencies']);
            $mem_cons_count = count($this->data['member_constituencies']);
            if ($member_count == 0 && $cons_count == 0 && $mem_cons_count == 0) {
                $errors["representative"] = gettext("No matching representative found");
            }
        } elseif ($this->data['pid']) {
            $MEMBER = new \MySociety\TheyWorkForYou\Member(['person_id' => $this->data['pid']]);
            $this->data['members'] = [[
                "person_id" => $MEMBER->person_id,
                "given_name" => $MEMBER->given_name,
                "family_name" => $MEMBER->family_name,
                "house" => $MEMBER->house_disp,
                "title" => $MEMBER->title,
                "lordofname" => $MEMBER->lordofname,
                "constituency" => $MEMBER->constituency,
            ]];
        } elseif (isset($this->data['representative']) && $this->data['representative'] != '') {
            $this->data['members'] = \MySociety\TheyWorkForYou\Utility\Search::searchMemberDbLookupWithNames($this->data['representative'], true);

            $member_count = count($this->data['members']);
            if ($member_count == 0) {
                $errors["representative"] = gettext("No matching representative found");
            } elseif ($member_count > 1) {
                $errors["representative"] = gettext("Multiple matching representatives found, please select one.");
            } else {
                $this->data['pid'] = $this->data['members'][0]['person_id'];
            }
        } else {
            $this->data['members'] = [];
        }


        if (isset($this->data['constituencies'])) {
            $cons = [];
            foreach ($this->data['constituencies'] as $type => $constituency) {
                try {
                    $house = \MySociety\TheyWorkForYou\Utility\Postcode::mapitTypeToHouse($type);
                    // regional list reps for wales and scotland
                    if ($type == 'SPE' || $type == 'WAE') {
                        $db = new \ParlDB();
                        $q = $db->query("SELECT person_id FROM member WHERE constituency = :constituency AND house = :house and left_reason = 'still_in_office'", [':constituency' => $constituency, ':house' => $house]);
                        foreach ($q as $row) {
                            $MEMBER = new \MySociety\TheyWorkForYou\Member(['person_id' => $row['person_id'], 'house' => $house]);
                            $cons[] = [ 'member' => $MEMBER, 'constituency' => $constituency ];
                        }

                    } else {
                        $MEMBER = new \MySociety\TheyWorkForYou\Member(['constituency' => $constituency, 'house' => $house]);
                        $cons[] = [ 'member' => $MEMBER, 'constituency' => $constituency ];
                    }
                } catch (\MySociety\TheyWorkForYou\MemberException $e) {
                    // do nothing
                }
            }
            $this->data['constituencies'] = $cons;
        }

        if (isset($this->data['member_constituencies'])) {
            $cons = [];
            foreach ($this->data['member_constituencies'] as $pid) {
                try {
                    $MEMBER = new \MySociety\TheyWorkForYou\Member(['person_id' => $pid]);
                    $cons[] = [ 'member' => $MEMBER, 'constituency' => $MEMBER->constituency ];
                } catch (\MySociety\TheyWorkForYou\MemberException $e) {
                    // do nothing
                }
            }
            $this->data['constituencies'] = $cons;
        }

        if ($this->data['alertsearch'] && !$this->data['mp_step'] && ($this->data['pid'] || $this->data['members'] || $this->data['constituencies'])) {
            $this->data['mp_step'] = 'mp_alert';
            $this->data['mp_search'] = $this->data['alertsearch'];
            $this->data['alertsearch'] = '';
        }

        if (count($this->data["errors"]) > 0) {
            $this->data["errors"] = array_merge($this->data["errors"], $errors);
        } else {
            $this->data["errors"] = $errors;
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
            $this->data['step'] = '';
            $this->data['mp_step'] = '';
            $result = 'alert-added';
        } elseif ($success > 0) {
            $this->data['step'] = '';
            $this->data['mp_step'] = '';
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
        $this->data['criteria'] = $this->alert->criteria;
        $this->data['display_criteria'] = \MySociety\TheyWorkForYou\Utility\Alert::prettifyCriteria($this->alert->criteria, $this->alert->ignore_speaker_votes);
    }


    private function formatSearchTerms() {
        if ($this->data['alertsearch']) {
            $this->data['alertsearch_pretty'] = \MySociety\TheyWorkForYou\Utility\Alert::prettifyCriteria($this->data['alertsearch']);
            $this->data['search_text'] = $this->data['alertsearch'];
        } elseif ($this->data['mp_search']) {
            $this->data['search_text'] = $this->data['mp_search'];
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

                $MEMBER = new \MySociety\TheyWorkForYou\Member(['postcode' => $postcode]);
                // move the postcode to the front just to be tidy
                $tidy_alertsearch = $postcode . " " . trim(str_replace("$postcode", "", $this->data['alertsearch']));
                $alertsearch_display = str_replace("$postcode ", "", $tidy_alertsearch);

                $this->data['member_alertsearch'] = str_replace("$postcode", "speaker:" . $MEMBER->person_id, $tidy_alertsearch);
                $this->data['member_displaysearch'] = $alertsearch_display;
                $this->data['member'] = $MEMBER;

                if (isset($this->data['mistakes']['postcode_and'])) {
                    $constituencies = \MySociety\TheyWorkForYou\Utility\Postcode::postcodeToConstituencies($postcode);
                    if (isset($constituencies['SPC'])) {
                        $MEMBER = new \MySociety\TheyWorkForYou\Member(['constituency' => $constituencies['SPC'], 'house' => HOUSE_TYPE_SCOTLAND]);
                        $this->data['scottish_alertsearch'] = str_replace("$postcode", "speaker:" . $MEMBER->person_id, $tidy_alertsearch);
                        $this->data['scottish_member'] = $MEMBER;
                    } elseif (isset($constituencies['WAC'])) {
                        $MEMBER = new \MySociety\TheyWorkForYou\Member(['constituency' => $constituencies['WAC'], 'house' => HOUSE_TYPE_WALES]);
                        $this->data['welsh_alertsearch'] = str_replace("$postcode", "speaker:" . $MEMBER->person_id, $tidy_alertsearch);
                        $this->data['welsh_member'] = $MEMBER;
                    }
                }
            } catch (\MySociety\TheyWorkForYou\MemberException $e) {
                $this->data['member_error'] = 1;
            }
        }

        if ($this->data['pid']) {
            $MEMBER = new \MySociety\TheyWorkForYou\Member(['person_id' => $this->data['pid']]);
            $this->data['pid_member'] = $MEMBER;
        }

        if ($this->data['keyword']) {
            $this->data['display_keyword'] = \MySociety\TheyWorkForYou\Utility\Alert::prettifyCriteria($this->data['keyword']);
        }
    }

    /*
     * This makes sure that the $this->data makes sense once all the other steps have been completed
     */
    private function setUserData() {
        if (!isset($this->data['criteria'])) {
            $criteria = $this->data['keyword'];
            if ($criteria) {
                if (!$this->data['match_all']) {
                    $has_or = strpos($criteria, ' OR ') !== false;
                    $missing_braces = strpos($criteria, '(') === false;

                    if ($has_or && $missing_braces) {
                        $criteria = "($criteria)";
                    }
                }
                if ($this->data['search_section']) {
                    $criteria .= " section:" . $this->data['search_section'];
                }
                if ($this->data['pid']) {
                    $criteria .= " speaker:" . $this->data['pid'];
                }
                $this->data['search_results']  = $this->getRecentResults($criteria);
            }

            $this->data['criteria'] = $criteria;
            $this->data['display_criteria'] = \MySociety\TheyWorkForYou\Utility\Alert::prettifyCriteria($criteria);
        }
        if ($this->data['results'] == 'changes-abandoned') {
            $this->data['members'] = false;
            $this->data['alertsearch'] = '';
        }

        if ($this->data['alertsearch'] && !(isset($this->data['mistakes']['postcode_and']) || $this->data['members'] || $this->data['pid'])) {
            $this->data['step'] = 'define';
            $this->data['words'] = [$this->data['alertsearch']];
            $this->data['keywords'] = [$this->data['alertsearch']];
            $this->data['exclusions'] = '';
            $this->data['representative'] = '';
        } elseif ($this->data['alertsearch'] && ($this->data['members'] || $this->data['pid'])) {
            $this->data['mp_step'] = 'mp_alert';
            $this->data['mp_search'] = [$this->data['alertsearch']];
        } elseif ($this->data['members'] && $this->data['mp_step'] == 'mp_search' && count($this->data['errors']) == 0) {
            $this->data['mp_step'] = '';
        }

        $this->data['current_mp'] = false;
        $this->data['alerts'] = [];
        $this->data['keyword_alerts'] = [];
        $this->data['speaker_alerts'] = [];
        $this->data['spoken_alerts'] = [];
        $this->data['own_member_alerts'] = [];
        $this->data['all_keywords'] = [];
        $this->data['own_mp_criteria'] = '';
        $own_mp_criteria = '';

        if ($this->data['email_verified']) {
            if ($this->user->postcode()) {
                $current_mp = new \MySociety\TheyWorkForYou\Member(['postcode' => $this->user->postcode()]);
                if ($current_mp_alert = !$this->alert->fetch_by_mp($this->data['email'], $current_mp->person_id())) {
                    $this->data['current_mp'] = $current_mp;
                    $own_mp_criteria = sprintf('speaker:%s', $current_mp->person_id());
                }
                $own_mp_criteria = $current_mp->full_name();
                $this->data['own_mp_criteria'] = $own_mp_criteria;
            }
            $this->data['alerts'] = \MySociety\TheyWorkForYou\Utility\Alert::forUser($this->data['email']);
            foreach ($this->data['alerts'] as $alert) {
                if (array_key_exists('spokenby', $alert) and sizeof($alert['spokenby']) == 1 and $alert['spokenby'][0] == $own_mp_criteria) {
                    $this->data['own_member_alerts'][] = $alert;
                } elseif (array_key_exists('spokenby', $alert)) {
                    if (!array_key_exists($alert['spokenby'][0], $this->data['spoken_alerts'])) {
                        $this->data['spoken_alerts'][$alert['spokenby'][0]] = [];
                    }
                    $this->data['spoken_alerts'][$alert['spokenby'][0]][] = $alert;
                }
            }
            foreach ($this->data['alerts'] as $alert) {
                $term = implode(' ', $alert['words']);
                $add = true;
                if (array_key_exists('spokenby', $alert)) {
                    $add = false;
                } elseif (array_key_exists($term, $this->data['spoken_alerts'])) {
                    $add = false;
                    $this->data['all_keywords'][] = $term;
                    $this->data['spoken_alerts'][$term][] = $alert;
                } elseif ($term == $own_mp_criteria) {
                    $add = false;
                    $this->data['all_keywords'][] = $term;
                    $this->data['own_member_alerts'][] = $alert;
                } elseif (\MySociety\TheyWorkForYou\Utility\Search::searchMemberDbLookupWithNames($term, true)) {
                    if (!array_key_exists($term, $this->data['spoken_alerts'])) {
                        $this->data['spoken_alerts'][$term] = [];
                    }
                    $add = false;
                    # need to add this to make it consistent so the front end know where to get the name
                    $alert['spokenby'] = [$term];
                    $this->data['all_keywords'][] = $term;
                    $this->data['spoken_alerts'][$term][] = $alert;
                }
                if ($add) {
                    $this->data['all_keywords'][] = $term;
                    $alert['search_results']  = $this->getRecentResults($alert["criteria"]);
                    $this->data['keyword_alerts'][] = $alert;
                }
            }
        } else {
            if ($this->data['alertsearch'] && $this->data['pc']) {
                $this->data['mp_step'] = 'mp_alert';
            }
        }
        if (count($this->data['alerts'])) {
            $this->data['delete_token'] = $this->data['alerts'][0]['token'];
        }
        if ($this->data['addword'] != '' || ($this->data['step'] && count($this->data['errors']) > 0)) {
            $this->data["step"] = get_http_var('this_step');
        } elseif ($this->data['mp_step'] && count($this->data['errors']) > 0) {
            $this->data["mp_step"] = 'mp_alert';
        } else {
            $this->data['this_step'] = '';
        }

        $this->data["search_term"] = $this->data['alertsearch'];
        if ($this->data['mp_search']) {
            $this->data["search_term"] = $this->data['mp_search'];
        }
    }
}
