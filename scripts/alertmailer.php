<?php
/* 
 * Name: alertmailer.php
 * Description: Mailer for email alerts
 * $Id: alertmailer.php,v 1.7 2006-10-26 10:55:32 twfy-live Exp $
 */

include '/data/vhost/www.theyworkforyou.com/includes/easyparliament/init.php';
ini_set('memory_limit', -1);
include INCLUDESPATH . 'easyparliament/member.php';

$global_start = getmicrotime();

$lastupdated = file('alerts-lastsent');
$lastupdated = join('', $lastupdated);
if (!$lastupdated) $lastupdated = strtotime('00:00 today');

# For testing purposes, specify nomail on command line to not send out emails
$nomail = false;
$onlyemail = '';
$fromemail = '';
for ($k=1; $k<$argc; $k++) {
	if ($argv[$k] == '--nomail')
		$nomail = true;
	if (preg_match('#^--only=(.*)$#', $argv[$k], $m))
		$onlyemail = $m[1];
	if (preg_match('#^--from=(.*)$#', $argv[$k], $m))
		$fromemail = $m[1];
}

if ($nomail) print "NOT SENDING EMAIL\n";
if ($fromemail && $onlyemail) {
	print "Can't have both from and only!\n";
	exit;
}

$active = 0;
$queries = 0;
$unregistered = 0;
$registered = 0;
$sentemails = 0;

$LIVEALERTS = new ALERT;

$current_email = '';
$email_text = '';
$globalsuccess = 1;

# Fetch all confirmed, non-deleted alerts
$confirmed = 1; $deleted = 0;
$alertdata = $LIVEALERTS->fetch($confirmed, $deleted);
$alertdata = $alertdata['data'];

$DEBATELIST = new DEBATELIST; # Nothing debate specific, but has to be one of them
$db = new ParlDB;

$sects = array('', 'Commons debate', 'Westminster Hall debate', 'Written Answer', 'Written Ministerial Statement');
$sects[101] = 'Lords debate';
$sects_short = array('', 'debate', 'westminhall', 'wrans', 'wms');
$sects_short[101] = 'lords';
$results = array();

