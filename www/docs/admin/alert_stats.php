<?php

include_once '../../includes/easyparliament/init.php';
include_once '../../includes/easyparliament/member.php';

$this_page = "alert_stats";

$PAGE->page_start();
$PAGE->stripe_start();
$PAGE->block_start(['id' => 'alerts', 'title' => 'Alert Statistics']);
$db = new ParlDB();

$q_confirmed = $db->query('select count(*) as c from alerts where confirmed and not deleted')->first()['c'];
$q_not_confirmed = $db->query('select count(*) as c from alerts where not confirmed and not deleted')->first()['c'];
$q_deleted = $db->query('select count(*) as c from alerts where confirmed and deleted')->first()['c'];
$q_speaker = $db->query('select count(*) as c from alerts where criteria like "%speaker:%" and confirmed and not deleted')->first()['c'];
$q_keyword = $db->query("select count(*) as c from alerts where criteria not like '%speaker:%' and confirmed and not deleted")->first()['c'];

print '<h3>Headline stats</h3> <table>';
$data = [
    'header' => [ 'Alert Type', 'Count' ],
    'rows' => [
        [ 'Confirmed', $q_confirmed ],
        [ 'Not Confirmed', $q_not_confirmed ],
        [ 'Deleted', $q_deleted ],
        [ 'For a Speaker<sup>*</sup>', $q_speaker ],
        [ 'For a Keyword', $q_keyword ],
    ],
];

$PAGE->display_table($data);

print '<p>* Includes alerts for speaker and keyword</p>';

$q = $db->query('select alert_id, criteria from alerts where criteria not like "%speaker:%" and criteria like "%,%" and confirmed and not deleted');
print '<h3>People who probably wanted separate signups</h3>';
$rows = [];
foreach ($q as $row) {
    $id = $row['alert_id'];
    $criteria = $row['criteria'];
    $rows[] = [$id, $critera];
}
$data = [ 'rows' => $rows ];
$PAGE->display_table($data);

$q = $db->query('select count(*) as c, criteria from alerts where criteria like "speaker:%" and confirmed and not deleted group by criteria order by c desc');
$tots = [];
$name = [];
foreach ($q as $row) {
    $c = $row['c'];
    $criteria = $row['criteria'];
    if (!preg_match('#^speaker:(\d+)#', $criteria, $m)) {
        continue;
    }
    $person_id = $m[1];
    $MEMBER = new MEMBER(['person_id' => $person_id]);
    if ($MEMBER->valid) {
        if (!array_key_exists($person_id, $tots)) {
            $tots[$person_id] = 0;
        }
        $tots[$person_id] += $c;
        $name[$person_id] = $MEMBER->full_name();
    }
}
$q = $db->query('select count(*) as c, criteria from alerts where criteria like "speaker:%" and not confirmed group by criteria order by c desc');
$unconfirmed = [];
foreach ($q as $row) {
    $c = $row['c'];
    $criteria = $row['criteria'];
    if (!preg_match('#^speaker:(\d+)#', $criteria, $m)) {
        continue;
    }
    $person_id = $m[1];
    if (isset($name[$person_id])) {
        if (!array_key_exists($person_id, $unconfirmed)) {
            $unconfirmed[$person_id] = 0;
        }
        $unconfirmed[$person_id] += $c;
    }
}

$people_header = [ 'Name', 'Confirmed', 'Unconfirmed'];
print '<h3>Alert signups by MP/Peer</h3>';
$rows = [];
foreach ($tots as $person_id => $c) {
    $u = $unconfirmed[$person_id] ?? 0;
    $rows[] = [ $name[$person_id], $c, $u ];
}
$data = [
    'header' => [ 'Name', 'Confirmed', 'Unconfirmed'],
    'rows' => $rows,
];
$PAGE->display_table($data);

$unconfirmed = [];
$confirmed = [];
$total = [];
$q = $db->query("select count(*) as c, criteria from alerts where criteria not like '%speaker:%' and confirmed and not deleted group by criteria having c>1 order by c desc");
foreach ($q as $row) {
    $c = $row['c'];
    $criteria = $row['criteria'];
    $confirmed[$criteria] = $c;
    $total[$criteria] = 1;
}
$q = $db->query("select count(*) as c, criteria from alerts where criteria not like '%speaker:%' and not confirmed group by criteria having c>1 order by c desc");
foreach ($q as $row) {
    $c = $row['c'];
    $criteria = $row['criteria'];
    $unconfirmed[$criteria] = $c;
    $total[$criteria] = 1;
}
print '<h3>Alert signups by keyword</h3>';
$rows = [];
foreach ($total as $criteria => $tot) {
    $c = $confirmed[$criteria] ?? 0;
    $u = $unconfirmed[$criteria] ?? 0;
    $rows[] = [ $criteria, $c, $u ];
}
$data = [
    'header' => [ 'Criteria', 'Confirmed', 'Unconfirmed'],
    'rows' => $rows,
];
$PAGE->display_table($data);
$PAGE->block_end();
$menu = $PAGE->admin_menu();
$PAGE->stripe_end([
    [
        'type'		=> 'html',
        'content'	=> $menu,
    ],
]);
$PAGE->page_end();
