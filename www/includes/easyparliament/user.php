<?php

/*

NO HTML IN THIS FILE!!

This file contains USER and THEUSER classes.

It automatically instantiates a $THEUSER global object. This refers to the person actually viewing the site. If they have a valid cookie set, $THEUSER's data will be fetched from the DB and they will be logged in. Otherwise they will have a minimum access level and will not be logged in.


The USER class allows us to fetch and alter data about any user (rather than $THEUSER).

To create a new user do:
    $USER = new USER;
    $USER->init($user_id);

You can then access all the user's variables with appropriately named functions, such as:
    $USER->user_id();
    $USER->email();
etc. Don't access the variables directly because I think that's bad.

USER is extended into the THEUSER class which is used only for the person currently using the site. ie, it adds functions for logging in and out, checking log in status, etc.

GUESTUSER:
In the database there should be a user with an id of 0 and a status of 'Viewer' (and probably a name of 'Guest').

The cookie set to indicate a logged in user is called "epuser_id". More on that in THEUSER().

Functions here:

USER
    init()              Send it a user id to fetch data from DB.
    add()               Add a new user to the DB.
    send_confirmation_email()   Done after add()ing the user.
    update_other_user() Update the data of another user.
    change_password()   Generate a new password and put in DB.
    id_exists()         Checks if a user_id is valid.
    email_exists()      Checks if a user exists with a certain email address.
    is_able_to()        Is the user allowed to perform this action?
    possible_statuses() Return an array of the possible security statuses for users.
    Accessor functions for each object variable (eg, user_id()  ).
    _update()           Private function that updates a user's data in DB.

THEUSER
    THEUSER()           Constructor that logs in if the cookie is correct.
    isloggedin()        Check if the user is logged in or not.
    isvalid()           Check to see if the user's login form details are OK.
    login()             Log the user in.
    logout()            Log the user out.
    confirm()           With the correct token, confirms the user then logs them in.
    update_self()       Update the user's own data in the DB.
    check_user_access() Check a the user is allowed to view this page.

*/

class USER {

    public $user_id = "0";         // So we have an ID for non-logged in users reporting comments etc.
    public $firstname = "Guest";   // So we have something to print for non-logged in users.
    public $lastname = "";
    public $password = "";         // This will be a hashed version of a plaintext pw.
    public $email = "";
    public $postcode = "";
    public $url = "";
    public $lastvisit = "";        // Last time the logged-in user loaded a page (GMT).
    public $registrationtime = ""; // When they registered (GMT).
    public $registrationip = "";   // Where they registered from.
    public $optin = "";            // Int containing multiple binary opt-ins. (See top of User.php)
    public $deleted = "";          // User can't log in or have their info displayed.
    public $confirmed = '';        // boolean - Has the user confirmed via email?
    public $facebook_id = '';      // Facebook ID for users who login with FB
    public $facebook_token = '';   // Facebook token for users who login with FB
    // Don't use the status to check access privileges - use the is_able_to() function.
    public $status = "Viewer";

    // If you add more user variables above you should also:
    //      Add the approrprate code to $this->add()
    //      Add the appropriate code to $this->_update()
    //      Add accessor functions way down below...
    //      Alter THEUSER->update_self() to update with the new vars, if appropriate.
    //      Change things in the add/edit/view user page.

    public function __construct() {
        $this->db = new ParlDB;
    }

    public function init($user_id) {
        // Pass it a user id and it will fetch the user's data from the db
        // and put it all in the appropriate variables.
        // Returns true if we've found user_id in the DB, false otherwise.

        // Look for this user_id's details.
        $q = $this->db->query("SELECT firstname,
                                lastname,
                                password,
                                email,
                                postcode,
                                url,
                                lastvisit,
                                registrationtime,
                                registrationtoken,
                                registrationip,
                                optin,
                                status,
                                deleted,
                                confirmed,
                                facebook_id,
                                facebook_token
                        FROM    users
                        WHERE   user_id = :user_id",
                        array(':user_id' => $user_id))->first();


        if ($q) {
            // We've got a user, so set them up.

            $this->user_id              = $user_id;
            $this->firstname            = $q["firstname"];
            $this->lastname             = $q["lastname"];
            $this->password             = $q["password"];
            $this->email                = $q["email"];
            $this->postcode             = $q["postcode"];
            $this->facebook_id          = $q["facebook_id"];
            $this->facebook_token       = $q["facebook_token"];
            $this->url                  = $q["url"];
            $this->lastvisit            = $q["lastvisit"];
            $this->registrationtoken    = $q['registrationtoken'];
            $this->registrationtime     = $q["registrationtime"];
            $this->registrationip       = $q["registrationip"];
            $this->optin                = $q["optin"];
            $this->status               = $q["status"];
            $this->deleted = $q["deleted"] == 1 ? true : false;
            $this->confirmed = $q["confirmed"] == 1 ? true : false;

            return true;

        } else {
            return false;
            twfy_debug("USER", "There is no user with an id of '" . _htmlentities($user_id) . "'");
        }

    }

