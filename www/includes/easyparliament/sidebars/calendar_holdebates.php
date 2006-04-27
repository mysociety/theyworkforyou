<?php
global $PAGE;

// The calendar that appears in sidebars linking to debates.
// There is a separate one for wrans (so we can have both on the same page).

// Contents varies depending on the page we're on...

if ($this_page == 'lordsdebatesday') {
	$date = get_http_var('d');
	list($year, $month, $day) = explode('-', $date);
	
	$args = array (
		'year' => $year,
		'month' => $month,
		'onday' => $date
	);
	$title = 'Debates this month';
	
} else {
	$args = array (
		'months' => 1	// How many recent months to show.
	);
	$title = 'Recent debates';
}

$PAGE->block_start(array('title'=>$title));

$LIST = new LORDSDEBATELIST;

$LIST->display('calendar', $args);

$PAGE->block_end();
?>
