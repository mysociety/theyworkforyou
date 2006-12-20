<?php

/*
Used on the 'All MPs' page to produce the list of MPs in CSV format.
*/

global $this_page;

twfy_debug("TEMPLATE", "people_mlas.php");

$order = $data['info']['order'];

print "Person ID,Name,Party,Constituency,URI";
print "\n";

foreach ($data['data'] as $n => $mla) {
	render_mlas_row($mla, $order);
}

function render_mlas_row($mla, $order) {
	global $parties;
	$con = html_entity_decode($mla['constituency']);
	if (strstr($con, ',')) $con = "\"$con\"";
	$name = member_full_name(3, $mla['title'], $mla['first_name'], $mla['last_name'], $mla['constituency']);
	if (strstr($name, ',')) $name = "\"$name\"";
	print $mla['person_id'] . ',' . ucfirst($name) . ',';
	if (array_key_exists($mla['party'], $parties))
		print $parties[$mla['party']];
	else
		print $mla['party'];
	print ',' . $con . ',' .  'http://www.theyworkforyou.com/mla/' . 
		make_member_url($mla['first_name'].' '.$mla['last_name']);
	print "\n";
}
