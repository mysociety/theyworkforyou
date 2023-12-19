<?php

/********************************************************************************
First some things to help make our PHP nicer and betterer
********************************************************************************/

error_reporting (E_ALL ^ E_DEPRECATED);

/********************************************************************************
Now some constants that are the same for live and dev versions
(unlike those variables in conf/general)
********************************************************************************/

// In case we need to switch these off globally at some point...
define ("ALLOWCOMMENTS", true);

// These variables are so we can keep date/time formats consistent across the site
// and change them easily.
// Formats here: http://www.php.net/manual/en/function.date.php
define ("LONGERDATEFORMAT",		"%A, %e %B %Y");// Monday, 31 December 2003
define ("LONGDATEFORMAT", 		"%e %B %Y"); 	// 31 December 2003
define ("SHORTDATEFORMAT", 		"%e %b %Y");	// 31 Dec 2003
define ("TIMEFORMAT", 			"%l:%M %p");	// 11:59 pm

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

bindtextdomain('TheyWorkForYou', BASEDIR . '/../../locale');
if (substr($_SERVER['SERVER_NAME'] ?? '', 0, 2) == 'cy') {
    define('LANGUAGE', 'cy');
    setlocale(LC_ALL, 'cy_GB.UTF-8');
    putenv('LC_ALL=cy_GB.UTF-8');
} else {
    define('LANGUAGE', 'en');
    setlocale(LC_ALL, 'en_GB.UTF-8');
    putenv('LC_ALL=en_GB.UTF-8');
}
textdomain('TheyWorkForYou');

// Set the default timezone
if(function_exists('date_default_timezone_set')) {
    date_default_timezone_set(TIMEZONE);
}

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

$DATA = new \MySociety\TheyWorkForYou\Data;

class ParlDB extends \MySociety\TheyWorkForYou\Db\Connection {
    public function __construct() {
        $this->init (OPTION_TWFY_DB_HOST, OPTION_TWFY_DB_USER, OPTION_TWFY_DB_PASS, OPTION_TWFY_DB_NAME);
    }
}

$filter = new \MySociety\TheyWorkForYou\Utility\LibFilter;

include_once (INCLUDESPATH."easyparliament/user.php");

// Test to see if this is a new-style template using the renderer class.
if (! isset($new_style_template) or $new_style_template !== true) {

    // This is an old-style page. Use the old page classes.
    include_once (INCLUDESPATH."easyparliament/page.php");

}

include_once (INCLUDESPATH."easyparliament/hansardlist.php");
include_once (INCLUDESPATH."dbtypes.php");
include_once (INCLUDESPATH."easyparliament/commentlist.php");
include_once (INCLUDESPATH."easyparliament/comment.php");

// Added in as new module by Richard Allan MP
include_once (INCLUDESPATH."easyparliament/alert.php");

twfy_debug_timestamp("at end of init.php");