foreach ($alertdata as $alertitem) {
	$active++;
	$email = $alertitem['email'];
	if ($onlyemail && $email != $onlyemail) continue;
	if ($fromemail && strtolower($email) < $fromemail) continue;
	$criteria = $alertitem['criteria'];

	print "$active : Checking $criteria for $email; current memory usage : ".memory_get_usage()."\n";

	if ($email != $current_email) {
		if ($email_text)
			write_and_send_email($current_email, $user_id, $email_text);
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
		print "  EMAIL: Looking up $email, result uid $user_id\n";
	}

	if (!isset($results[$criteria])) {
		$start = getmicrotime();
		$SEARCHENGINE = new SEARCHENGINE($criteria);
		$args = array(
			's' => $criteria,
			'threshold' => $lastupdated, # Return everything added since last time this script was run
			'o' => 'c',
			'num' => 1000, // this is limited to 1000 in hansardlist.php anyway
			'pop' => 1,
			'e' => 1 # Don't escape ampersands
		);
		$results[$criteria] = $DEBATELIST->_get_data_by_search($args);
		#		unset($SEARCHENGINE);
		$total_results = $results[$criteria]['info']['total_results'];
		$queries++;
		print "  QUERY $queries : Looking up '$criteria', hits ".$total_results.", time ".(getmicrotime()-$start)."\n";
	}
	$data = $results[$criteria];

	if (isset($data['rows']) && count($data['rows']) > 0) {
		usort($data['rows'], 'sort_by_stuff'); # Sort results into order, by major, then date, then hpos
		$o = array(); $major = 0; $count = array(); $total = 0;
		$any_content = false;
		foreach ($data['rows'] as $row) {
			if ($major != $row['major']) {
				$count[$major] = $total; $total = 0;
				$major = $row['major'];
				$o[$major] = '';
				$k = 3;
			}
			#print $row['major'] . " " . $row['gid'] ."\n";
			if ($row['hdate'] < '2006-05-19') continue;
			$q = $db->query('SELECT gid_from FROM gidredirect WHERE gid_to=\'uk.org.publicwhip/' . $sects_short[$major] . '/' . mysql_escape_string($row['gid']) . "'");
			if ($q->rows() > 0) continue;
			--$k;
			if ($k>=0) {
				$any_content = true;
				$parentbody = str_replace(array('&#8212;','<span class="hi">','</span>'), array('-','*','*'), $row['parent']['body']);
				$body = str_replace(array('&#163;','&#8212;','<span class="hi">','</span>'), array("\xa3",'-','*','*'), $row['body']);
				if (isset($row['speaker']) && count($row['speaker'])) $body = html_entity_decode(member_full_name($row['speaker']['house'], $row['speaker']['title'], $row['speaker']['first_name'], $row['speaker']['last_name'], $row['speaker']['constituency'])) . ': ' . $body;

				$body = wordwrap($body, 72);
				$o[$major] .= $parentbody . ' (' . format_date($row['hdate'], SHORTDATEFORMAT) . ")\nhttp://www.theyworkforyou.com" . $row['listurl'] . "\n";
				$o[$major] .= $body . "\n\n";
			}
			$total++;
		}
		$count[$major] = $total;

		if ($any_content) {
			# Add data to email_text
			$desc = trim(html_entity_decode($data['searchdescription']));
			$deschead = ucfirst(str_replace('containing ', '', $desc));
			foreach ($o as $major => $body) {
				if ($body) {
					$heading = $deschead . ' : ' . $count[$major] . ' ' . $sects[$major] . ($count[$major]!=1?'s':'');
					$email_text .= "$heading\n".str_repeat('=',strlen($heading))."\n\n";
					if ($count[$major] > 3) {
						$email_text .= "There are more results than we have shown here. See more:\nhttp://www.theyworkforyou.com/search/?s=".urlencode($criteria)."+section:".$sects_short[$major]."&o=d\n\n";
					}
					$email_text .= $body;
				}
			}
			$email_text .= "To cancel your alert for items " . $desc . ", please use:\nhttp://www.theyworkforyou.com/alert/delete/?t=" . $alertitem['alert_id'] . '::' . $alertitem['registrationtoken'] . "\n\n";
		}
	}
}
if ($email_text)
	write_and_send_email($current_email, $user_id, $email_text);

print "\n";

$sss = "Active alerts: $active\nEmail lookups: $registered registered, $unregistered unregistered\nQuery lookups: $queries\nSent emails: $sentemails\n";
if ($globalsuccess) {
	$sss .= 'Everything went swimmingly, in ';
} else {
	$sss .= 'Something went wrong! Total time: ';
}
$sss .= (getmicrotime()-$global_start)."\n\n";
print $sss;
if (!$nomail && !$onlyemail) {
	$fp = fopen('alerts-lastsent', 'w');
	fwrite($fp, time() );
	fclose($fp);
	mail(ALERT_STATS_EMAILS, 'Email alert statistics', $sss, 'From: Email Alerts <fawkes@dracos.co.uk>');
}

function sort_by_stuff($a, $b) {
	if ($a['major'] > $b['major']) return 1;
	if ($a['major'] < $b['major']) return -1;

	if ($a['hdate'] < $b['hdate']) return 1;
	if ($a['hdate'] > $b['hdate']) return -1;

	if ($a['hpos'] == $b['hpos']) return 0;
	return ($a['hpos'] > $b['hpos']) ? 1 : -1;
}

function write_and_send_email($email, $user_id, $data) {
	global $globalsuccess, $sentemails, $nomail;

	$data .= '===================='."\n\n";
	if ($user_id) {
		$data .= "As a registered user, visit http://www.theyworkforyou.com/user/\nto manage your alerts.\n";
	} else {
		$data .= "If you register on the site, you will be able to manage your\nalerts there as well as post comments. :)\n";
	}
	$sentemails++;
	print "SEND $sentemails : Sending email to $email\n";
	$d = array('to' => $email, 'template' => 'alert_mailout');
	$m = array('DATA' => $data);
	if (!$nomail) {
		$success = send_template_email($d, $m);
		usleep(500000);
	} else {
		print $data;
		$success = 1;
	}
	print "Sent\n";
	if (!$success) $globalsuccess = 0;
}

?>
