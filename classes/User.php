<?php
/**
 * User Class
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou;

/**
 * User
 */

function calculateOptinValue($optin_service, $optin_stream, $optin_org) {
    // combine three booleans into a single integer to store in the database
    // +1 = optin_service
    // +2 = optin_stream
    // +4 = optin_org

    $value = 0;

    $value += $optin_service ? 1 : 0;
    $value += $optin_stream ? 2 : 0;
    $value += $optin_org ? 4 : 0;

    return $value;
}

function extractOptinValues($value) {
    // convert an integer into three seperate optin values ('Yes', 'No')
    return [
        'optin_service' => ($value & 1) ? "Yes" : "No",
        'optin_stream' => ($value & 2) ? "Yes" : "No",
        'optin_org' => ($value & 4) ? "Yes" : "No",
    ];
}

class User {
    public function getUserDetails($user_id = false) {
        global $THEUSER;

        $user = $THEUSER;
        if ($user_id && $user_id != $THEUSER->user_id()) {
            $user = new \USER();
            $valid = $user->init($user_id);

            if (!$valid || !$user->confirmed || $user->deleted()) {
                return ['error' => 'User does not exist'];
            }
        }

        $data = [];
        $data['firstname'] = $user->firstname();
        $data['lastname'] = $user->lastname();
        $data['name'] = $user->firstname() . " " . $user->lastname();
        $data['url'] = $user->url();
        $data['email'] = $user->email();
        $optin_values = extractOptinValues($user->optin());
        $data['optin_service'] = $optin_values['optin_service'];
        $data['optin_stream'] = $optin_values['optin_stream'];
        $data['optin_org'] = $optin_values['optin_org'];
        $data['postcode']	= $user->postcode();
        $data['website']	= $user->url();
        $data['registrationtime']	= $user->registrationtime();
        $data['status'] = $user->status();
        $data["deleted"] = $user->deleted();
        $data["confirmed"] = $user->confirmed();
        $data["status"] = $user->status();
        $data["facebook_id"] = $user->facebook_id();
        $data['facebook_user'] = $user->facebook_user();
        $data['can_annotate'] = $user->can_annotate();
        $data['organisation'] = $user->organisation();
        return $data;
    }

    public function getUpdateDetails($this_page, $user) {
        $details = [];

        if ($user->facebook_user) {
            $details = $this->getUserDetails();
            $details["password"] = '';
        } else {
            $details["firstname"] = trim(get_http_var("firstname"));
            $details["lastname"] = trim(get_http_var("lastname"));

            $details["password"] = trim(get_http_var("password"));
            $details["password2"] = trim(get_http_var("password2"));

            $details["email"] = trim(get_http_var("em"));

            $details["url"] = trim(get_http_var("url"));

            $optin_service = get_http_var("optin_service") == "true" ? true : false;
            $optin_stream = get_http_var("optin_stream") == "true" ? true : false;
            $optin_org = get_http_var("optin_org") == "true" ? true : false;

            $details["optin"] = calculateOptinValue($optin_service, $optin_stream, $optin_org);

            if (get_http_var("remember") != "") {
                $remember = get_http_var("remember");
                $details["remember"] = $remember[0] == "true" ? true : false;
            }

            if ($details['url'] != '' && !preg_match('/^http/', $details['url'])) {
                $details['url'] = 'https://' . $details['url'];
            }

            # these are used when displaying user details
            $details['name'] = $details["firstname"] . " " . $details["lastname"];
            $details["website"] = $details["url"];
            $details['registrationtime'] = $user->registrationtime();
            $details['status'] = $user->status();
        }

        $details['mp_alert'] = get_http_var('mp_alert') == 'true' ? true : false;
        $details["postcode"] = trim(get_http_var("postcode"));

        if ($this_page == "otheruseredit") {
            $details["user_id"] = trim(get_http_var("u"));
            $details["status"] = trim(get_http_var("status"));

            if (get_http_var("deleted") != "") {
                $deleted = get_http_var("deleted");
                $details["deleted"] = $deleted[0] == "true" ? true : false;
            } else {
                $details['deleted'] = false;
            }

            if (get_http_var("confirmed") != "") {
                $confirmed = get_http_var("confirmed");
                $details["confirmed"] = $confirmed[0] == "true" ? true : false;
            } else {
                $details['confirmed'] = false;
            }
        }

        return $details;
    }

