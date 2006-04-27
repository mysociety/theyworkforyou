<?php

include_once '../../includes/easyparliament/init.php';
include_once INCLUDESPATH . 'easyparliament/commentreportlist.php';
include_once INCLUDESPATH . 'easyparliament/searchengine.php';
include_once INCLUDESPATH . 'easyparliament/member.php';

$this_page = 'admin_alerts';

$db = new ParlDB;

$PAGE->page_start();
$PAGE->stripe_start();

print '<h4>Statistics</h4>';
$q = $db->query('SELECT COUNT(*) AS c FROM alerts');
$total = $q->field(0, 'c');
$q = $db->query('SELECT COUNT(*) AS c FROM alerts WHERE confirmed=1 AND deleted=0');
$active = $q->field(0, 'c');
$q = $db->query('SELECT COUNT(*) AS c FROM alerts WHERE deleted=1');
$deleted = $q->field(0, 'c');
$q = $db->query('SELECT COUNT(*) AS c FROM alerts WHERE confirmed=0');
$unconfirmed = $q->field(0, 'c');
$rows = array(array('Total', $total), array('Active', $active), array('Deleted', $deleted), array('Unconfirmed', $unconfirmed));
$tabledata = array (
	'header' => array('Stat', 'Number'),
	'rows' => $rows
);
$PAGE->display_table($tabledata);

$order = 'email, alert_id';
if (isset($_GET['o']) && $_GET['o'] == 'c') $order = 'created, alert_id';

print '<h4>Active alerts</h4>';
$q = $db->query('SELECT email,criteria,created FROM alerts WHERE confirmed=1 AND deleted=0 ORDER BY '.$order);
$tabledata = array (
	'header' => array('<a href="alerts.php">Email</a>', 'Criteria', '<a href="alerts.php?o=c">Created</a>'),
	'rows' => generate_rows($q)
);
$PAGE->display_table($tabledata);

print '<h4>Deleted alerts</h4>';
$q = $db->query('SELECT email,criteria,created FROM alerts WHERE deleted=1 ORDER BY '.$order);
$tabledata['rows'] = generate_rows($q);
$PAGE->display_table($tabledata);

print '<h4>Unconfirmed alerts</h4>';
$q = $db->query('SELECT email,criteria,created FROM alerts WHERE confirmed=0 ORDER BY '.$order);
$tabledata['rows'] = generate_rows($q);
$PAGE->display_table($tabledata);

$menu = $PAGE->admin_menu();
$PAGE->stripe_end(array(
	array(
		'type'		=> 'html',
		'content'	=> $menu
	)
));

$PAGE->page_end();

function generate_rows($q) {
	global $db;
	$rows = array();
	$USERURL = new URL('userview');
	for ($row=0; $row<$q->rows(); $row++) {
		$email = $q->field($row, 'email');
		$criteria = $q->field($row, 'criteria');
		$SEARCHENGINE = new SEARCHENGINE($criteria);
		$r = $db->query("SELECT user_id,firstname,lastname FROM users WHERE email = '" . mysql_escape_string($email) . "'");
		if ($r->rows() > 0) {
			$user_id = $r->field(0, 'user_id');
			$USERURL->insert(array('u'=>$user_id));
			$name = '<a href="'. $USERURL->generate() . '">' . $r->field(0, 'firstname') . ' ' . $r->field(0, 'lastname') . '</a>';
		} else {
			$name = $email;
		}
		$created = $q->field($row, 'created');
		if ($created == '0000-00-00 00:00:00') $created = '&nbsp;';
		$rows[] = array($name, $SEARCHENGINE->query_description_long(), $created);
	}
	return $rows;
}

?>
