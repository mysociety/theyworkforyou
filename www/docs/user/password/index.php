<?php

// For changing a user's password.

/* 	If the form hasn't been submitted, a form is displayed.
    If the form has been submitted we check input.
    If input is OK, we change the user's password and email them.
    If input isn't OK (or the email doesn't work) we display the form with error messages.

*/

include_once '../../../includes/easyparliament/init.php';

$this_page = "userpassword";


$PAGE->page_start();

$PAGE->stripe_start();


if (get_http_var("submitted")) {
    // Form's been submitted.

    // Where we'll store any errors that occur...
    $errors = array();


    $email = trim(get_http_var("email"));


    if ($email == "") {
        $errors["email"] = gettext("Please enter your email address");
    } elseif (!validate_email($email)) {
        $errors["email"] = gettext("Please enter a valid email address");
    } else {

        $USER = new USER;
        $emailexists = $USER->email_exists($email);
        if (!$emailexists) {
            $errors["email"] = 'There is no user registered with that email address. If you are subscribed to email alerts, you are not necessarily registered on the website. If you register, you will be able to manage your email alerts, as well as leave annotations.';
        }

    }

    if (sizeof($errors) > 0) {
        // Validation errors. Print form again.
        display_page($errors);

    } else {

        // Change the user's password!

        $password = $USER->change_password($email);



        if ($password) {

            $success = $USER->send_password_reminder();

            if ($success) {

                print "<p>A new password has been sent to " . _htmlentities($email) . "</p>\n";

            } else {

                $errors["sending"] = "Sorry, there was a technical problem sending the email.";

                display_page($errors);

            }

        } else {
            // This email address isn't in the DB.

            $errors["passwordchange"] = "Sorry, there was a problem and we couldn't set a new password for " . _htmlentities($email);


            display_page($errors);

        }

    }

} else {

    display_page();
}



function display_page ($errors=array()) {
    global $this_page, $PAGE;

    if (isset($errors["sending"])) {
        $PAGE->error_message($errors["sending"]);
    } else {
        print "<p>If you can't remember your password we can send you a new one.</p>\n<p>If you would like a new password, enter your address below.</p>\n";
    }
?>

<form method="get" class="password-form" action="<?php $URL = new \MySociety\TheyWorkForYou\Url($this_page); echo $URL->generate(); ?>">

    <?php
        if (isset($errors["email"])) {
            $PAGE->error_message($errors["email"]);
        }

        if (isset($errors["passwordchange"])) {
            $PAGE->error_message($errors["passwordchange"]);
        }
        ?>

    <div class="row">
    <label for="em">Email address</label>
    <input type="email" name="email" value="<?php echo _htmlentities(get_http_var("email")); ?>" maxlength="100" size="30" class="form-control">
    </div>

    <div class="row">
    
    <input type="submit" value="Send me a new password" class="button"></div>
    </div>

    <input type="hidden" name="submitted" value="true">

</form>

<?php



}

$PAGE->stripe_end();

$PAGE->page_end();

?>
