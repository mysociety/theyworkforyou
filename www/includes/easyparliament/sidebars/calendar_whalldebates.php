<?php
global $PAGE;

// The calendar that appears in sidebars linking to debates.
// There is a separate one for wrans (so we can have both on the same page).

// Contents varies depending on the page we're on...

if ($this_page == 'whallday') {
	$date = get_http_var('d');
	if (preg_match('#^(\d\d\d\d)-(\d\d)-(\d\d)$#', $date, $m)) {
		$year = $m[1]; $month = $m[2]; $day = $m[3];
		$args = array (
			'year' => $year,
			'month' => $month,
			'onday' => $date
		);
		$title = 'Westminster Hall debates this month';
	} else {
		$args = array(
			'months' => 1
		);
		$title = 'Recent Westminster Hall debates';
	}
} else {
	$args = array (
		'months' => 1	// How many recent months to show.
	);
	$title = 'Recent Westminster Hall debates';
}

$PAGE->block_start(array('title'=>$title));


$LIST = new WHALLLIST;

$LIST->display('calendar', $args);


$PAGE->block_end();
?>
