<?php

error_reporting (E_ALL);

define ("LONGDATEFORMAT", 		"j F Y"); 	// 31 December 2003
define ("SHORTDATEFORMAT", 		"j M Y");	// 31 Dec 2003
include_once dirname(__FILE__) . "/../../../conf/general";

$DATA = new \MySociety\TheyWorkForYou\Data;

include_once (INCLUDESPATH."utility.php");

// Start execution timers for database
global $mysqltotalduration;
$mysqltotalduration = 0.0;
$global_connection = null;
