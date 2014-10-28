<?php
/*
 * Name: alertgonemps.php
 * Description: Mailer for those whose MP has gone
 * $Id: alertgonemps.php,v 1.2 2009-06-16 09:12:09 matthew Exp $
 */

function mlog($message) {
    print $message;
}

include_once '../www/includes/easyparliament/init.php';
ini_set('memory_limit', -1);

$global_start = getmicrotime();
$db = new \MySociety\TheyWorkForYou\ParlDb;

$nomail = false;
$onlyemail = '';
$fromemail = '';
$fromflag = false;
$toemail = '';
$template = 'alert_gone';
for ($k=1; $k<$argc; $k++) {
	if ($argv[$k] == '--nomail')
		$nomail = true;
	if (preg_match('#^--only=(.*)$#', $argv[$k], $m))
		$onlyemail = $m[1];
	if (preg_match('#^--from=(.*)$#', $argv[$k], $m))
		$fromemail = $m[1];
	if (preg_match('#^--to=(.*)$#', $argv[$k], $m))
		$toemail = $m[1];
	if (preg_match('#^--template=(.*)$#', $argv[$k], $m)) {
		$template = $m[1];
		# Tee hee
		$template = "../../../../../../../../../../home/twfy-live/email-alert-templates/alert_mailout_$template";
	}
}

#if (DEVSITE)
#	$nomail = true;

if ($nomail) mlog("NOT SENDING EMAIL\n");
if (($fromemail && $onlyemail) || ($toemail && $onlyemail)) {
	mlog("Can't have both from/to and only!\n");
	exit;
}

$active = 0;
$queries = 0;
$unregistered = 0;
$registered = 0;
$sentemails = 0;

$LIVEALERTS = new \MySociety\TheyWorkForYou\Alert;

$current = array('email' => '', 'token' => '');
$email_text = array();
$globalsuccess = 1;

# Fetch all confirmed, non-deleted alerts
$confirmed = 1; $deleted = 0;
$alertdata = $LIVEALERTS->fetch($confirmed, $deleted);
$alertdata = $alertdata['data'];

$outof = count($alertdata);
$members = array();
$start_time = time();
foreach ($alertdata as $alertitem) {
	$active++;
	$email = $alertitem['email'];
    if ($onlyemail && $email != $onlyemail) continue;
    if ($fromemail && strtolower($email) == $fromemail) $fromflag = true;
    if ($fromemail && !$fromflag) continue;
    if ($toemail && strtolower($email) >= $toemail) continue;
    $criteria_raw = $alertitem['criteria'];

	if (!strstr($criteria_raw, 'speaker:')) continue;

	preg_match('#speaker:(\d+)#', $criteria_raw, $m);
	$person_id = $m[1];
	if (!isset($members[$person_id])) {
        $queries++;
        $members[$person_id] = new \MySociety\TheyWorkForYou\Member(array('person_id' => $person_id));
	}
    $member = $members[$person_id];
    if ($member->current_member_anywhere()) continue;

    if (in_array($member->full_name(), array(
        'Mr Paul Boateng', 'Mrs Helen Liddell', 'Jack McConnell', 'Tim Boswell',
        'Angela Browning', 'John Gummer', 'Michael Howard', 'John Maples',
        'Michael Spicer', 'Mr Richard Allan', 'Matthew Taylor', 'Phil Willis',
        'Hilary Armstrong', 'Des Browne', 'Quentin Davies', 'Beverley Hughes',
        'John Hutton', 'Jim Knight', 'Thomas McAvoy', 'John McFall', 'John Prescott',
        'John Reid', 'Angela Smith', 'Don Touhig', 'Michael Wills', 'Ian Paisley',
    ))) continue;

	if ($email != $current['email']) {
		if ($email_text)
            write_and_send_email($current, $email_text, $template);
		$current['email'] = $email;
		$current['token'] = $alertitem['alert_id'] . '-' . $alertitem['registrationtoken'];
		$email_text = array();
		$q = $db->query('SELECT user_id FROM users WHERE email = :email', array(
            ':email' => $email));
        if ($q->rows() > 0) {
            $user_id = $q->field(0, 'user_id');
            $registered++;
		} else {
			$user_id = 0;
			$unregistered++;
		}
		mlog("\nEMAIL: $email, uid $user_id; memory usage : ".memory_get_usage()."\n");
	}

    $lh = $member->left_house();
    $lh = array_shift($lh);
    $text = '* ' . $member->full_name() . ', left ' . $lh['date_pretty'];
    if (!in_array($text, $email_text))
	    $email_text[] = $text;
}
if ($email_text)
    write_and_send_email($current, $email_text, $template);

mlog("\n");

$sss = "Active alerts: $active\nEmail lookups: $registered registered, $unregistered unregistered\nQuery lookups: $queries\nSent emails: $sentemails\n";
if ($globalsuccess) {
      $sss .= 'Everything went swimmingly, in ';
} else {
      $sss .= 'Something went wrong! Total time: ';
}
$sss .= (getmicrotime()-$global_start)."\n\n";
mlog($sss);
mlog(date('r') . "\n");

function write_and_send_email($current, $data, $template) {
	global $globalsuccess, $sentemails, $nomail, $start_time;

	$sentemails++;
	mlog("SEND $sentemails : Sending email to $current[email] ... ");
	$d = array('to' => $current['email'], 'template' => $template);
	$m = array(
		'DATA' => join("\n", $data),
		'MANAGE' => 'http://www.theyworkforyou.com/D/' . $current['token'],
        'ALERT_IS' => count($data)==1 ? 'alert is' : 'alerts are',
        'ALERTS' => count($data)==1 ? 'an alert' : 'some alerts',
	);
	if (!$nomail) {
		$success = send_template_email($d, $m, true);
		mlog("sent ... ");
		# sleep if time between sending mails is less than a certain number of seconds on average
		if (((time() - $start_time) / $sentemails) < 0.5 ) { # number of seconds per mail not to be quicker than
			mlog("pausing ... ");
			sleep(1);
		}
	} else {
		mlog(join('', $data));
		$success = 1;
	}
	mlog("done\n");
	if (!$success) $globalsuccess = 0;
}

