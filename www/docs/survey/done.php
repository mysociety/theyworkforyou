<?php
/*
 * survey/done.php:
 * Say thank you.
 *  
 * Copyright (c) 2009 UK Citizens Online Democracy. All rights reserved.
 * Email: matthew@mysociety.org. WWW: http://www.mysociety.org
 *
 * $Id: done.php,v 1.3 2009-09-18 10:37:25 matthew Exp $
 * 
 */

include_once "../../includes/easyparliament/init.php";
$this_page = 'survey_done';
$PAGE->page_start();

setcookie('survey', '2', time()+60*60*24*365, '/');

?>

<h2>Many thanks for filling in our survey</h2>

<div id="survey">

<p>Thanks very much for your input.
If you have any questions regarding this survey, please feel free to
email <a href="mailto:tobias&#64;mysociety.org">tobias&#64;mysociety.org</a>.</p>

</div>

<?

$PAGE->page_end ();

