<?php
global $PAGE;

# XXX TODO

// The calendar that appears in sidebars linking to debates.

// Contents varies depending on the page we're on...

$args = array();
if ($this_page == '') {
	$date = get_http_var('d');
	$datebits = explode('-', $date);
	if (count($datebits)>2) {
		$args = array (
			'year' => $datebits[0],
			'month' => $datebits[1],
			'onday' => $date
		);
		$title = 'Debates this month';
	}
}
if (!$args) {
	$args = array (
		'months' => 1	// How many recent months to show.
	);
	$title = 'Recent debates';
}

$PAGE->block_start(array('title'=>$title, 'id'=>'calendar'));

$PAGE->block_end();

