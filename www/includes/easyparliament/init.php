<?php


/********************************************************************************
First some things to help make our PHP nicer and betterer
********************************************************************************/

error_reporting (E_ALL );
ini_set("magic_quotes_runtime", 0);
ini_set('memory_limit', 16*1024*1024);

/********************************************************************************
Now some constants that are the same for live and dev versions 
(unlike those variables in includes/easyparliament/config.php)
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

include_once "config.php";
include_once (INCLUDESPATH."utility.php");
twfy_debug_timestamp("after including utility.php");


// The error_handler function is in includes/utility.php
$old_error_handler = set_error_handler("error_handler");


// The time the page starts, so we can display the total at the end.
// getmicrotime() is in utiltity.php.
$rusage = getrusage();
define ("STARTTIME", getmicrotime());
define ('STARTTIMES', $rusage['ru_stime.tv_sec']*1000000 + $rusage['ru_stime.tv_usec']);
define ('STARTTIMEU', $rusage['ru_utime.tv_sec']*1000000 + $rusage['ru_utime.tv_usec']);
include_once (INCLUDESPATH."data.php");
include_once (INCLUDESPATH."mysql.php");

Class ParlDB extends MySQL {
	function ParlDB () {
		$this->init (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	}
}

include_once (INCLUDESPATH."url.php");
include_once (INCLUDESPATH."lib_filter.php");
include_once (INCLUDESPATH."easyparliament/skin.php");
include_once (INCLUDESPATH."easyparliament/user.php");
include_once (INCLUDESPATH."easyparliament/page.php");
include_once (INCLUDESPATH."easyparliament/hansardlist.php");
include_once (INCLUDESPATH."easyparliament/commentlist.php");
include_once (INCLUDESPATH."easyparliament/comment.php");
include_once (INCLUDESPATH."easyparliament/trackback.php");

// Added in as new module by Richard Allan MP
include_once (INCLUDESPATH."easyparliament/alert.php");

twfy_debug_timestamp("at end of init.php");

?>
