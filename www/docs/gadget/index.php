<?php

$this_page = 'gadget';

include_once "../../includes/easyparliament/init.php";

$PAGE->page_start();
$PAGE->stripe_start();

?>

<p><img align="left" src="screenshot.png" width="280" height="341" alt="TheyWorkForYou google gadget screenshot" hspace="8">

As part of the <a href="http://www.google.co.uk/politics/">Google UK
Politics</a> site, we've created a TheyWorkForYou gadget that lets
you keep up to date with your MP's activities, search TheyWorkForYou,
and more, from your iGoogle page.</p>

<p>
<a href="http://www.google.co.uk/ig/adde?moduleurl=http://www.theyworkforyou.com/gadget/twfy.xml"><img src="http://www.google.co.uk/politics/images/add.gif" alt="Add to Google"></a>
</p>

<?

$includes = array(
	array (
		'type' => 'include',
		'content' => 'whatisthissite'
	),
);
$PAGE->stripe_end($includes);
$PAGE->page_end();

