<?php

include_once '../../includes/easyparliament/init.php';
include_once INCLUDESPATH . 'easyparliament/commentreportlist.php';
include_once INCLUDESPATH . 'easyparliament/searchengine.php';
include_once INCLUDESPATH . 'easyparliament/member.php';
include_once INCLUDESPATH . 'easyparliament/people.php';

$this_page = 'admin_mpurls';

$db = new ParlDB;

$PAGE->page_start();
$PAGE->stripe_start();

#$q = $db->query('SELECT COUNT(*) AS c FROM alerts');
#$total = $q->field(0, 'c');
#$tabledata = array (
#	'header' => array('Stat', 'Number'),
#	'rows' => $rows
#);
#$PAGE->display_table($tabledata);

$out = list_members();

function list_members() {
    $out = '';
    $errors = array();
    #array_push($errors, 'Not got the photo.');
    $out .= <<<EOF

EOF;
    return $out;
}

$menu = $PAGE->admin_menu();
$PAGE->stripe_end(array(
	array(
		'type'		=> 'html',
		'content'	=> $menu
	)
));

$PAGE->page_end();

?>
