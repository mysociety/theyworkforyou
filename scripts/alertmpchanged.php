<?php
/*
 * Name: alertmpchanged.php
 * Description: Mailer for those whose MP has changed at the election
 */

function mlog($message) {
    print $message;
}

include_once dirname(__FILE__) . '/../www/includes/easyparliament/init.php';
ini_set('memory_limit', -1);
include_once INCLUDESPATH . 'easyparliament/member.php';

$global_start = getmicrotime();
$db = new ParlDB;

$nomail = false;
$onlyemail = '';
$fromemail = '';
$fromflag = false;
$toemail = '';
$template = 'alert_new_mp';
for ($k=1; $k<$argc; $k++) {
	if ($argv[$k] == '--nomail')
		$nomail = true;
	if (preg_match('#^--only=(.*)$#', $argv[$k], $m))
		$onlyemail = $m[1];
	if (preg_match('#^--from=(.*)$#', $argv[$k], $m))
		$fromemail = $m[1];
	if (preg_match('#^--to=(.*)$#', $argv[$k], $m))
		$toemail = $m[1];
}

#if (DEVSITE)
#	$nomail = true;

if (!defined('DISSOLUTION_DATE') ) {
    mlog('Need to set DISSOLUTION_DATE in config');
    exit;
}

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

$LIVEALERTS = new ALERT;

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

    // we only care about alerts for people speaking
    if (!strstr($criteria_raw, 'speaker:')) continue;

    preg_match('#speaker:(\d+)#', $criteria_raw, $m);
    $person_id = $m[1];
    if (!isset($members[$person_id])) {
        $queries++;
        $members[$person_id] = new MEMBER(array('person_id' => $person_id));
    }
    $member = $members[$person_id];

    // if they're still elected then don't send the email
    if ($member->current_member_anywhere()) continue;

    // skip if they didn't lose their westminster seat in the most recent election
    if ($member->left_house[1]['date'] != DISSOLUTION_DATE) continue;

    if ( !isset($cons[$member->constituency]) ) {
        $cons_member = new MEMBER(array('constituency' => $member->constituency, 'house' => 1, 'still_in_office' => true));
        if ( !$cons_member ) {
            continue;
        }
        $cons[$member->constituency] = $cons_member;
    } else {
        $cons_member = $cons[$member->constituency];
    }

    // these should never happen but let's just be sure
    if ( $cons_member->person_id == $member->person_id ) continue;
    if (!$cons_member->current_member_anywhere()) continue;

    if ($email != $current['email']) {
        if ($email_text && $change_text) {
            write_and_send_email($current, $email_text, $change_text, $template);
        }
        $current['email'] = $email;
        $current['token'] = $alertitem['alert_id'] . '-' . $alertitem['registrationtoken'];
        $email_text = array();
        $change_text = array();
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
    $text = '* ' . $member->full_name();
    if (!in_array($text, $email_text)) {
        $email_text[] = $text;

        $change = '* ' . $member->full_name() . ': http://' . DOMAIN . '/C/' . $alertitem['registrationtoken'];
        $change_text[] = $change;
    }
}
if ($email_text && $change_text) {
    write_and_send_email($current, $email_text, $change_text, $template);
}

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

function write_and_send_email($current, $data, $change, $template) {
    global $globalsuccess, $sentemails, $nomail, $start_time;

    $sentemails++;
    mlog("SEND $sentemails : Sending email to $current[email] ... ");
    $d = array('to' => $current['email'], 'template' => $template);
    $m = array(
        'DATA' => join("\n", $data),
        'CHANGE' => join("\n", $change),
        'ALERT_IS' => count($data)==1 ? 'alert is' : 'alerts are',
        'MPS' => count($data)==1 ? 'This MP' : 'These MPs',
        'MPS2' => count($data)==1 ? 'MP' : 'MPs',
        'ALERTS' => count($data)==1 ? 'an alert' : 'some alerts',
        'ALERTS2' => count($data)==1 ? 'alert' : 'alerts',
        'LINKS' => count($data)==1 ? 'link' : 'links',
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

    if (!$success) {
        $globalsuccess = 0;
    }
}

