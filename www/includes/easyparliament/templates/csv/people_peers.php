<?php

/*
Used on the 'All MPs' page to produce the list of MPs in CSV format.
*/

global $this_page;

twfy_debug("TEMPLATE", "people_peers.php");

$order = $data['info']['order'];

header('Content-Type: text/csv');
print "Person ID,Name,Party,URI";
print "\n";

foreach ($data['data'] as $n => $peer) {
	render_peers_row($peer, $order);
}

function render_peers_row($peer, $order) {
	global $parties;
	$name = member_full_name(2, $peer['title'], $peer['first_name'], $peer['last_name'], $peer['constituency']);
	if (strstr($name, ',')) $name = "\"$name\"";
	print $peer['person_id'] . ',' . ucfirst($name) . ',';
	if (array_key_exists($peer['party'], $parties))
		print $parties[$peer['party']];
	else
		print $peer['party'];
	print ',' .  'http://www.theyworkforyou.com/peer/' . 
		make_member_url($name, NULL, 2, $peer['person_id']);
	print "\n";
}

?>
