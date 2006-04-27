<?php
global $PAGE;

// The calendar that appears in sidebars linking to debates.
// There is a separate one for wrans (so we can have both on the same page).

// Contents varies depending on the page we're on...

if ($this_page == 'wransday') {
	$date = get_http_var('d');
	if (preg_match('#^(\d\d\d\d)-(\d\d)-(\d\d)$#', $date, $m)) {
		$year = $m[1]; $month = $m[2]; $day = $m[3];
		$args = array (
			'year' => $year,
			'month' => $month,
			'onday' => $date
		);
		$title = 'Answers this month';
	} else {
		$args = array(
			'months' => 1
		);
		$title = 'Recent written answers';
	}
} else {
	$args = array (
		'months' => 1	// How many recent months to show.
	);
	$title = 'Recent written answers';
}

$PAGE->block_start(array('title'=>$title));


$LIST = new WRANSLIST;

$LIST->display('calendar', $args);


$PAGE->block_end();

?>
