<?php

error_reporting (E_ALL);
ini_set("magic_quotes_runtime", 0);
ini_set('memory_limit', 16*1024*1024);

include_once "../../includes/easyparliament/config.php";
include_once (INCLUDESPATH."utility.php");
include_once (INCLUDESPATH."mysql.php");

Class ParlDB extends MySQL {
	function ParlDB () {
		$this->init (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	}
}

