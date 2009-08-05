<?php
/*
 * survey/done.php:
 * Say thank you.
 *  
 * Copyright (c) 2009 UK Citizens Online Democracy. All rights reserved.
 * Email: matthew@mysociety.org. WWW: http://www.mysociety.org
 *
 * $Id: done.php,v 1.1 2009-08-05 18:14:04 matthew Exp $
 * 
 */

include_once "../../includes/easyparliament/init.php";
$this_page = 'survey_done';
$PAGE->page_start();

?>

<h2>Many thanks for filling in our survey</h2>

<div id="survey">

<p>Thanks for that &ndash; the statistics should help us convince politicians that
they could do more on the Web to encourage greater participation in the
democratic process.
</p>

<p>If you've got any questions, feel free to <a href="/contact/">contact us</a>.</p>

</div>

<?

$PAGE->page_end ();

