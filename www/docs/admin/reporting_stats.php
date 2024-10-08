<?php

include_once '../../includes/easyparliament/init.php';
include_once '../../includes/easyparliament/member.php';

$this_page = "admin_reportstats";

$PAGE->page_start();
$PAGE->stripe_start();
$PAGE->block_start(['id' => 'alerts', 'title' => 'Reporting Statistics']);
$db = new ParlDB();
$q = $db->query('select year(created) as the_year, month(created) as the_month, count(*) as c from alerts where confirmed and not deleted group by year(created) desc, month(created) desc');
print '<h3>Alert signups per month</h3> <table>';

print '<table cellpadding="5">';
print '<thead><tr><th>Year</th><th style="padding-right: 15px;">Month</th><th>Sign Ups</th><tr></thead>';

foreach ($q as $row) {
    $year = $row['the_year'];
    $month = $row['the_month'];
    $count = $row['c'];
    $shade = $i % 2 == 1 ? 'style="background-color: #eee"' : '';
    print "<tr $shade><td>$year</td><td align='center'>$month</td><td align='right'>$count</td></tr>";
}

print '</table>';

$PAGE->block_end();


$menu = $PAGE->admin_menu();
$PAGE->stripe_end([
    [
        'type'		=> 'html',
        'content'	=> $menu,
    ],
]);

$PAGE->page_end();
