<?php
include_once "../../includes/easyparliament/init.php";
require_once "share.php";
require_once "sharethis.php";

// Share page for non-JS browsers

$this_page = 'campaign';
$PAGE->page_start();
$PAGE->stripe_start();
$PAGE->block_start(array ('title'=>'Share the \'Free our Bills!\' campaign'));
freeourbills_styles();

freeourbills_share_page();

?>		<p><a href="/freeourbills">Return to 'Free our Bills' homepage</a> <?

$PAGE->block_end();
$PAGE->stripe_end();
$PAGE->page_end ();

