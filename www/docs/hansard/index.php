<?php

include_once "../../includes/easyparliament/init.php";

$number_of_debates_to_show = 6;
$number_of_wrans_to_show = 5;

if (($date = get_http_var('d')) && preg_match('#^\d\d\d\d-\d\d-\d\d$#', $date)) {
	$this_page = 'hansard_date';
	$PAGE->set_hansard_headings(array('date'=>$date));
	$URL = new URL($this_page);
	$db = new ParlDB;
	$q = $db->query("SELECT MIN(hdate) AS hdate FROM hansard WHERE hdate > '$date'");
	if ($q->rows() > 0 && $q->field(0, 'hdate') != NULL) {
		$URL->insert( array( 'd'=>$q->field(0, 'hdate') ) );
		$title = format_date($q->field(0, 'hdate'), SHORTDATEFORMAT);
		$nextprevdata['next'] = array (
			'hdate'         => $q->field(0, 'hdate'),
			'url'           => $URL->generate(),
			'body'          => 'Next day',
			'title'         => $title
		);
	}
	$q = $db->query("SELECT MAX(hdate) AS hdate FROM hansard WHERE hdate < '$date'");
	if ($q->rows() > 0 && $q->field(0, 'hdate') != NULL) {
		$URL->insert( array( 'd'=>$q->field(0, 'hdate') ) );
		$title = format_date($q->field(0, 'hdate'), SHORTDATEFORMAT);
		$nextprevdata['prev'] = array (
			'hdate'         => $q->field(0, 'hdate'),
			'url'           => $URL->generate(),
			'body'          => 'Previous day',
			'title'         => $title
		);
	}
	#	$year = substr($date, 0, 4);
	#	$URL = new URL($hansardmajors[1]['page_year']);
	#	$URL->insert(array('y'=>$year));
	#	$nextprevdata['up'] = array (
		#		'body'  => "All of $year",
		#		'title' => '',
		#		'url'   => $URL->generate()
		#	);
	$DATA->set_page_metadata($this_page, 'nextprev', $nextprevdata);
	$PAGE->page_start();
	$PAGE->stripe_start();
	include_once INCLUDESPATH . 'easyparliament/recess.php';
	$time = strtotime($date);
	$dayofweek = date('w', $time);
	$recess = recess_prettify(date('j', $time), date('n', $time), date('Y', $time));
	if ($recess[0]) {
		print '<p>The Houses of Parliament are in their ' . $recess[0] . ' at this time.</p>';
	} elseif ($dayofweek == 0 || $dayofweek == 6) {
		print '<p>The Houses of Parliament do not meet at weekends.</p>';
	} else {
		$data = array(
			'date' => $date
		);
		foreach (array_keys($hansardmajors) as $major) {
			$URL = new URL($hansardmajors[$major]['page_all']);
			$URL->insert(array('d'=>$date));
			$data[$major] = array('listurl'=>$URL->generate());
		}
		major_summary($data);
	}
	$PAGE->stripe_end(array(
		array (
			'type' 	=> 'nextprev'
		),
	));
	$PAGE->page_end();
	exit;
}

$this_page = 'hansard';
$PAGE->page_start();
// Page title will appear here.
$PAGE->stripe_start('head-1');
$message = $PAGE->recess_message();
if ($message != '') {
	print "<p><strong>$message</strong></p>\n";
}
$PAGE->stripe_end();
$PAGE->stripe_start();
?>
				<h3>Busiest House of Commons debates from the most recent week</h3>
<?php
$DEBATELIST = new DEBATELIST;
$DEBATELIST->display('biggest_debates', array('days'=>7, 'num'=>$number_of_debates_to_show));

$MOREURL = new URL('debatesfront');
$anchor = $number_of_debates_to_show + 1;
?>
				<p><strong><a href="<?php echo $MOREURL->generate(); ?>#d<?php echo $anchor; ?>">See more debates</a></strong></p>
<?php

