<?php

/********************************************************************************
First some things to help make our PHP nicer and betterer
********************************************************************************/

error_reporting (E_ALL);

/********************************************************************************
Now some constants that are the same for live and dev versions 
(unlike those variables in conf/general)
********************************************************************************/

// In case we need to switch these off globally at some point...
define ("ALLOWCOMMENTS", true);
define ("ALLOWTRACKBACKS", true);

// These variables are so we can keep date/time formats consistent across the site
// and change them easily.
// Formats here: http://www.php.net/manual/en/function.date.php
define ("LONGERDATEFORMAT",		"l, j F Y");// Monday, 31 December 2003
define ("LONGDATEFORMAT", 		"j F Y"); 	// 31 December 2003
define ("SHORTDATEFORMAT", 		"j M Y");	// 31 Dec 2003
define ("TIMEFORMAT", 			"g:i a");	// 11:59 pm

define ("SHORTDATEFORMAT_SQL",	"%e %b %Y"); // 31 Dec 2003
define ("TIMEFORMAT_SQL", 		"%l:%i %p"); // 11:59 PM

// Where we store the postcode of users if they search for an MP by postcode.
define ('POSTCODE_COOKIE', 		'eppc'); 

/********************************************************************************
And now all the files we'll include on every page. 
********************************************************************************/

if ( TESTING !== TRUE ) {
    include_once dirname(__FILE__) . '/../../../conf/general';
}
include_once INCLUDESPATH . 'utility.php';
twfy_debug_timestamp("after including utility.php");

// Set the default timezone
if(function_exists('date_default_timezone_set')) date_default_timezone_set(TIMEZONE);

// The error_handler function is in includes/utility.php
$error_level = E_ALL & ~E_NOTICE;
if (DEVSITE) {
    $error_level = E_ALL | E_STRICT;
} elseif (version_compare(phpversion(), "5.3") >= 0) {
    $error_level = $error_level & ~E_DEPRECATED;
}

set_error_handler("error_handler", $error_level);
set_exception_handler("exception_handler");

// The time the page starts, so we can display the total at the end.
// getmicrotime() is in utiltity.php.
define ("STARTTIME", getmicrotime());
if (!isset($_SERVER['WINDIR'])) {
	$rusage = getrusage();
	define ('STARTTIMES', $rusage['ru_stime.tv_sec']*1000000 + $rusage['ru_stime.tv_usec']);
	define ('STARTTIMEU', $rusage['ru_utime.tv_sec']*1000000 + $rusage['ru_utime.tv_usec']);
}
include_once (INCLUDESPATH."data.php");
include_once (INCLUDESPATH."mysql.php");

Class ParlDB extends MySQL {
	function ParlDB () {
		$this->init (OPTION_TWFY_DB_HOST, OPTION_TWFY_DB_USER, OPTION_TWFY_DB_PASS, OPTION_TWFY_DB_NAME);
	}
}

include_once (INCLUDESPATH."url.php");
include_once (INCLUDESPATH."lib_filter.php");
include_once (INCLUDESPATH."easyparliament/user.php");
include_once (INCLUDESPATH."easyparliament/page.php");
include_once (INCLUDESPATH."easyparliament/hansardlist.php");
include_once (INCLUDESPATH."easyparliament/commentlist.php");
include_once (INCLUDESPATH."easyparliament/comment.php");
include_once (INCLUDESPATH."easyparliament/trackback.php");

// Added in as new module by Richard Allan MP
include_once (INCLUDESPATH."easyparliament/alert.php");

twfy_debug_timestamp("at end of init.php");

