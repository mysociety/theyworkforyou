<?php

error_reporting (E_ALL);

define ("LONGDATEFORMAT", 		"j F Y"); 	// 31 December 2003
define ("SHORTDATEFORMAT", 		"j M Y");	// 31 Dec 2003
include_once dirname(__FILE__) . "/../../../conf/general";
include_once (INCLUDESPATH."data.php");
include_once (INCLUDESPATH."utility.php");
include_once (INCLUDESPATH."mysql.php");

Class ParlDB extends MySQL {
    public function ParlDB() {
        $this->init (OPTION_TWFY_DB_HOST, OPTION_TWFY_DB_USER, OPTION_TWFY_DB_PASS, OPTION_TWFY_DB_NAME);
    }
}
