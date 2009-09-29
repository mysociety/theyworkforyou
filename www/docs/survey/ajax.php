<?php
/*
 * survey/ajax.php:
 * Record displaying of survey teaser.
 *  
 * Copyright (c) 2009 UK Citizens Online Democracy. All rights reserved.
 * Email: matthew@mysociety.org. WWW: http://www.mysociety.org
 *
 * $Id: ajax.php,v 1.1 2009-09-29 11:45:54 matthew Exp $
 * 
 */

include_once "../../includes/easyparliament/init.php";
$this_page = 'survey_ajax';

$db = new ParlDB;
$db->query('update survey set shown = shown + 1');

echo '1';

