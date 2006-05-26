<?
// authed.php:
// Returns whether an email address has signed up to a TWFY alert for an MP.
// Uses shared secret for authentication.
//
// Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: authed.php,v 1.1 2006-05-26 08:44:46 matthew Exp $

include_once "../../includes/easyparliament/init.php";
include_once INCLUDESPATH . '../../../phplib/auth.php';

header("Content-Type: text/plain");

$email = get_http_var('email');
$sign = get_http_var('sign');
$pid = get_http_var('pid');
if (!$pid || !ctype_digit($pid)) print 'not valid';
else {
	$authed = auth_verify_with_shared_secret($email, OPTION_AUTH_SHARED_SECRET, $sign);
	if ($authed) {
		$db = new ParlDB;
		$email = mysql_escape_string($email);
		$q = $db->query('select alert_id from alerts where email="' . $email . '" and criteria="speaker:' . $pid . '" and confirmed and not deleted');
		$already_signed = $q->rows();
		if ($already_signed)
			print "already signed";
		else
			print "not signed";
	} else {
		print "not authed";
	}
}
