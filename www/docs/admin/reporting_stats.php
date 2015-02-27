<?php

include_once '../../includes/easyparliament/init.php';
include_once '../../includes/easyparliament/member.php';

$this_page = "reporting_stats";

$PAGE->page_start();
$PAGE->stripe_start();
$PAGE->block_start(array ('id'=>'alerts', 'title'=>'Reporting Statistics'));
$db = new ParlDB;
$q = $db->query('select year(created) as the_year, month(created) as the_month, count(*) as c from alerts where confirmed and not deleted group by year(created) desc, month(created) desc');
print '<h3>Alert signups per month</h3> <table>';
for ($i=0; $i<$q->rows(); $i++) {
    $year = $q->field($i, 'the_year');
    $month = $q->field($i, 'the_month');
    $count = $q->field($i, 'c');
    print "<tr><td>$year</td><td>$month</td><td>$count</td></tr>";
}
print '</table>';

$PAGE->block_end();


$menu = $PAGE->admin_menu();
$PAGE->stripe_end(array(
    array(
        'type'		=> 'html',
        'content'	=> $menu
    )
));

$PAGE->page_end();