    public function checkUpdateDetails($details) {
        global $THEUSER, $this_page;

        $errors = [];

        // Check each of the things the user has input.
        // If there is a problem with any of them, set an entry in the $errors array.
        // This will then be used to (a) indicate there were errors and (b) display
        // error messages when we show the form again.

        // facebook user's can only change their postcode so skip all this
        if (!isset($details['facebook_user'])) {
            // Check first name.
            if ($details["firstname"] == "") {
                $errors["firstname"] = "Please enter a first name";
            }

            // They don't need a last name. In case Madonna joins.

            // Check email address is valid and unique.
            if ($this_page == "otheruseredit" || $this_page == 'userjoin' || $this_page == 'useredit') {
                if ($details["email"] == "") {
                    $errors["email"] = "Please enter an email address";

                } elseif (!validate_email($details["email"])) {
                    // validate_email() is in includes/utilities.php
                    $errors["email"] = "Please enter a valid email address";

                } else {

                    $USER = new \USER();
                    $id_of_user_with_this_addresss = $USER->email_exists($details["email"], true);

                    if ($this_page == "useredit" &&
                        get_http_var("u") == "" &&
                        $THEUSER->isloggedin()) {
                        // User is updating their own info.
                        // Check no one else has this email.

                        if ($id_of_user_with_this_addresss &&
                            $id_of_user_with_this_addresss != $THEUSER->user_id()) {
                            $errors["email"] = "Someone else has already joined with this email address";
                        }

                    } else {
                        // User is joining. Check no one is already here with this email.
                        if ($this_page == "userjoin" && $id_of_user_with_this_addresss) {
                            $errors["email"] = "There is already a user with this email address";
                        }
                    }
                }
            }

            // Check passwords.
            if ($this_page == "userjoin") {

                // Only *must* enter a password if they're joining.
                if ($details["password"] == "") {
                    $errors["password"] = gettext("Please enter a password");

                } elseif (strlen($details["password"]) < 6) {
                    $errors["password"] = gettext("Please enter at least six characters");
                }

                if ($details["password2"] == "") {
                    $errors["password2"] = gettext("Please enter a password again");
                }

                if ($details["password"] != "" && $details["password2"] != "" && $details["password"] != $details["password2"]) {
                    $errors["password"] = gettext("The passwords did not match. Please try again.");
                }

            } else {

                // Update details pages.

                if ($details["password"] != "" && strlen($details["password"]) < 6) {
                    $errors["password"] = gettext("Please enter at least six characters");
                }

                if ($details["password"] != $details["password2"]) {
                    $errors["password"] = gettext("The passwords did not match. Please try again.");
                }
            }
        }

        // Check postcode (which is not a compulsory field).
        if ($details["postcode"] != "") {
            if (!validate_postcode($details["postcode"])) {
                $errors["postcode"] = gettext("Sorry, this isn't a valid UK postcode.");
            } else {
                try {
                    new \MySociety\TheyWorkForYou\Member([
                        'postcode' => $details['postcode'],
                        'house' => HOUSE_TYPE_COMMONS,
                    ]);
                } catch (MemberException $e) {
                    $errors["postcode"] = gettext("Sorry, we could not find an MP for that postcode.");
                }
            }
        }

        // No checking of URL.


        if ($this_page == "otheruseredit") {

            // We're editing another user's info.

            // Could check status here...?


        }

        // Send the array of any errors back...
        return $errors;
    }