$PAGE->stripe_end(array(
	array (
		'type' => 'include',
		'content' => "hocdebates_short"
	),
	array (
		'type' => 'include',
		'content' => "calendar_hocdebates"
	)
));

$PAGE->stripe_start();
?>
				<h3>Busiest House of Lords debates from the most recent week</h3>
<?php
$DEBATELIST = new LORDSDEBATELIST;
$DEBATELIST->display('biggest_debates', array('days'=>7, 'num'=>$number_of_debates_to_show));

$MOREURL = new URL('lordsdebatesfront');
$anchor = $number_of_debates_to_show + 1;
?>
				<p><strong><a href="<?php echo $MOREURL->generate(); ?>#d<?php echo $anchor; ?>">See more debates</a></strong></p>
<?php

$PAGE->stripe_end(array(
	array (
		'type' => 'include',
		'content' => "holdebates_short"
	),
	array (
		'type' => 'include',
		'content' => "calendar_holdebates"
	)
));

$PAGE->stripe_start();
?>
				<h3>Some recent written answers</h3>
<?php

$WRANSLIST = new WRANSLIST;
$WRANSLIST->display('recent_wrans', array('days'=>7, 'num'=>$number_of_wrans_to_show));

$MOREURL = new URL('wransfront');
?>
				<p><strong><a href="<?php echo $MOREURL->generate(); ?>">See more written answers</a></strong></p>
<?php

$PAGE->stripe_end(array(
	array (
		'type' => 'include',
		'content' => "wrans_short"
	),
	array (
		'type' => 'include',
		'content' => "calendar_wrans"
	)
));

$PAGE->stripe_start();

?>
				<h3>Busiest Westminster Hall debates from the most recent week</h3>
<?php
$WHALLLIST = new WHALLLIST;
$WHALLLIST->display('biggest_debates', array('days'=>7, 'num'=>$number_of_debates_to_show));

$MOREURL = new URL('whallfront');
$anchor = $number_of_debates_to_show + 1;
?>
				<p><strong><a href="<?php echo $MOREURL->generate(); ?>#d<?php echo $anchor; ?>">See more debates</a></strong></p>
<?php

$PAGE->stripe_end(array(
	array (
		'type' => 'include',
		'content' => "whalldebates_short"
	),
	array (
		'type' => 'include',
		'content' => "calendar_whalldebates"
	)
));

$PAGE->stripe_start();

?>
				<h3>Some recent Written Ministerial Statements</h3>
<?php
$WMSLIST = new WMSLIST;
$WMSLIST->display('recent_wms', array('days'=>7, 'num'=>$number_of_wrans_to_show));
$MOREURL = new URL('wmsfront');
?>
				<p><strong><a href="<?php echo $MOREURL->generate(); ?>">See more written ministerial statements</a></strong></p>
<?php
$PAGE->stripe_end(array(
	array( 'type' => 'include', 'content' => 'wms_short' ),
	array( 'type' => 'include', 'content' => 'calendar_wms' )
));

$PAGE->stripe_start();
?>
	<h3>Busiest Northern Ireland Assembly debates from the most recent month</h3>
<?php
$DEBATELIST = new NILIST;
$DEBATELIST->display('biggest_debates', array('days'=>30, 'num'=>$number_of_debates_to_show));

$MOREURL = new URL('nidebatesfront');
$anchor = $number_of_debates_to_show + 1;
?>
		<p><strong><a href="<?php echo $MOREURL->generate(); ?>#d<?php echo $anchor; ?>">See more debates</a></strong></p>
<?php

$PAGE->stripe_end(array(
	array (
		'type' => 'include',
		'content' => "nidebates_short"
	),
	array (
		'type' => 'include',
		'content' => "calendar_nidebates"
	)
));
$PAGE->stripe_start();
?>

<p>Still to come: Select Committees, and much more...</p>

<?php
$PAGE->stripe_end();
$PAGE->page_end();
?>
