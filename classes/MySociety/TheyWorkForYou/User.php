<?php
/**
 * User Class
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou;

/**
 * All Users
 *
 * A $THEUSER variable should be created as part of init. This refers to the
 * person actually viewing the site. If they have a valid cookie set, $THEUSER's
 * data will be fetched from the DB and they will be logged in. Otherwise they
 * will have a minimum access level and will not be logged in.
 *
 * The User class allows us to fetch and alter data about any user (rather than $THEUSER).
 *
 * To create a new user do:
 *     $USER = new \MySociety\TheyWorkForYou\User;
 *     $USER->init($user_id);
 *
 * You can then access all the user's variables with appropriately named functions, such as:
 *     $USER->user_id();
 *     $USER->email();
 * etc. Don't access the variables directly because I think that's bad.
 *
 * User is extended into the {@see TheUser} class which is used only for the
 * person currently using the site. ie, it adds functions for logging in and
 * out, checking log in status, etc.
 *
 * GUESTUSER:
 * In the database there should be a user with an id of 0 and a status of
 * 'Viewer' (and probably a name of 'Guest').
 *
 * The cookie set to indicate a logged in user is called `epuser_id`. More on
 * that in {@see TheUser}.
 */

class User {

    public $user_id = "0";         // So we have an ID for non-logged in users reporting comments etc.
    public $firstname = "Guest";   // So we have something to print for non-logged in users.
    public $lastname = "";
    public $password = "";         // This will be a crypt()ed version of a plaintext pw.
    public $email = "";
    public $emailpublic = "";      // boolean - can other users see this user's email?
    public $postcode = "";
    public $url = "";
    public $lastvisit = "";        // Last time the logged-in user loaded a page (GMT).
    public $registrationtime = ""; // When they registered (GMT).
    public $registrationip = "";   // Where they registered from.
    public $optin = "";            // boolean - Do they want emails from us?
    public $deleted = "";          // User can't log in or have their info displayed.
    public $confirmed = '';        // boolean - Has the user confirmed via email?
    // Don't use the status to check access privileges - use the is_able_to() function.
    public $status = "Viewer";

    // If you add more user variables above you should also:
    //      Add the approrprate code to $this->add()
    //      Add the appropriate code to $this->_update()
    //      Add accessor functions way down below...
    //      Alter THEUSER->update_self() to update with the new vars, if appropriate.
    //      Change things in the add/edit/view user page.

    public function __construct() {
        $this->db = new ParlDb;
    }

    /**
     * Retrieve a user's details from the DB and populate the object.
     *
     * Pass it a user id and it will fetch the user's data from the db and put
     * it all in the appropriate variables. Returns true if we've found user_id
     * in the DB, false otherwise.
     *
     * @param int $user_id The ID of the user to retrieve
     *
     * @return bool
     */

    public function init($user_id) {
        // Pass it a user id and it will fetch the user's data from the db
        // and put it all in the appropriate variables.
        // Returns true if we've found user_id in the DB, false otherwise.

        // Look for this user_id's details.
        $q = $this->db->query("SELECT firstname,
                                lastname,
                                password,
                                email,
                                emailpublic,
                                postcode,
                                url,
                                lastvisit,
                                registrationtime,
                                registrationtoken,
                                registrationip,
                                optin,
                                status,
                                deleted,
                                confirmed
                        FROM    users
                        WHERE   user_id = :user_id",
                        array(':user_id' => $user_id));


        if ($q->rows() == 1) {
            // We've got a user, so set them up.

            $this->user_id              = $user_id;
            $this->firstname            = $q->field(0,"firstname");
            $this->lastname             = $q->field(0,"lastname");
            $this->password             = $q->field(0,"password");
            $this->email                = $q->field(0,"email");
            $this->emailpublic = $q->field(0,"emailpublic") == 1 ? true : false;
            $this->postcode             = $q->field(0,"postcode");
            $this->url                  = $q->field(0,"url");
            $this->lastvisit            = $q->field(0,"lastvisit");
            $this->registrationtoken    = $q->field(0, 'registrationtoken');
            $this->registrationtime     = $q->field(0,"registrationtime");
            $this->registrationip       = $q->field(0,"registrationip");
            $this->optin = $q->field(0,"optin") == 1 ? true : false;
            $this->status               = $q->field(0,"status");
            $this->deleted = $q->field(0,"deleted") == 1 ? true : false;
            $this->confirmed = $q->field(0,"confirmed") == 1 ? true : false;

            return true;

        } elseif ($q->rows() > 1) {
            // And, yes, if we've ended up with more than one row returned
            // we're going to show an error too, just in case.
            // *Should* never happen...
            return false;
            twfy_debug("USER", "There is more than one user with an id of '" . _htmlentities($user_id) . "'");

        } else {
            return false;
            twfy_debug("USER", "There is no user with an id of '" . _htmlentities($user_id) . "'");
        }

    }

    /**
     * Add a new user to the DB.
     */

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

        // We crypt all passwords going into DB.
        $passwordforDB = crypt($details["password"]);

        if (!isset($details["status"])) {
            $details["status"] = "User";
        }

        $optin = $details["optin"] == true ? 1 : 0;

        $emailpublic = $details["emailpublic"] == true ? 1 : 0;

