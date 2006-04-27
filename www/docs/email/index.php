<?php

$this_page = "emailfriend";

include_once "../../includes/easyparliament/init.php";
include_once INCLUDESPATH . 'easyparliament/member.php';

$PAGE->page_start();

$PAGE->stripe_start();

// bonus points, let it take multiple emails in the box.
$recipient_email = get_http_var('recipient_mail');
$sender_email = get_http_var('sender_mail');
$sender_name = get_http_var('sender_name');
$pid = intval(get_http_var('pid'));
if ($pid)
	$MEMBER = new MEMBER(array('person_id' => $pid));

//validate them
$errors = array();

if (!$pid)
	$errors[] = 'You did not get to this page from an MP or Peer page. Please go back and try again.';
if (!preg_match('#^[^@]+@#', $recipient_email))
	$errors[] = "'$recipient_email' is not a valid recipient email address. Please have another go.";
if (!validate_email($sender_email))
	$errors[] = "'$sender_email' is not a valid sender email address. Please have another go.";
if (!$sender_name)
	$errors[] = "If you don't give us your name, we can't tell the recipient who sent them the link. We won't store it or use for any other purpose than sending this email.";

if (sizeof($errors)) {
	print '<p>Please correct the following errors:</p>';
	print '<ul><li>' . join('</li> <li>', $errors) . '</li></ul><br />';
?>
<form action="./" method="post">
<p>
<label for="recmail">Their email:</label> <input type="text" name="recipient_mail" id="recmail" value="<?=$recipient_email ?>" size="30" />
<br /><label for="sendmail">Your email:</label> <input type="text" id="sendmail" name="sender_mail" value="<?=$sender_email ?>" size="30" />
<br /><label for="sendname">Your name:</label> <input type="text" id="sendname" name="sender_name" value="<?=$sender_name ?>" size="30" />
<input type="hidden" name="pid" value="<?=$pid ?>" />
<br />(<a href="/privacy/">privacy policy</a>)
<input type="submit" class="submit" value="Send" /></p>
</form>
<?
} else {
	$rep_name = $MEMBER->full_name() . ($MEMBER->house() == 1 ? ' MP' : '');
	$data = array (
		'template'      => 'email_a_friend',
		'to'            => $recipient_email,
		'subject'       => 'Find out all about ' . $rep_name
	);
	$url = $MEMBER->url();
	$merge = array (
		'NAME' => $sender_name,
		'EMAIL' => $sender_email,
		'REP_NAME' => $rep_name,
		'REP_URL' => $url
	);

	$success = send_template_email($data, $merge);
	if ($success) {
		print "<p>Your email has been sent successfully. Thank you for using TheyWorkForYou.</p> <p><a href=\"$url\">Return to ".$MEMBER->full_name()."'s page</a></p>";
	} else {
		print "<p>Sorry, something went wrong trying to send an email. Please wait a few minutes and try again.</p>";
	}
}

$PAGE->stripe_end();

$PAGE->page_end();

?>
