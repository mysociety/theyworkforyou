<?php
/**
 * TheUser Class
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou;

/**
 * The Current User
 *
 * Handles all the login/out functionality and checking for the user
 * who is using the site right NOW. Yes, him, over there.
 */

class TheUser extends User {

    // This will become true if all goes well...
    public $loggedin = false;


    public function __construct() {
        // This function is run automatically when a THEUSER
        // object is instantiated.

        $this->db = new \ParlDB;

        // We look at the user's cookie and see if it's valid.
        // If so, we're going to log them in.

        // A user's cookie is of the form:
        // 123.blahblahblah
        // Where '123' is a user id, and 'blahblahblah' is an md5 hash of the
        // encrypted password we've stored in the db.
        // (Maybe we could just put the encrypted pw in the cookie and md5ing
        // it is overkill? Whatever, it works.)

        $cookie = get_cookie_var("epuser_id"); // In includes/utility.php.

        if ($cookie == '') {
            twfy_debug("THEUSER init FAILED", "No cookie set");
            $this->loggedin = false;

        } elseif (preg_match("/([[:alnum:]]*)\.([[:alnum:]]*)/", $cookie, $matches)) {

            if (is_numeric($matches[1])) {

                $success = $this->init($matches[1]);

                if ($success) {
                    // We got all the user's data from the DB.

                    // But we need to check the password before we log them in.
                    // And make sure the user hasn't been "deleted".

                    if (md5($this->password()) == $matches[2] && $this->deleted() == false) {
                        // The correct password is in the cookie,
                        // and the user isn't deleted, so set the user to be logged in.

                        // This would be an appropriate place to call other functions
                        // that might set user info that only a logged-in user is going
                        // to need. Their preferences and saved things or something.


                        twfy_debug ("THEUSER init SUCCEEDED", "setting as logged in");
                        $this->loggedin = true;

                    } elseif (md5 ($this->password()) != $matches[2]) {
                        twfy_debug ("THEUSER init FAILED", "Password doesn't match cookie");
                        $this->loggedin = false;
                    } else {
                        twfy_debug ("THEUSER init FAILED", "User is deleted");
                        $this->loggedin = false;
                    }

                } else {
                    twfy_debug ("THEUSER init FAILED", "didn't get 1 row from db");
                    $this->loggedin = false;
                }

            } else {
                twfy_debug ("THEUSER init FAILED", "cookie's user_id is not numeric");
                $this->loggedin = false;
            }

        } else {
            twfy_debug ("THEUSER init FAILED", "cookie is not of the correct form");
            $this->loggedin = false;
        }

        // If a user is logged in they *might* have set their own postcode.
        // If they aren't logged in, or they haven't set one, then we may
        // have set a postcode for them when they searched for their MP.
        // If so, we'll use that as $this->postcode.
        if ($this->postcode == '') {
            if (get_cookie_var(POSTCODE_COOKIE) != '') {
                $pc = get_cookie_var(POSTCODE_COOKIE);

                $this->set_postcode_cookie($pc);
            }
        }

        $this->update_lastvisit();

    }