    public function update($details) {
        global $THEUSER, $this_page, $PAGE;

        $results = [];
        // There were no errors when the edit user form was submitted,
        // so make the changes in the DB.

        // Who are we updating? $THEUSER or someone else?
        if ($this_page == "otheruseredit") {
            $who = 'the user&rsquo;s';
            $success = $THEUSER->update_other_user($details);
        } else {
            $who = 'your';
            $success = $THEUSER->update_self($details);
        }


        if ($success) {
            // No errors, all updated, show results.

            if ($this_page == 'otheruseredit') {
                $this_page = "userview";
            } else {
                $this_page = "userviewself";
            }

            if ($details['email'] != $THEUSER->email()) {
                $results['email_changed'] = true;
            }


        } else {
            $results['errors'] = ["db" => "Sorry, we were unable to update $who details. Please <a href=\"mailto:" . str_replace('@', '&#64;', CONTACTEMAIL) . "\">let us know</a> what you were trying to change. Thanks."];
        }

        return $results;
    }

    public function add($details) {
        global $THEUSER, $PAGE, $this_page;


        // If this goes well, the user will have their data
        // added to the database and a confirmation email
        // will be sent to them.
        $success = $THEUSER->add($details);

        $errors = [];

        if (!$success) {
            $errors["db"] = "Sorry, we were unable to create an account for you. Please <a href=\"mailto:" . str_replace('@', '&#64;', CONTACTEMAIL) . "\">let us know</a>. Thanks.";
        }

        return $errors;
    }

    public function getRep($cons_type, $mp_house) {
        global $THEUSER;
        if (!$THEUSER->has_postcode()) {
            return [];
        }

        // User is logged in and has a postcode, or not logged in with a cookied postcode.

        // (We don't allow the user to search for a postcode if they
        // already have one set in their prefs.)

        // this is for people who have e.g. an English postcode looking at the
        // Scottish homepage
        try {
            $constituencies = \MySociety\TheyWorkForYou\Utility\Postcode::postcodeToConstituencies($THEUSER->postcode());
            if (isset($constituencies[$cons_type])) {
                $constituency = $constituencies[$cons_type];
                $MEMBER = new Member(['constituency' => $constituency, 'house' => $mp_house]);
            }
        } catch (MemberException $e) {
            return [];
        }

        if (isset($MEMBER) && $MEMBER->valid) {
            return $this->constructMPData($MEMBER, $THEUSER, $mp_house);
        }

        return [];
    }

    private function constructMPData($member, $user, $mp_house) {
        $mp_data = [];
        $mp_data['name'] = $member->full_name();
        $mp_data['party'] = $member->party();
        $mp_data['constituency'] = $member->constituency();
        $left_house = $member->left_house();
        $mp_data['former'] = '';
        if ($left_house[$mp_house]['date'] != '9999-12-31') {
            $mp_data['former'] = 'former';
        }
        $mp_data['postcode'] = $user->postcode();
        $mp_data['mp_url'] = $member->url();
        $mp_data['change_url'] = $this->getPostCodeChangeURL();

        $image = $member->image();
        $mp_data['image'] = $image['url'];

        return $mp_data;
    }

    public function getRegionalReps($cons_type, $mp_house) {
        global $THEUSER;

        $mreg = [];
        if ($THEUSER->isloggedin() && $THEUSER->postcode() != '' || $THEUSER->postcode_is_set()) {
            $reps = \MySociety\TheyWorkForYou\Member::getRegionalList($THEUSER->postcode, $mp_house, $cons_type);
            foreach ($reps as $rep) {
                $member = new \MySociety\TheyWorkForYou\Member(['person_id' => $rep['person_id']]);
                $mreg[$rep['person_id']] = $this->constructMPData($member, $THEUSER, $mp_house);
            }
        }

        return $mreg;
    }

    public function getPostCodeChangeURL() {
        global $THEUSER;
        $CHANGEURL = new Url('userchangepc');
        if ($THEUSER->isloggedin()) {
            $CHANGEURL = new Url('useredit');
        }

        return $CHANGEURL->generate();
    }


}
