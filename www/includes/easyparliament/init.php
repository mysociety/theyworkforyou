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

// The Composer autoloader, which also handles our internal autoloading magic.
require_once dirname(__FILE__) . '/../../../vendor/autoload.php';

include_once dirname(__FILE__) . '/../../../conf/general';
include_once INCLUDESPATH . 'utility.php';
twfy_debug_timestamp("after including utility.php");

// Set the default timezone
if(function_exists('date_default_timezone_set')) date_default_timezone_set(TIMEZONE);

// Only do clever things with errors if we're not testing, otherwise show as default

if (!(defined('TESTING') && TESTING == true)) {

    // The error_handler function is in includes/utility.php
    $error_level = E_ALL & ~E_NOTICE;
    if (DEVSITE) {
        $error_level = E_ALL | E_STRICT;
    } elseif (version_compare(phpversion(), "5.3") >= 0) {
        $error_level = $error_level & ~E_DEPRECATED;
    }

    set_error_handler("error_handler", $error_level);

    // Decide how to handle exceptions (send to Whoops or use the legacy handler)
    if (DEVSITE) {
        $whoops = new \Whoops\Run;
        $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
        $whoops->register();
    } else {
        set_exception_handler("exception_handler");
    }

}

// The time the page starts, so we can display the total at the end.
// getmicrotime() is in utiltity.php.
define ("STARTTIME", getmicrotime());
if (!isset($_SERVER['WINDIR'])) {
    $rusage = getrusage();
    define ('STARTTIMES', $rusage['ru_stime.tv_sec']*1000000 + $rusage['ru_stime.tv_usec']);
    define ('STARTTIMEU', $rusage['ru_utime.tv_sec']*1000000 + $rusage['ru_utime.tv_usec']);
}

include_once (INCLUDESPATH."dbtypes.php");

$DATA = new \MySociety\TheyWorkForYou\Data;

// Start execution timers for database
global $mysqltotalduration;
$mysqltotalduration = 0.0;
$global_connection = null;

$filter = new lib_filter();

// Instantiate a new global $THEUSER object when every page loads.
$THEUSER = new \MySociety\TheyWorkForYou\TheUser;

// Test to see if this is a new-style template using the renderer class.
if (! isset($new_style_template) OR $new_style_template !== TRUE) {

    // This is an old-style page. Old style page class is autoloaded.
    // We load Gaze manually, as it is used by the Page class.
    include_once INCLUDESPATH . '../../commonlib/phplib/gaze.php';
    $PAGE = new \MySociety\TheyWorkForYou\Page;

}

if (defined('XAPIANDB') AND XAPIANDB != '') {
    if (file_exists('/usr/share/php/xapian.php')) {
        include_once '/usr/share/php/xapian.php';
    } else {
        twfy_debug('SEARCH', '/usr/share/php/xapian.php does not exist');
    }
}

global $SEARCHENGINE;
$SEARCHENGINE = null;

global $SEARCHLOG;
$SEARCHLOG = new \MySociety\TheyWorkForYou\SearchLog();

twfy_debug_timestamp("at end of init.php");
