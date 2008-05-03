<?php

include_once "../../includes/easyparliament/init.php";

$number_of_debates_to_show = 6;
$number_of_wrans_to_show = 5;

$this_page = 'sp_home';
$PAGE->page_start();
// Page title will appear here.
$PAGE->stripe_start();
?>
<h3>Busiest Scottish Parliament debates from the most recent week</h3>
<?php
$DEBATELIST = new SPLIST;
$DEBATELIST->display('biggest_debates', array('days'=>7, 'num'=>$number_of_debates_to_show));

$MOREURL = new URL('spdebatesfront');
$anchor = $number_of_debates_to_show + 1;
?>
				<p><strong><a href="<?php echo $MOREURL->generate(); ?>#d<?php echo $anchor; ?>">See more debates</a></strong></p>
<?php

$PAGE->stripe_end(array(
	array (
		'type' => 'include',
		'content' => "spdebates"
	),
	array (
		'type' => 'include',
		'content' => "calendar_spdebates"
	)
));

$PAGE->stripe_start();
?>
<h3>Some recent written answers</h3>
<?php

$WRANSLIST = new SPWRANSLIST;
$WRANSLIST->display('recent_wrans', array('days'=>7, 'num'=>$number_of_wrans_to_show));

$MOREURL = new URL('spwransfront');
?>
				<p><strong><a href="<?php echo $MOREURL->generate(); ?>">See more written answers</a></strong></p>
<?php

$PAGE->stripe_end(array(
	array (
		'type' => 'include',
		'content' => "calendar_spwrans"
	)
));

$PAGE->page_end();
