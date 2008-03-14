<?

// Copied from ms.org

include_once '../../includes/easyparliament/init.php';
$this_page = 'campaign';
$PAGE->page_start();
$PAGE->stripe_start();

function send_subscribe_email($from) {
    $to = 'campaigns-request@lists.mysociety.org';
    $subject = 'Subscribe';
    $message = 'Subscribing from web form.';

    $headers = "From: $from\r\n" .
        "X-Mailer: PHP/" . phpversion();

    $success = mail ($to, $subject, $message, $headers);
    return $success;
}

$ok = false;
$email = get_http_var('subv');
if (!$email) {
    print '<p id="warning">Please enter your e-mail address</p>';
} elseif (!validate_email($email)) {
    print '<p id="warning">Please enter a valid e-mail address</p>';
} else {
    $ok = true;
}

if (!$ok) {

?>

<form method="post" action="subscribe">
<p>Sign up to our <strong>campaign update list</strong>,
<label for="subv">your email address:</label>
<input type="text" id="subv" name="subv" value="">
<input type="submit" name="sub" value="Subscribe">
</form>

<?
} else {
    if (send_subscribe_email($email)) {
        print "<p><b>Thanks!</b>  Now check your email. In a few minutes you will
        get a message telling you how to confirm your subscription.</p>";
    } else {
        print "<p>Sorry, there was a problem subscribing you.</p>";
    }
}

?>
<p><a href="./">Return to campaign homepage</a>

<?php

$PAGE->stripe_end();
$PAGE->page_end();

