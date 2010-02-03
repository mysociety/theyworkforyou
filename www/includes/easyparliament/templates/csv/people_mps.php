<?php

/*
Used on the 'All MPs' page to produce the list of MPs in CSV format.
*/

global $this_page;

twfy_debug ("TEMPLATE", "people_mps.php");

$order = $data['info']['order'];

header('Content-Type: text/csv');
print "Person ID,First name,Last name,Party,Constituency,URI";
if ($order == 'expenses') print ', 2004 Expenses Grand Total';
elseif ($order == 'debates') print ',Debates spoken in the last year';
print "\n";

foreach ($data['data'] as $n => $mp) {
	render_mps_row($mp, $order);
}

function render_mps_row($mp, $order) {
	global $parties;
	$con = html_entity_decode($mp['constituency']);
	if (strstr($con, ',')) $con = "\"$con\"";
	print $mp['person_id'] . ',';
	print $mp['first_name'] . ',' . $mp['last_name'] . ',';
	if (array_key_exists($mp['party'], $parties))
		print $parties[$mp['party']];
	else
		print $mp['party'];
	print ',' . $con . ',' .  'http://www.theyworkforyou.com/mp/' . 
		make_member_url($mp['first_name'].' '.$mp['last_name'], $mp['constituency']);
	if ($order == 'expenses') print ', £' . $mp['data_value'];
	elseif ($order == 'debates') print ', ' . $mp['data_value'];
	print "\n";
}

?>