    public function add($details, $confirmation_required=true) {
        // Adds a new user's info into the db.
        // Then optionally (and usually) calls another function to
        // send them a confirmation email.

        // $details is an associative array of all the user's details, of the form:
        // array (
        //      "firstname" => "Fred",
        //      "lastname"  => "Bloggs",
        //      etc... using the same keys as the object variable names.
        // )
        // The BOOL variables (eg, optin) will be true or false and will need to be
        // converted to 1/0 for MySQL.
        global $REMOTE_ADDR;

        $registrationtime = gmdate("YmdHis");

        $passwordforDB = password_hash($details["password"], PASSWORD_BCRYPT);

        if (!isset($details["status"])) {
            $details["status"] = "User";
        }

        if (!isset($details["facebook_id"])) {
            $details["facebook_id"] = "";
        }

        $q = $this->db->query("INSERT INTO users (
                firstname,
                lastname,
                email,
                postcode,
                url,
                password,
                optin,
                status,
                registrationtime,
                registrationip,
                facebook_id,
                deleted
            ) VALUES (
                :firstname,
                :lastname,
                :email,
                :postcode,
                :url,
                :password,
                :optin,
                :status,
                :registrationtime,
                :registrationip,
                :facebook_id,
                '0'
            )
        ", array(
            ':firstname' => $details["firstname"],
            ':lastname' => $details["lastname"],
            ':email' => $details["email"],
            ':postcode' => $details["postcode"],
            ':url' => $details["url"],
            ':password' => $passwordforDB,
            ':optin' => $details["optin"],
            ':status' => $details["status"],
            ':registrationtime' => $registrationtime,
            ':facebook_id' => $details["facebook_id"],
            ':registrationip' => $REMOTE_ADDR
        ));

        if ($q->success()) {
            // Set these so we can log in.
            // Except we no longer automatically log new users in, we
            // send them an email. So this may not be required.
            $this->user_id = $q->insert_id();
            $this->password = $passwordforDB;
            $this->facebook_id = $details["facebook_id"];

            // We have to set the user's registration token.
            // This will be sent to them via email, so we can confirm they exist.
            // The token will be the first 16 characters of a hash.

            $token = substr( password_hash($details["email"] . microtime(), PASSWORD_BCRYPT), 29, 16 );

            // Full stops don't work well at the end of URLs in emails, so
            // replace them. And double slash would be treated as single and
            // not work either. We won't be doing anything clever with the hash
            // stuff, just need to match this token.
            $token = strtr($token, './', 'Xx');
            $this->registrationtoken = $token;

            // Add that to the DB.
            $r = $this->db->query("UPDATE users
                            SET registrationtoken = :registrationtoken
                            WHERE   user_id = :user_id
                            ", array (
                                ':registrationtoken' => $this->registrationtoken,
                                ':user_id' => $this->user_id
                            ));

            if ($r->success()) {
                // Updated DB OK.

                if ($details['mp_alert'] && $details['postcode']) {
                    $MEMBER = new MEMBER(array('postcode'=>$details['postcode'], 'house'=>HOUSE_TYPE_COMMONS));
                    $pid = $MEMBER->person_id();
                    # No confirmation email, but don't automatically confirm
                    $ALERT = new ALERT;
                    $ALERT->add(array(
                        'email' => $details['email'],
                        'pid' => $pid,
                        'pc' => $details['postcode'],
                    ), false, false);
                }

                if ($confirmation_required) {
                    // Right, send the email...
                    $success = $this->send_confirmation_email($details);

                    if ($success) {
                        // All is good in the world!
                        return true;
                    } else {
                        // Couldn't send the email.
                        return false;
                    }
                } else {
                    // No confirmation email needed.
                    return true;
                }
            } else {
                // Couldn't add the registration token to the DB.
                return false;
            }

        } else {
            // Couldn't add the user's data to the DB.
            return false;
        }
    }

    public function add_facebook_id($facebook_id) {
        $q = $this->db->query ("UPDATE users SET facebook_id = :facebook_id WHERE email = :email",
            array(
                ':facebook_id' => $facebook_id,
                ':email' => $this->email
            ));

        if ($q->success()) {
            $this->facebook_id = $facebook_id;

            return $facebook_id;
        } else {
            return false;
        }
    }

    public function send_email_confirmation_email($details) {
        // A brief check of the facts...
        if (!is_numeric($this->user_id) ||
            !isset($details['email']) ||
            $details['email'] == '' ||
            !isset($details['token']) ||
            $details['token'] == '' ) {
            return false;
        }

        // We prefix the registration token with the user's id and '-'.
        // Not for any particularly good reason, but we do.

        $urltoken = $this->user_id . '-' . $details['token'];

        $confirmurl = 'https://' . DOMAIN . '/E/' . $urltoken;

        // Arrays we need to send a templated email.
        $data = array (
            'to'        => $details['email'],
            'template'  => 'email_confirmation'
        );

        $merge = array (
            'CONFIRMURL'    => $confirmurl
        );

        $success = send_template_email($data, $merge);

        if ($success) {
            return true;
        } else {
            return false;
        }
    }

    public function send_confirmation_email($details) {
        // After we've add()ed a user we'll probably be sending them
        // a confirmation email with a link to confirm their address.

        // $details is the array we just sent to add(), and which it's
        // passed on to us here.

        // A brief check of the facts...
        if (!is_numeric($this->user_id) ||
            !isset($details['email']) ||
            $details['email'] == '') {
            return false;
        }

        // We prefix the registration token with the user's id and '-'.
        // Not for any particularly good reason, but we do.

        $urltoken = $this->user_id . '-' . $this->registrationtoken;

        $confirmurl = 'https://' . DOMAIN . '/U/' . $urltoken;
        if (isset($details['ret'])) {
            $confirmurl .= '?ret=' . $details['ret'];
        }

        // Arrays we need to send a templated email.
        $data = array (
            'to'        => $details['email'],
            'template'  => 'join_confirmation'
        );

        $merge = array (
            'CONFIRMURL'    => $confirmurl
        );

        $success = send_template_email($data, $merge);

        if ($success) {
            return true;
        } else {
            return false;
        }
    }


    public function update_other_user($details) {
        // If someone (like an admin) is updating another user, call this
        // function. It checks their privileges before letting them.

        // $details is an array like that in $this->add().
        // It must include a 'user_id' element!

        global $THEUSER;

        if (!isset($details["user_id"])) {
            return false;

        } elseif ($THEUSER->is_able_to("edituser")) {

            // If the user doing the updating has appropriate privileges...

            $newdetails = $this->_update($details);

            // $newdetails will be an array of details if all went well,
            // false otherwise.
            if ($newdetails) {
                return true;
            } else {
                return false;
            }

        } else {
            return false;

        }
    }



    public function change_password($email) {

        // This function is called from the Change Password page.
        // It will create a new password for the user with $email address.
        // If all goes OK it will return the plaintext version of the password.
        // Otherwise it returns false.

        if ($this->email_exists($email)) {

            $this->email = $email;
            for (;;) {

                $pwd=null;
                $o=null;

                // Generates the password ....
                for ($x=0; $x < 6;) {
                    $y = rand(1,1000);
                    if($y>350 && $y<601) $d=chr(rand(48,57));
                    if($y<351) $d=chr(rand(65,90));
                    if($y>600) $d=chr(rand(97,122));
                    if ($d!=$o && !preg_match('#[O01lI]#', $d)) {
                        $o=$d; $pwd.=$d; $x++;
                    }
                }

                // If the PW fits your purpose (e.g. this regexpression) return it, else make a new one
                // (You can change this regular-expression how you want ....)
                if (preg_match("/^[a-zA-Z]{1}([a-zA-Z]+[0-9][a-zA-Z]+)+/",$pwd)) {
                    break;
                }

            }
            $pwd = strtoupper($pwd);

        // End password generating stuff.

        } else {

            // Email didn't exist.
            return false;

        }

        $passwordforDB = password_hash($pwd, PASSWORD_BCRYPT);

        $q = $this->db->query ("UPDATE users SET password = :password WHERE email = :email",
            array(
                ':password' => $passwordforDB,
                ':email' => $email
            ));

        if ($q->success()) {
            $this->password = $pwd;

            return $pwd;

        } else {
            return false;
        }

    }

    public function send_password_reminder() {
        global $PAGE;

        // You'll probably have just called $this->change_password().

        if ($this->email() == '') {
            $PAGE->error_message("No email set for this user, so can't send a password reminder.");

            return false;
        }

        $data = array (
            'to'            => $this->email(),
            'template'      => 'new_password'
        );

        $URL = new \MySociety\TheyWorkForYou\Url("userlogin");

        $merge = array (
            'EMAIL'         => $this->email(),
            'LOGINURL'      => "https://" . DOMAIN . $URL->generate(),
            'PASSWORD'      => $this->password()
        );

        // send_template_email in utility.php.
        $success = send_template_email($data, $merge);

        return $success;

    }




    public function id_exists($user_id) {
        // Returns true if there's a user with this user_id.

        if (is_numeric($user_id)) {
            $q = $this->db->query("SELECT user_id FROM users WHERE user_id = :user_id",
                array(':user_id' => $user_id));
            if ($q->rows() > 0) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }

    }


    public function email_exists($email, $return_id = false) {
        // Returns true if there's a user with this email address.

        if ($email != "") {
            $q = $this->db->query("SELECT user_id FROM users WHERE email = :email", array(':email' => $email))->first();
            if ($q) {
                if ($return_id) {
                    return $q['user_id'];
                }
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function facebook_id_exists($id, $return_id = false) {
        // Returns true if there's a user with this facebook id.

        if ($id!= "") {
            $q = $this->db->query("SELECT user_id FROM users WHERE facebook_id = :id", array(':id' => $id))->first();
            if ($q) {
                if ($return_id) {
                    return $q['user_id'];
                }
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function is_able_to($action) {
        // Call this function to find out if a user is allowed to do something.
        // It uses the user's status to return true or false.
        // Possible actions:
        //  "addcomment"
        //  "reportcomment"
        //  "edituser"
        global $PAGE;

        $status = $this->status();

        switch ($action) {

            // You can add more below as they're needed...
            // But keep them in alphabetical order!

            case "deletecomment": // Delete comments.

                switch ($status) {
                    case "User":            return false;
                    case "Moderator":       return true;
                    case "Administrator":   return true;
                    case "Superuser":       return true;
                    default: /* Viewer */   return false;
                }

            case "edituser":

                switch ($status) {
                    case "User":            return false;
                    case "Moderator":       return false;
                    case "Administrator":   return false;
                    case "Superuser":       return true;
                    default: /* Viewer */   return false;
                }

            case "reportcomment":   // Report a comment for moderation.

                switch ($status) {
                    case "User":            return true;
                    case "Moderator":       return true;
                    case "Administrator":   return true;
                    case "Superuser":       return true;
                    default: /* Viewer */   return true;
                }

            case "viewadminsection":    // Access pages in the Admin section.

                switch ($status) {
                    case "User":            return false;
                    case "Moderator":       return false;
                    case "Administrator":   return true;
                    case "Superuser":       return true;
                    default: /* Viewer */   return false;
                }

            case "voteonhansard":   // Rate hansard things interesting/not.
                /* Everyone */              return true;

            default:
                $PAGE->error_message ("You need to set permissions for '$action'!");

                return false;


        }



    }

    // Same for every user...
    // Just returns an array of the possible statuses a user could have.
    // Handy for forms where you edit/view users etc.
    public function possible_statuses() {
        // Maybe there's a way of fetching these from the DB,
        // so we don't duplicate them here...?

        $statuses = array ("Viewer", "User", "Moderator", "Administrator", "Superuser");

        return $statuses;

    }



    // Functions for accessing the user's variables.

    public function user_id() { return $this->user_id; }
    public function firstname() { return $this->firstname; }
    public function lastname() { return $this->lastname; }
    public function password() { return $this->password; }
    public function email() { return $this->email; }
    public function postcode() { return $this->postcode; }
    public function url() { return $this->url; }
    public function lastvisit() { return $this->lastvisit; }
    public function facebook_id() { return $this->facebook_id; }
    public function facebook_token() { return $this->facebook_token; }
    public function facebook_user() { return $this->facebook_user; }

    public function registrationtime() { return $this->registrationtime; }
    public function registrationip() { return $this->registrationip; }
    public function optin() { return $this->optin; }
    // Don't use the status to check access privileges - use the is_able_to() function.
    // But you might use status() to return text to display, describing a user.
    // We can then change what status() does in the future if our permissions system
    // changes.
    public function status() { return $this->status; }
    public function deleted() { return $this->deleted; }
    public function confirmed() { return $this->confirmed; }


    public function postcode_is_set() {
        // So we can tell if the, er, postcode is set or not.
        // Could maybe put some validation in here at some point.
        if ($this->postcode != '') {
            return true;
        } else {
            return false;
        }
    }


/////////// PRIVATE FUNCTIONS BELOW... ////////////////

    public function _update($details) {
        // Update a user's info.
        // DO NOT call this function direct.
        // Call either $this->update_other_user() or $this->update_self().

        // $details is an array like that in $this->add().
        global $PAGE;

        // Update email alerts if email address changed
        if (isset($details['email']) && $this->email != $details['email']) {
            $this->db->query('UPDATE alerts SET email = :details_email WHERE email = :email',
            array(
                ':details_email' => $details['email'],
                ':email' => $this->email
            ));
        }

        // These are used to put optional fragments of SQL in, depending
        // on whether we're changing those things or not.
        $passwordsql = "";
        $deletedsql = "";
        $confirmedsql = "";
        $statussql = "";
        $emailsql = '';

        $params = array();

        if (isset($details["password"]) && $details["password"] != "") {
            // The password is being updated.
            // If not, the password fields on the form will be left blank
            // so we don't want to overwrite the user's pw in the DB!

            $passwordforDB = password_hash($details["password"], PASSWORD_BCRYPT);

            $passwordsql = "password = :password, ";
            $params[':password'] = $passwordforDB;
        }

        if (isset($details["deleted"])) {
            // 'deleted' won't always be an option (ie, if the user is updating
            // their own info).
            if ($details['deleted'] == true) {
                $del = '1';
            } elseif ($details['deleted'] == false) {
                $del = '0';
            }
            if (isset($del)) {
                $deletedsql = "deleted  = '$del', ";
            }
        }

        if (isset($details["confirmed"])) {
            // 'confirmed' won't always be an option (ie, if the user is updating
            // their own info).
            if ($details['confirmed'] == true) {
                $con = '1';
            } elseif ($details['confirmed'] == false) {
                $con = '0';
            }
            if (isset($con)) {
                $confirmedsql = "confirmed  = '$con', ";
            }
        }

        if (isset($details["status"]) && $details["status"] != "") {
            // 'status' won't always be an option (ie, if the user is updating
            // their own info.
            $statussql = "status = :status, ";
            $params[':status'] = $details['status'];

        }

        if (isset($details['email']) && $details['email']) {
            $emailsql = "email = :email, ";
            $params[':email'] = $details['email'];
        }

        $q = $this->db->query("UPDATE users
                        SET     firstname   = :firstname,
                                lastname    = :lastname,
                                postcode    = :postcode,
                                url         = :url,"
                                . $passwordsql
                                . $deletedsql
                                . $confirmedsql
                                . $emailsql
                                . $statussql . "
                                optin       = :optin
                        WHERE   user_id     = :user_id
                        ", array_merge($params, array(
                            ':firstname' => $details['firstname'],
                            ':lastname' => $details['lastname'],
                            ':postcode' => $details['postcode'],
                            ':url' => $details['url'],
                            ':optin' => $details['optin'],
                            ':user_id' => $details['user_id']
                        )));

        // If we're returning to
        // $this->update_self() then $THEUSER will have its variables
        // updated if everything went well.
        if ($q->success()) {
            return $details;

        } else {
            $PAGE->error_message ("Sorry, we were unable to update user id '" . _htmlentities($details["user_id"]) . "'");

            return false;
        }


    }





} // End USER class






class THEUSER extends USER {

    // Handles all the login/out functionality and checking for the user
    // who is using the site right NOW. Yes, him, over there.

    // This will become true if all goes well...
    public $loggedin = false;
    public $facebook_user = false;


    public function __construct() {
        // This function is run automatically when a THEUSER
        // object is instantiated.

        $this->db = new ParlDB;

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
            $cookie = get_cookie_var("facebook_id");
            if ($cookie != '') {
                $this->facebook_user = true;
                twfy_debug("THEUSER", "is facebook login");
            }
        }

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

                    if ($this->facebook_user) {
                        if (md5($this->facebook_token()) == $matches[2] && $this->deleted() == false) {
                            twfy_debug ("THEUSER", "init SUCCESS: setting as logged in");
                            $this->loggedin = true;
                        } elseif (md5 ($this->facebook_token()) != $matches[2]) {
                            twfy_debug ("THEUSER", "init FAILED: Facebook token doesn't match cookie");
                            $this->loggedin = false;
                        } else {
                            twfy_debug ("THEUSER", "init FAILED: User is deleted");
                            $this->loggedin = false;
                        }
                    } else {
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

    } // End THEUSER()

    public function update_lastvisit() {

        if ($this->isloggedin()) {
            // Set last_visit to now.
            $date_now = gmdate("Y-m-d H:i:s");
            $this->db->query("UPDATE users SET lastvisit = :lastvisit WHERE user_id = :user_id",
                [ ':lastvisit' => $date_now, ':user_id' => $this->user_id() ]);

            $this->lastvisit = $date_now;
        }
    }

    // For completeness, but it's better to call $this->isloggedin()
    // if you want to check the log in status.
    public function loggedin() { return $this->loggedin; }



    public function isloggedin() {
        // Call this function to check if the user is successfully logged in.

        if ($this->loggedin()) {
            twfy_debug("THEUSER", "isloggedin: true");

            return true;
        } else {
            twfy_debug("THEUSER", "isloggedin: false");

            return false;
        }
    }


    public function isvalid($email, $userenteredpassword) {
        // Returns true if this email and plaintext password match a user in the db.
        // If false returns an array of form error messages.

        // We use this on the log in page to check if the details the user entered
        // are correct. We can then continue with logging the user in (taking into
        // account their cookie remembering settings etc) with $this->login().

        // This error string is shared between both email and password errors to
        // prevent leaking of account existence.

        $error_string = 'There is no user registered with an email of ' . _htmlentities($email) . ', or the given password is incorrect. If you are subscribed to email alerts, you are not necessarily registered on the website. If you register, you will be able to manage your email alerts, as well as leave annotations.';

        $q = $this->db->query("SELECT user_id, password, deleted, confirmed FROM users WHERE email = :email", array(':email' => $email))->first();

        if ($q) {
            // OK.
            $dbpassword = $q["password"];
            if (password_verify($userenteredpassword, $dbpassword)) {
                $this->user_id  = $q["user_id"];
                $this->password = $dbpassword;
                // We'll need these when we're going to log in.
                $this->deleted  = $q["deleted"] == 1 ? true : false;
                $this->confirmed = $q["confirmed"] == 1 ? true : false;

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

    public function has_postcode() {
        $has_postcode = false;
        if ( $this->isloggedin() && $this->postcode() != '' || $this->postcode_is_set() ) {
            $has_postcode = true;
        }
        return $has_postcode;
    }


    public function facebook_login($returl, $expire, $accessToken) {
        global $PAGE;

        twfy_debug("THEUSER", "Faceook login, user_id " . $this->user_id);
        twfy_debug("THEUSER", "Faceook login, facebook_id " . $this->facebook_id);
        twfy_debug("THEUSER", "Faceook login, email" . $this->email);
        if ($this->facebook_id() == "") {
            $PAGE->error_message ("We don't have a facebook id for this user.", true);

            return;
        }

        twfy_debug("THEUSER", "Faceook login, facebook_token: " . $accessToken);

        $q = $this->db->query ("UPDATE users SET facebook_token = :token WHERE email = :email",
            array(
                ':token' => $accessToken,
                ':email' => $this->email
            ));

        if (!$q->success()) {
            $PAGE->error_message ("There was a problem logging you in", true);
            twfy_debug("THEUSER", "Faceook login, failed to set accessToken");

            return false;
        }

        // facebook login users probably don't have a password
        $cookie = $this->user_id() . "." . md5 ($accessToken);
        twfy_debug("THEUSER", "Faceook login, cookie: " . $cookie);

        twfy_debug("USER", "logging in user from facebook " . $this->user_id);

        $this->loggedin = true;
        $this->_login($returl, $expire, $cookie, 'facebook_id');
        return true;
    }

    public function login($returl, $expire) {

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
            $URL = new \MySociety\TheyWorkForYou\Url("home");
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

        // Reminder: $this->password is actually a hashed version of the plaintext pw.
        $cookie = $this->user_id() . "." . md5 ($this->password());

        $this->_login($returl, $expire, $cookie);
    }

    private function _login($returl, $expire, $cookie, $cookie_name = 'epuser_id') {
        // Unset any existing postcode cookie.
        // This will be the postcode the user set for themselves as a non-logged-in
        // user. We don't want it hanging around as it causes confusion.
        $this->unset_postcode_cookie();

        twfy_debug("THEUSER", "expire is " . $expire);

        $cookie_expires = 0;
        if ($expire == 'never') {
            twfy_debug("THEUSER", "cookie never expires");
            $cookie_expires = time()+86400*365*20;
        } elseif (is_int($expire) && $expire > time()) {
            twfy_debug("THEUSER", "cookie expires at " . $expire);
            $cookie_expires = $expire;
        } else {
            twfy_debug("THEUSER", "cookie expires with session");
        }

        header("Location: $returl");
        setcookie($cookie_name, $cookie, $cookie_expires, '/', COOKIEDOMAIN);
    }


    public function logout($returl) {

        // $returl is the URL to redirect the user to after log in, generally the
        // page they were on before. But if it doesn't exist, they'll just go to
        // the front page.

        if ($returl == '') {
            $URL = new \MySociety\TheyWorkForYou\Url("home");
            $returl = $URL->generate();
        }

        // get_cookie_var() is in includes/utility.php
        if (get_cookie_var("epuser_id") != "") {
            // They're logged in, so set the cookie to empty.
            header("Location: $returl");
            setcookie('epuser_id', '', time() - 86400, '/', COOKIEDOMAIN);
        }

        if (get_cookie_var("facebook_id") != "") {
            // They're logged in, so set the cookie to empty.
            header("Location: $returl");
            setcookie('facebook_id', '', time() - 86400, '/', COOKIEDOMAIN);
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
        ", array (':token' => $registrationtoken))->first();

        if ($q) {
            $expires = $q['expires'];
            $expire_time = strtotime($expires);
            if ( $expire_time < time() ) {
                global $PAGE;
                if ($PAGE && $redirect) {
                    $PAGE->error_message ("Sorry, that token seems to have expired");
                }

                return false;
            }

            list( $user_id, $email ) = explode('::', $q['data']);

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
            );
            $ret = $this->_update($details);

            if ($ret) {
                // and remove the token to be tidy
                $this->db->query("DELETE
                    FROM    tokens
                    WHERE   token = :token
                    AND   type = 'E'
                ", array(':token' => $registrationtoken));

                $this->email = $email;

                # Check Stripe email
                $subscription = new MySociety\TheyWorkForYou\Subscription($this);
                if ($subscription->stripe) {
                    $subscription->update_email($email);
                }

                $URL = new \MySociety\TheyWorkForYou\Url('userconfirmed');
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
                        ))->first();

        if ($q) {

            // We'll need these to be set before logging the user in.
            $this->user_id  = $user_id;
            $this->email    = $q['email'];
            $this->password = $q['password'];

            // Set that they're confirmed in the DB.
            $r = $this->db->query("UPDATE users
                            SET     confirmed = '1'
                            WHERE   user_id = :user_id
                            ", array(':user_id' => $user_id));

            if ($q['postcode']) {
                try {
                    $MEMBER = new MEMBER(array('postcode'=>$q['postcode'], 'house'=>HOUSE_TYPE_COMMONS));
                    $pid = $MEMBER->person_id();
                    # This should probably be in the ALERT class
                    $this->db->query('update alerts set confirmed=1 where email = :email and criteria = :criteria', array(
                            ':email' => $this->email,
                            ':criteria' => 'speaker:' . $pid
                        ));
                } catch (MySociety\TheyWorkForYou\MemberException $e) {
                }
            }

            if ($r->success()) {

                $this->confirmed = true;

                $redirecturl = get_http_var('ret');
                if (!$redirecturl || substr($redirecturl, 0, 1) != '/') {
                    // Log the user in, redirecting them to the confirm page
                    // where they should get a nice welcome message.
                    $URL = new \MySociety\TheyWorkForYou\Url('userconfirmed');
                    $URL->insert(array('welcome'=>'t'));
                    $redirecturl = $URL->generate();
                }

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

    public function confirm_without_token() {
        // If we want to confirm login without a token, e.g. during
        // Facebook registration
        //
        // Note that this doesn't login or redirect the user.

        twfy_debug("THEUSER", "Confirming user without token: " . $this->user_id());
        $q = $this->db->query("SELECT email, password, postcode
                        FROM    users
                        WHERE   user_id = :user_id
                        ", array(
                            ':user_id' => $this->user_id,
                        ))->first();

        if ($q) {

            twfy_debug("THEUSER", "User with ID found to confirm: " . $this->user_id());
            // We'll need these to be set before logging the user in.
            $this->email    = $q['email'];

            // Set that they're confirmed in the DB.
            $r = $this->db->query("UPDATE users
                            SET     confirmed = '1'
                            WHERE   user_id = :user_id
                            ", array(':user_id' => $this->user_id));

            if ($q['postcode']) {
                try {
                    $MEMBER = new MEMBER(array('postcode'=>$q['postcode'], 'house'=>HOUSE_TYPE_COMMONS));
                    $pid = $MEMBER->person_id();
                    # This should probably be in the ALERT class
                    $this->db->query('update alerts set confirmed=1 where email = :email and criteria = :criteria', array(
                            ':email' => $this->email,
                            ':criteria' => 'speaker:' . $pid
                        ));
                } catch (MySociety\TheyWorkForYou\MemberException $e) {
                }
            }

            if ($r->success()) {
                twfy_debug("THEUSER", "User with ID confirmed: " . $this->user_id());
                $this->confirmed = true;
                return true;
            } else {
                twfy_debug("THEUSER", "User with ID not confirmed: " . $this->user_id());
                // Couldn't set them as confirmed in the DB.
                return false;
            }

        } else {
            // Couldn't find this user in the DB. Maybe the token was
            // wrong or incomplete?
            twfy_debug("THEUSER", "User with ID not found to confirm: " . $this->user_id());
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

    // mostly here for updating from facebook where we do not need
    // to confirm the email address
    public function update_self_no_confirm($details) {
        global $THEUSER;

        if ($this->isloggedin()) {
            twfy_debug("THEUSER", "is logged in for update_self");

            // this is checked elsewhere but just in case we check here and
            // bail out to be on the safe side
            if ( isset($details['email'] ) ) {
                if ( $details['email'] != $this->email() && $this->email_exists( $details['email'] ) ) {
                    return false;
                }
            }

            $details["user_id"] = $this->user_id;

            $newdetails = $this->_update($details);

            if ($newdetails) {
                // The user's data was updated, so we'll change the object
                // variables accordingly.

                $this->firstname        = $newdetails["firstname"];
                $this->lastname         = $newdetails["lastname"];
                $this->postcode         = $newdetails["postcode"];
                $this->url              = $newdetails["url"];
                $this->optin            = $newdetails["optin"];
                $this->email            = $newdetails['email'];
                if ($newdetails["password"] != "") {
                    $this->password = $newdetails["password"];
                }

                return true;
            } else {
                return false;
            }

        } else {
            return false;
        }

    }

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
                $this->postcode         = $newdetails["postcode"];
                $this->url              = $newdetails["url"];
                $this->optin            = $newdetails["optin"];
                if ($newdetails["password"] != "") {
                    $this->password = $newdetails["password"];
                }

                if ($email && $email != $this->email) {
                    $token = substr( password_hash($email . microtime(), PASSWORD_BCRYPT), 29, 16 );
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

// Yes, we instantiate a new global $THEUSER object when every page loads.
$THEUSER = new THEUSER;
