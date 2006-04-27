<?php
/* 
 * Name: dobs.php
 * Description: Match up person ids
 * $Id: dobs.php,v 1.1 2006-04-27 14:20:20 twfy-live Exp $
 */

include '/data/vhost/www.theyworkforyou.com/includes/easyparliament/init.php';
include INCLUDESPATH . 'easyparliament/member.php';
$db = new ParlDB;
$f = file('../../DoBs.csv');
foreach ($f as $r) {
	$a = explode('|', $r);
	$q = $db->query('SELECT person_id FROM member WHERE member_id = ' . $a[0]);
	print $q->field(0, 'person_id') . '|' . trim($a[1]) . '|' . trim($a[2]) . "\n";
}
