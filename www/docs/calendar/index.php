<?php

include_once "../../includes/easyparliament/init.php";

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
	$recess = recess_prettify(date('j', $time), date('n', $time), date('Y', $time), 1);
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

} else {

	header("Location: http://" . DOMAIN . "/");
	exit;

}

