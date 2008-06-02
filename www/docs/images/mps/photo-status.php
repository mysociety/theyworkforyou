<?php

include_once "../../../includes/easyparliament/init.php";
include_once "../../../includes/easyparliament/member.php";
$DATA->set_page_metadata($this_page, 'heading', 'MPs photo status on TheyWorkForYou');
$PAGE->page_start();
$PAGE->stripe_start();
$db = new ParlDB;
$query = 'SELECT person_id, first_name, last_name, constituency, party
	FROM member
	WHERE house=1 AND left_house = (SELECT MAX(left_house) FROM member) ';
$q = $db->query($query . "ORDER BY last_name, first_name");
$out = array('both'=>'', 'small'=>'', 'none'=>array());
for ($i=0; $i<$q->rows(); $i++) {
	$p_id = $q->field($i, 'person_id');
	list($dummy, $sz) = find_rep_image($p_id);
	if ($sz == 'L') {
		$out['both'] .= $q->field($i, 'first_name') . ' ' . $q->field($i, 'last_name') . ', ';
	} elseif ($sz == 'S') {
		$out['small'] .= $q->field($i, 'first_name') . ' ' . $q->field($i, 'last_name') . ', ';
	} else {
		array_push($out['none'], '<li>' . $q->field($i, 'first_name') . ' ' . $q->field($i, 'last_name') . ' (' . $q->field($i, 'party') . ')' . ', ' . $q->field($i, 'constituency'));
	}
}
print '<h3>Missing completely ('.count($out['none']).')</h3> <ul>';
print join($out['none'], "\n");
print '</ul>';
print '<h3>Large and small</h3> <p>';
print $out['both'];
print '<h3>Only small photos</h3> <p>';
print $out['small'];
$PAGE->stripe_end();
$PAGE->page_end();