        $q = $this->db->query("INSERT INTO users (
                firstname,
                lastname,
                email,
                emailpublic,
                postcode,
                url,
                password,
                optin,
                status,
                registrationtime,
                registrationip,
                deleted
            ) VALUES (
                :firstname,
                :lastname,
                :email,
                :emailpublic,
                :postcode,
                :url,
                :password,
                :optin,
                :status,
                :registrationtime,
                :registrationip,
                '0'
            )
        ", array(
            ':firstname' => $details["firstname"],
            ':lastname' => $details["lastname"],
            ':email' => $details["email"],
            ':emailpublic' => $emailpublic,
            ':postcode' => $details["postcode"],
            ':url' => $details["url"],
            ':password' => $passwordforDB,
            ':optin' => $optin,
            ':status' => $details["status"],
            ':registrationtime' => $registrationtime,
            ':registrationip' => $REMOTE_ADDR
        ));

        if ($q->success()) {
            // Set these so we can log in.
            // Except we no longer automatically log new users in, we
            // send them an email. So this may not be required.
            $this->user_id = $q->insert_id();
            $this->password = $passwordforDB;

            // We have to set the user's registration token.
            // This will be sent to them via email, so we can confirm they exist.
            // The token will be the first 16 characters of a crypt.

            $token = substr( crypt($details["email"] . microtime()), 12, 16 );

            // Full stops don't work well at the end of URLs in emails,
            // so replace them. We won't be doing anything clever with the crypt
            // stuff, just need to match this token.

            $this->registrationtoken = strtr($token, '.', 'X');

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
                    $MEMBER = new Member(array('postcode'=>$details['postcode'], 'house'=>1));
                    $pid = $MEMBER->person_id();
                    # No confirmation email, but don't automatically confirm
                    $ALERT = new Alert;
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

    /**
     * Done after add()ing the user.
     */

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

        $confirmurl = 'http://' . DOMAIN . '/E/' . $urltoken;

        // Arrays we need to send a templated email.
        $data = array (
            'to'        => $details['email'],
            'template'  => 'email_confirmation'
        );

        $merge = array (
            'FIRSTNAME'     => $details['firstname'],
            'LASTNAME'      => $details['lastname'],
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

        $confirmurl = 'http://' . DOMAIN . '/U/' . $urltoken;

        // Arrays we need to send a templated email.
        $data = array (
            'to'        => $details['email'],
            'template'  => 'join_confirmation'
        );

        $merge = array (
            'FIRSTNAME'     => $details['firstname'],
            'LASTNAME'      => $details['lastname'],
            'CONFIRMURL'    => $confirmurl
        );

        $success = send_template_email($data, $merge);

        if ($success) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Update the data of another user.
     */

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

    /**
     * Generate a new password and put in DB.
     */

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

        $passwordforDB = crypt($pwd);

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

        $URL = new Url("userlogin");

        $merge = array (
            'EMAIL'         => $this->email(),
            'LOGINURL'      => "http://" . DOMAIN . $URL->generate(),
            'PASSWORD'      => $this->password()
        );

        // send_template_email in utility.php.
        $success = send_template_email($data, $merge);

        return $success;

    }

    /**
     * Checks if a user_id is valid.
     */

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

    /**
     * Checks if a user exists with a certain email address.
     */

    public function email_exists($email, $return_id = false) {
        // Returns true if there's a user with this email address.

        if ($email != "") {
            $q = $this->db->query("SELECT user_id FROM users WHERE email = :email", array(':email' => $email));
            if ($q->rows() > 0) {
                if ($return_id) {
                    $row = $q->row(0);

                    return $row['user_id'];
                }

                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }

    }

    /**
     * Is the user allowed to perform this action?
     */

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

            case "addcomment":  // Post comments.

                switch ($status) {
                    case "User":            return true;
                    case "Moderator":       return true;
                    case "Administrator":   return true;
                    case "Superuser":       return true;
                    default: /* Viewer */   return false;
                }

            case "addterm": // Add Glossary terms.

                switch ($status) {
                    case "User":            return true;
                    case "Moderator":       return true;
                    case "Administrator":   return true;
                    case "Superuser":       return true;
                    default: /* Viewer */   return false;
                }

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

    /**
     * Possible user statuses.
     *
     * Just returns an array of the possible statuses a user could have. Handy
     * for forms where you edit/view users etc.
     *
     * @return array The possible security statuses for users.
     */

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
    public function emailpublic() { return $this->emailpublic; }
    public function postcode() { return $this->postcode; }
    public function url() { return $this->url; }
    public function lastvisit() { return $this->lastvisit; }

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

    /**
     * Updates a user's data in DB.
     */

    protected function _update($details) {
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

            // We crypt all passwords going into DB.
            $passwordforDB = crypt($details["password"]);

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

        // Convert internal true/false variables to MySQL BOOL 1/0 variables.
        $emailpublic = $details["emailpublic"] == true ? 1 : 0;
        $optin = $details["optin"] == true ? 1 : 0;

        $q = $this->db->query("UPDATE users
                        SET     firstname   = :firstname,
                                lastname    = :lastname,
                                emailpublic = :emailpublic,
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
                            ':emailpublic' => $emailpublic,
                            ':postcode' => $details['postcode'],
                            ':url' => $details['url'],
                            ':optin' => $optin,
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

}
