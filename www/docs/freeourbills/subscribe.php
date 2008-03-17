<?

// Copied from ms.org

include_once '../../includes/easyparliament/init.php';
require_once '../../../../phplib/auth.php';
require_once "share.php";

$db = new ParlDB;

$this_page = 'campaign';
$PAGE->page_start();
$PAGE->stripe_start();

function send_subscribe_email($campaigner, $token) {
    $to = $campaigner;
    $from = "TheyWorkForYou <team@theyworkforyou.com>";
    $subject = 'Confirm that you want to Free our Bills!';
    $url = "http://" . DOMAIN . '/B/' . $token;
    $message = 'Please click on the link below to confirm your email address.
You will then be signed up to TheyWorkForYou\'s campaign to Free our Bills.

'.$url.'

We will never give away or sell your email address to anyone else
without your permission.

-- TheyWorkForYou.com campaigns team';

    $headers = "From: $from\r\n" .
        "X-Mailer: PHP/" . phpversion();

    $success = mail ($to, $subject, $message, $headers);
    return $success;
}

$url_token = trim(get_http_var('t'));
if ($url_token) {
	
	$q = $db->query('SELECT * FROM campaigners WHERE token = "' . mysql_escape_string($url_token).'"');
 	if ($q->rows() > 0) {
		$q = $db->query('UPDATE campaigners SET confirmed = 1 WHERE token = "' . mysql_escape_string($url_token).'"');
		?>
		<p>Thanks for signing up to the campaign!</p>
		<p><a href="/freeourbills">Return to 'Free our Bills' homepage</a>
		<?
	} else {
		?>
		<p>Please check the link that you've copied from your email, it doesn't seem right.</p>
		<p><a href="/freeourbills">Return to 'Free our Bills' homepage</a>
		<?
	}
	return;
}


$errors = array();
$email = trim(get_http_var('email'));
if (!$email) {
    $errors[] = 'Please enter your e-mail address';
} elseif (!validate_email($email)) {
    $errors[] = 'Please enter a valid e-mail address';
}
$postcode = trim(get_http_var('postcode'));
if (!$postcode) {
    $errors[] = 'Please enter your postcode';
} elseif (!validate_postcode($postcode)) {
    $errors[] = 'Please enter a valid postcode';
}

if ($errors) {
	print '<div id="warning"><ul><li>';
	print join ('</li><li>', array_values($errors));
	print '</li></ul></div>';

    signup_form();
} else {
    $token = auth_random_token();
    if (send_subscribe_email($email, $token)) {
        $q = $db->query("INSERT INTO campaigners (email, postcode, token, signup_date) VALUES ('" . mysql_escape_string($email) . "', '".mysql_escape_string($postcode)."', '".$token."', now())");

        print "<p><b>Thanks!</b>  Now check your email. In a few minutes you will
        get a message telling you how to confirm your subscription.</p>";
   } else {
   	print "<p>There was a problem signing you up, please try again.</p>";
   }
}

?>
<p><a href="./">Return to 'Free our Bills' homepage</a>

<?php

$PAGE->stripe_end();
$PAGE->page_end();