    public function update_lastvisit() {

        if ($this->isloggedin()) {
            // Set last_visit to now.
            $date_now = gmdate("Y-m-d H:i:s");
            $q = $this->db->query("UPDATE users
                            SET     lastvisit = '$date_now'
                            WHERE   user_id = '" . $this->user_id() . "'");

            $this->lastvisit = $date_now;
        }
    }

    /**
     * @deprecated It's better to call $this->isloggedin() if you want to check the log in status.
     */

    public function loggedin() { return $this->loggedin; }

    /**
     * Check if the user is logged in or not.
     */

    public function isloggedin() {

        if ($this->loggedin()) {
            twfy_debug("THEUSER", "isloggedin: true");

            return true;
        } else {
            twfy_debug("THEUSER", "isloggedin: false");

            return false;
        }
    }

    /**
     * Check to see if the user's login form details are OK.
     */

    public function isvalid($email, $userenteredpassword) {
        // Returns true if this email and plaintext password match a user in the db.
        // If false returns an array of form error messages.

        // We use this on the log in page to check if the details the user entered
        // are correct. We can then continue with logging the user in (taking into
        // account their cookie remembering settings etc) with $this->login().

        // This error string is shared between both email and password errors to
        // prevent leaking of account existence.

        $error_string = 'There is no user registered with an email of ' . _htmlentities($email) . ', or the given password is incorrect. If you are subscribed to email alerts, you are not necessarily registered on the website. If you register, you will be able to manage your email alerts, as well as leave annotations.';

        $q = $this->db->query("SELECT user_id, password, deleted, confirmed FROM users WHERE email = :email", array(':email' => $email));

        if ($q->rows() == 1) {
            // OK.
            // The password in the DB is crypted.
            $dbpassword = $q->field(0,"password");
            if (crypt($userenteredpassword, $dbpassword) == $dbpassword) {
                $this->user_id  = $q->field(0,"user_id");
                $this->password = $dbpassword;
                // We'll need these when we're going to log in.
                $this->deleted  = $q->field(0,"deleted") == 1 ? true : false;
                $this->confirmed = $q->field(0,"confirmed") == 1 ? true : false;

                return true;

            } else {
                // Failed.
                return array ("invalidemail" => $error_string);

            }

        } else {
            // Failed.
            return array ("invalidemail" => $error_string);
        }

    }

    /**
     * Log the user in.
     */

    public function login($returl="", $expire) {

        // This is used to log the user in. Duh.
        // You should already have checked the user's email and password using
        // $this->isvalid()
        // That will have set $this->user_id and $this->password, allowing the
        // login to proceed...

        // $expire is either 'session' or 'never' - for the cookie.

        // $returl is the URL to redirect the user to after log in, generally the
        // page they were on before. But if it doesn't exist, they'll just go to
        // the front page.
        global $PAGE;

        if ($returl == "") {
            $URL = new Url("home");
            $returl = $URL->generate();
        }

        // Various checks about the user - if they fail, we exit.
        if ($this->user_id() == "" || $this->password == "") {
            $PAGE->error_message ("We don't have the user_id or password to make the cookie.", true);

            return;
        } elseif ($this->deleted) {
            $PAGE->error_message ("This user has been deleted.", true);

            return;
        } elseif (!$this->confirmed) {
            $PAGE->error_message ("You have not yet confirmed your account by clicking the link in the confirmation email we sent to you. If you don't have the email, you can <a href='/user/login/?resend=" . $this->user_id() . "'>have it resent</a>. If it still doesn't arrive, get in touch.", true);

            return;
        }

        // Unset any existing postcode cookie.
        // This will be the postcode the user set for themselves as a non-logged-in
        // user. We don't want it hanging around as it causes confusion.
        $this->unset_postcode_cookie();

        // Reminder: $this->password is actually a crypted version of the plaintext pw.
        $cookie = $this->user_id() . "." . md5 ($this->password());

        if ($expire == 'never') {
            header("Location: $returl");
            setcookie('epuser_id', $cookie, time()+86400*365*20, '/', COOKIEDOMAIN);
        } else {
            header("Location: $returl");
            setcookie('epuser_id', $cookie, 0, '/', COOKIEDOMAIN);
        }
    }

    /**
     * Log the user out.
     */

    public function logout($returl) {

        // $returl is the URL to redirect the user to after log in, generally the
        // page they were on before. But if it doesn't exist, they'll just go to
        // the front page.

        if ($returl == '') {
            $URL = new Url("home");
            $returl = $URL->generate();
        }

        // get_cookie_var() is in includes/utility.php
        if (get_cookie_var("epuser_id") != "") {
            // They're logged in, so set the cookie to empty.
            header("Location: $returl");
            setcookie('epuser_id', '', time() - 86400, '/', COOKIEDOMAIN);
        }
    }

    public function confirm_email($token, $redirect=true) {
        $arg = '';
        if (strstr($token, '::')) $arg = '::';
        if (strstr($token, '-')) $arg = '-';
        list($user_id, $registrationtoken) = explode($arg, $token);

        if (!is_numeric($user_id) || $registrationtoken == '') {
            return false;
        }
        $q = $this->db->query("SELECT expires, data
            FROM    tokens
            WHERE   token = :token
            AND   type = 'E'
        ", array (':token' => $registrationtoken));

        if ($q->rows() == 1) {
            $expires = $q->field(0, 'expires');
            $expire_time = strtotime($expires);
            if ( $expire_time < time() ) {
                global $PAGE;
                if ($PAGE && $redirect) {
                    $PAGE->error_message ("Sorry, that token seems to have expired");
                }

                return false;
            }

            list( $user_id, $email ) = explode('::', $q->field(0, 'data'));

            // if we are logged in as someone else don't change the email
            if ( $this->user_id() != 0 && $this->user_id() != $user_id ) {
                return false;
            }

            // if the user isn't logged in then try and load the
            // details
            if ($this->user_id() == 0 && !$this->init($user_id)) {
                return false;
            }

            $details = array(
                'email' => $email,
                'firstname' => $this->firstname(),
                'lastname' => $this->lastname(),
                'postcode' => $this->postcode(),
                'url' => $this->url(),
                'optin' => $this->optin(),
                'user_id' => $user_id,
                'emailpublic' => $this->emailpublic()
            );
            $ret = $this->_update($details);

            if ($ret) {
                // and remove the token to be tidy
                $q = $this->db->query("DELETE
                    FROM    tokens
                    WHERE   token = :token
                    AND   type = 'E'
                ", array(':token' => $registrationtoken));

                $this->email = $email;
                $URL = new Url('userconfirmed');
                $URL->insert(array('email'=>'t'));
                $redirecturl = $URL->generate();
                if ($redirect) {
                    $this->login($redirecturl, 'session');
                } else {
                    return true;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }

    }

    /**
     * With the correct token, confirms the user then logs them in.
     */

    public function confirm($token) {
        // The user has clicked the link in their confirmation email
        // and the confirm page has passed the token from the URL to here.
        // If all goes well they'll be confirmed and then logged in.

        // Split the token into its parts.
        $arg = '';
        if (strstr($token, '::')) $arg = '::';
        if (strstr($token, '-')) $arg = '-';
        list($user_id, $registrationtoken) = explode($arg, $token);

        if (!is_numeric($user_id) || $registrationtoken == '') {
            return false;
        }

        $q = $this->db->query("SELECT email, password, postcode
                        FROM    users
                        WHERE   user_id = :user_id
                        AND     registrationtoken = :token
                        ", array(
                            ':user_id' => $user_id,
                            ':token' => $registrationtoken
                        ));

        if ($q->rows() == 1) {

            // We'll need these to be set before logging the user in.
            $this->user_id  = $user_id;
            $this->email    = $q->field(0, 'email');
            $this->password = $q->field(0, 'password');

            // Set that they're confirmed in the DB.
            $r = $this->db->query("UPDATE users
                            SET     confirmed = '1'
                            WHERE   user_id = :user_id
                            ", array(':user_id' => $user_id));

            if ($q->field(0, 'postcode')) {
                $MEMBER = new Member(array('postcode'=>$q->field(0, 'postcode'), 'house'=>1));
                $pid = $MEMBER->person_id();
                # This should probably be in the ALERT class
                $this->db->query('update alerts set confirmed=1 where email = :email and criteria = :criteria', array(
                        ':email' => $this->email,
                        ':criteria' => 'speaker:' . $pid
                    ));
            }

            if ($r->success()) {

                $this->confirmed = true;

                // Log the user in, redirecting them to the confirm page
                // where they should get a nice welcome message.
                $URL = new Url('userconfirmed');
                $URL->insert(array('welcome'=>'t'));
                $redirecturl = $URL->generate();

                $this->login($redirecturl, 'session');

            } else {
                // Couldn't set them as confirmed in the DB.
                return false;
            }

        } else {
            // Couldn't find this user in the DB. Maybe the token was
            // wrong or incomplete?
            return false;
        }
    }


    public function set_postcode_cookie($pc) {
        // Set the user's postcode.
        // Doesn't change it in the DB, as it's probably mainly for
        // not-logged-in users.

        $this->postcode = $pc;
        if (!headers_sent()) // if in debug mode
            setcookie (POSTCODE_COOKIE, $pc, time()+7*86400, "/", COOKIEDOMAIN);

        twfy_debug('USER', "Set the cookie named '" . POSTCODE_COOKIE . " to '$pc' for " . COOKIEDOMAIN . " domain");
    }

    public function unset_postcode_cookie() {
        if (!headers_sent()) // if in debug mode
            setcookie (POSTCODE_COOKIE, '', time() - 3600, '/', COOKIEDOMAIN);
    }

    /**
     * Update the user's own data in the DB.
     */

    public function update_self($details, $confirm_email = true) {
        // If the user wants to update their details, call this function.
        // It checks that they're logged in before letting them.


        // $details is an array like that in $this->add().

        global $THEUSER;

        if ($this->isloggedin()) {

            // this is checked elsewhere but just in case we check here and
            // bail out to be on the safe side
            $email = '';
            if ( isset($details['email'] ) ) {
                if ( $details['email'] != $this->email() && $this->email_exists( $details['email'] ) ) {
                    return false;
                }
                $email = $details['email'];
                unset($details['email']);
            }
            $details["user_id"] = $this->user_id;

            $newdetails = $this->_update($details);

            // $newdetails will be an array of details if all went well,
            // false otherwise.

            if ($newdetails) {
                // The user's data was updated, so we'll change the object
                // variables accordingly.

                $this->firstname        = $newdetails["firstname"];
                $this->lastname         = $newdetails["lastname"];
                $this->emailpublic      = $newdetails["emailpublic"];
                $this->postcode         = $newdetails["postcode"];
                $this->url              = $newdetails["url"];
                $this->optin            = $newdetails["optin"];
                if ($newdetails["password"] != "") {
                    $this->password = $newdetails["password"];
                }

                if ($email && $email != $this->email) {
                    $token = substr( crypt($email . microtime()), 12, 16 );
                    $data = $this->user_id() . '::' . $email;
                    $r = $this->db->query("INSERT INTO tokens
                        ( expires, token, type, data )
                        VALUES
                        (
                            DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY),
                            :token,
                            'E',
                            :data
                        )
                    ", array(
                        ':token' => $token,
                        ':data' => $data
                    ));

                    // send confirmation email here
                    if ( $r->success() ) {
                        $newdetails['email'] = $email;
                        $newdetails['token'] = $token;
                        if ($confirm_email) {
                            return $this->send_email_confirmation_email($newdetails);
                        } else {
                            return true;
                        }
                    } else {
                        return false;
                    }
                }

                return true;
            } else {
                return false;
            }

        } else {
            return false;
        }

    }

}
