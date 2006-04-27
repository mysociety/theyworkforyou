<?php

include_once "../../includes/easyparliament/init.php";
include_once "../../includes/easyparliament/member.php";

$this_page = "alert_stats";

$PAGE->page_start();
$PAGE->stripe_start();
$PAGE->block_start(array ('id'=>'alerts', 'title'=>'Alert Statistics'));
$db = new ParlDB;
$q = $db->query('select alert_id, criteria from alerts where criteria not like "%speaker:%" and criteria like "%,%" and confirmed and not deleted');
print '<h3>People who probably wanted separate signups</h3> <table>';
for ($i=0; $i<$q->rows(); $i++) {
	$id = $q->field($i, 'alert_id');
	$criteria = $q->field($i, 'criteria');
	print "<tr><td>$id</td><td>$criteria</td></tr>";
}
print '</table>';

$q = $db->query('select count(*) as c, criteria from alerts where criteria like "speaker:%" and confirmed and not deleted group by criteria order by c desc');
$tots = array(); $name = array();
for ($i=0; $i<$q->rows(); $i++) {
	$c = $q->field($i, 'c');
	$criteria = $q->field($i, 'criteria');
	if (!preg_match('#^speaker:(\d+)#', $criteria, $m)) continue;
	$person_id = $m[1];
	$MEMBER = new MEMBER(array('person_id'=>$person_id));
	if ($MEMBER->valid) {
		if (!array_key_exists($person_id, $tots)) $tots[$person_id] = 0;
		$tots[$person_id] += $c;
		$name[$person_id] = $MEMBER->full_name();
	}
}
$q = $db->query('select count(*) as c, criteria from alerts where criteria like "speaker:%" and not confirmed group by criteria order by c desc');
$unconfirmed = array();
for ($i=0; $i<$q->rows(); $i++) {
	$c = $q->field($i, 'c');
	$criteria = $q->field($i, 'criteria');
	if (!preg_match('#^speaker:(\d+)#', $criteria, $m)) continue;
	$person_id = $m[1];
	if (isset($name[$person_id])) {
		if (!array_key_exists($person_id, $unconfirmed)) $unconfirmed[$person_id] = 0;
		$unconfirmed[$person_id] += $c;
	}
}
print '<h3>Alert signups by MP/Peer</h3> <table><tr><th>Name</th><th>Confirmed</th><th>Unconfirmed</th></tr> ';
foreach ($tots as $person_id => $c) {
	$u = isset($unconfirmed[$person_id]) ? $unconfirmed[$person_id] : 0;
	print "<tr><td>";
	print $name[$person_id];
	print "</td><td>$c</td><td>$u</td></tr>";
}
print '</table>';

$unconfirmed = array();
$confirmed = array();
$total = array();
$q = $db->query("select count(*) as c, criteria from alerts where criteria not like '%speaker:%' and confirmed and not deleted group by criteria having c>1 order by c desc");
for ($i=0; $i<$q->rows(); $i++) {
	$c = $q->field($i, 'c');
	$criteria = $q->field($i, 'criteria');
	$confirmed[$criteria] = $c;
	$total[$criteria] = 1;
}
$q = $db->query("select count(*) as c, criteria from alerts where criteria not like '%speaker:%' and not confirmed group by criteria having c>1 order by c desc");
for ($i=0; $i<$q->rows(); $i++) {
	$c = $q->field($i, 'c');
	$criteria = $q->field($i, 'criteria');
	$unconfirmed[$criteria] = $c;
	$total[$criteria] = 1;
}
print '<h3>Alert signups by keyword</h3> <table><tr><th>Criteria</th><th>Confirmed</th><th>Unconfirmed</th></tr>';
foreach ($total as $criteria => $tot) {
	$c = isset($confirmed[$criteria]) ? $confirmed[$criteria] : 0;
	$u = isset($unconfirmed[$criteria]) ? $unconfirmed[$criteria] : 0;
	print "<tr><td>$criteria</td><td>$c</td><td>$u</tr>";
}
print '</table>';
$PAGE->block_end();	
$PAGE->stripe_end();
$PAGE->page_end(); 

?>


