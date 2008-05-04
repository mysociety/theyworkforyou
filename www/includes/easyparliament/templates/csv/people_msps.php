<?php

/*
Used on the 'All MPs' page to produce the list of MPs in CSV format.
*/

global $this_page;

twfy_debug("TEMPLATE", "people_msps.php");

$order = $data['info']['order'];

header('Content-Type: text/csv');
print "Person ID,Name,Party,Constituency,URI";
print "\n";

foreach ($data['data'] as $n => $msp) {
	render_msps_row($msp, $order);
}

function render_msps_row($msp, $order) {
	global $parties;
	$con = html_entity_decode($msp['constituency']);
	if (strstr($con, ',')) $con = "\"$con\"";
	$name = member_full_name(4, $msp['title'], $msp['first_name'], $msp['last_name'], $msp['constituency']);
	if (strstr($name, ',')) $name = "\"$name\"";
	print $msp['person_id'] . ',' . ucfirst($name) . ',';
	if (array_key_exists($msp['party'], $parties))
		print $parties[$msp['party']];
	else
		print $msp['party'];
	print ',' . $con . ',' .  'http://www.theyworkforyou.com/msp/' . 
		make_member_url($msp['first_name'].' '.$msp['last_name']);
	print "\n";
}
