<?

# Will need: $MEMBER = new MEMBER(array('constituency' => $constituency));

require_once '../../includes/easyparliament/init.php';
require_once '../../includes/postcode.inc';
require_once '../../../../phplib/auth.php';
require_once "share.php";
require_once "sharethis.php";

$db = new ParlDB;

$this_page = 'campaign';

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

if (!$errors) {
    $constituency = postcode_to_constituency($postcode);
    if ($constituency != "connection_timed_out" && $constituency != "") {
        $token = auth_random_token();
        if (send_subscribe_email($email, $token)) {
        $q = $db->query("INSERT INTO campaigners (email, postcode, token, signup_date, constituency) VALUES ('" . mysql_escape_string($email) . "', '".mysql_escape_string($postcode)."', '".$token."', now(), '".mysql_escape_string($constituency)."')");

        print "<html><head><title>Check your email! - Free Our Bills - TheyWorkForYou</title></head><body>";
        freeourbills_styles();
        ?>
        <h1 class="free_our_bills_confirm">Nearly Done! Now check your email...</h1>
        <h2 class="free_our_bills_confirm">The confirmation email <strong>may</strong> take a few minutes to arrive &mdash; <em>please</em> be patient.</h2>
        <p class="free_our_bills_confirm">If you use web-based email or have 'junk mail' filters, you may wish to check your bulk&#47;spam mail folders: sometimes, our messages are marked that way.</p>
        <p class="free_our_bills_confirm">You must now click on the link within the email we've just sent you -<br>if you do not, you will not have joined the Free Our Bills campaign.</p>

        <?
        exit;
       }
   }
}

$PAGE->page_start();
$PAGE->stripe_start();
freeourbills_styles();

$url_token = trim(get_http_var('t'));
if ($url_token) {
	
	$q = $db->query('SELECT * FROM campaigners WHERE token = "' . mysql_escape_string($url_token).'"');
 	if ($q->rows() > 0) {
		$q = $db->query('UPDATE campaigners SET confirmed = 1 WHERE token = "' . mysql_escape_string($url_token).'"');
		?>
		<p class="free_our_bills_thanks">Thanks for signing up to the campaign! We'll contact you soon.</p>
		<p class="free_our_bills_thanks">Now invite your friends to sign up too...</p>
<? 
$PAGE->block_start(array ('title'=>'Tell your friends about the \'Free our Bills!\' campaign'));
freeourbills_share_page(); 
$PAGE->block_end();
?>
		<p><a href="/freeourbills">Return to 'Free our Bills' homepage</a>
		<?
	} else {
		?>
		<p class="free_our_bills_confirm">Please check the link that you've copied from your email, it doesn't seem right.</p>
		<p><a href="/freeourbills">Return to 'Free our Bills' homepage</a>
		<?
	}
	return;
}


if ($errors) {
	print '<div id="warning"><ul><li>';
	print join ('</li><li>', array_values($errors));
	print '</li></ul></div>';

    signup_form();
}  else {
    print "<p class=\"free_our_bills_confirm\">There was a problem signing you up, please try again later.</p>";
}
?>

<p><a href="/freeourbills">Return to 'Free our Bills' homepage</a>

<?php

$PAGE->stripe_end();
$PAGE->page_end();

