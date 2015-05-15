<?php

include_once '../../includes/easyparliament/init.php';
include_once '../../includes/easyparliament/member.php';

$this_page = "alert_stats";

$PAGE->page_start();
$PAGE->stripe_start();
$PAGE->block_start(array ('id'=>'alerts', 'title'=>'Alert Statistics'));
$db = new ParlDB;

$q_confirmed = $db->query('select count(*) as c from alerts where confirmed and not deleted');
$q_not_confirmed = $db->query('select count(*) as c from alerts where not confirmed and not deleted');
$q_deleted = $db->query('select count(*) as c from alerts where confirmed and deleted');
$q_speaker = $db->query('select count(*) as c from alerts where criteria like "%speaker:%" and confirmed and not deleted');
$q_keyword = $db->query("select count(*) as c from alerts where criteria not like '%speaker:%' and confirmed and not deleted");

print '<h3>Headline stats</h3> <table>';
$data = array(
    'header' => array( 'Alert Type', 'Count' ),
    'rows' => array(
        array( 'Confirmed', $q_confirmed->field('0', 'c') ),
        array( 'Not Confirmed', $q_not_confirmed->field('0', 'c') ),
        array( 'Deleted', $q_deleted->field('0', 'c') ),
        array( 'For a Speaker<sup>*</sup>', $q_speaker->field('0', 'c') ),
        array( 'For a Keyword', $q_keyword->field('0', 'c') ),
    )
);

$PAGE->display_table($data);

print '<p>* Includes alerts for speaker and keyword</p>';

$q = $db->query('select alert_id, criteria from alerts where criteria not like "%speaker:%" and criteria like "%,%" and confirmed and not deleted');
print '<h3>People who probably wanted separate signups</h3>';
$rows = array();
for ($i=0; $i<$q->rows(); $i++) {
    $id = $q->field($i, 'alert_id');
    $criteria = $q->field($i, 'criteria');
    $rows[] = array($id, $critera);
}
$data = array( 'rows' => $rows );
$PAGE->display_table($data);

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

$people_header = array( 'Name', 'Confirmed', 'Unconfirmed');
print '<h3>Alert signups by MP/Peer</h3>';
$rows = array();
foreach ($tots as $person_id => $c) {
    $u = isset($unconfirmed[$person_id]) ? $unconfirmed[$person_id] : 0;
    $rows[] = array( $name[$person_id], $c, $u );
}
$data = array(
    'header' => array( 'Name', 'Confirmed', 'Unconfirmed'),
    'rows' => $rows
);
$PAGE->display_table($data);

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
print '<h3>Alert signups by keyword</h3>';
$rows = array();
foreach ($total as $criteria => $tot) {
    $c = isset($confirmed[$criteria]) ? $confirmed[$criteria] : 0;
    $u = isset($unconfirmed[$criteria]) ? $unconfirmed[$criteria] : 0;
    $rows[] = array( $criteria, $c, $u );
}
$data = array(
    'header' => array( 'Criteria', 'Confirmed', 'Unconfirmed'),
    'rows' => $rows
);
$PAGE->display_table($data);
$PAGE->block_end();
$menu = $PAGE->admin_menu();
$PAGE->stripe_end(array(
    array(
        'type'		=> 'html',
        'content'	=> $menu
    )
));
$PAGE->page_end();
