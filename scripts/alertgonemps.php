<?php
/* 
 * Name: alertgonemps.php
 * Description: Mailer for those whose MP has gone
 * $Id: alertgonemps.php,v 1.1 2006-04-27 14:20:20 twfy-live Exp $
 */

ini_set('memory_limit', -1);

include '/data/vhost/staging.theyworkforyou.com/includes/easyparliament/init.php';
include INCLUDESPATH . 'easyparliament/member.php';

$nomail = 1;

$sentemails = 0;
$out = '';

$unregistered = 0;
$registered = 0;

$LIVEALERTS = new ALERT;

$current_email = '';
$email_text = '';
$globalsuccess = 1;

# Fetch all confirmed, non-deleted alerts
$confirmed = 1; $deleted = 0;
$alertdata = $LIVEALERTS->fetch($confirmed, $deleted);
$alertdata = $alertdata['data'];

$leftd = array(); $named = array(); $num = array();
$db = new ParlDB;
foreach ($alertdata as $alertitem) {
	$email = $alertitem['email'];
	$criteria = $alertitem['criteria'];
	if (!strstr($criteria, 'speaker:')) continue;

	preg_match('#speaker:(\d+)#', $criteria, $m);
	$person_id = $m[1];
	if (!isset($leftd[$person_id])) {
		$q = $db->query('SELECT first_name,last_name,MAX(left_house) as l FROM member WHERE person_id = ' . $person_id . ' GROUP BY first_name');
		$leftd[$person_id] = $q->field(0, 'l');
		$named[$person_id] = $q->field(0, 'first_name') . ' ' . $q->field(0, 'last_name');
	}
	$left = $leftd[$person_id];
	$name = $named[$person_id];
	if ($left == '9999-12-31') continue;

	if ($email != $current_email) {
		if ($email_text) {
			print "$current_email : $email_text\n";
		}
		$current_email = $email;
		$email_text = '';
		$q = $db->query('SELECT user_id FROM users WHERE email = \''.mysql_escape_string($email)."'");
                if ($q->rows() > 0) {
                        $user_id = $q->field(0, 'user_id');
                        $registered++;
		} else {
			$user_id = 0;
			$unregistered++;
		}	
	}

	$email_text .= "$name, ";
	$num[$person_id] = 1;
}
if ($email_text) {
	print "$current_email : $email_text\n";
}

print "Number of different MPs: " . count($num) . "\n";
print "Email lookups: $registered registered, $unregistered unregistered\n";


function write_and_send_email($email, $user_id, $data) {
	global $globalsuccess, $out, $sentemails, $nomail;

	if ($user_id) {
		$data = "As a registered user, visit http://www.theyworkforyou.com/user/\nto manage your alerts.\n\n" . $data;
	} else {
		$data = "If you register on the site, you will be able to manage your\nalerts there as well as post comments. :)\n\n" . $data;
	}
	$out .= "SEND: Sending email to $email\n";
	print "SEND: Sending email to $email\n";
	$sentemails++;
	$d = array('to' => $email, 'template' => 'alert_mailout');
	$m = array('DATA' => $data);
	if (!$nomail) {
		$success = send_template_email($d, $m);
		usleep(500000);
	} else {
		$success = 1;
		$out .= $data . "\n\n";
#		print $data . "\n\n";
	}
	if (!$success) $globalsuccess = 0;
}

?>

