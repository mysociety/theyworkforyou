<?php

error_reporting (E_ALL);

define ("LONGDATEFORMAT", 		"j F Y"); 	// 31 December 2003
define ("SHORTDATEFORMAT", 		"j M Y");	// 31 Dec 2003
include_once dirname(__FILE__) . "/../../../conf/general";

$DATA = new \MySociety\TheyWorkForYou\Data;

include_once (INCLUDESPATH."utility.php");
include_once (INCLUDESPATH."mysql.php");

