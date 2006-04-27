<?php
global $PAGE, $DATA, $this_page;

debug ("TEMPLATE", "hansard_mp.php");
$PAGE->page_start();
$PAGE->stripe_start();
?>
<style type="text/css">
table#questions td, table#questions th {
	border-bottom: solid 1px #999999;
}
</style>
<table id="questions">
<tr><th>Question</th><th>Answer</th></tr>
<?
foreach ($data['data'] as $row) {
	print '<tr><th colspan="2" align="left">' . $row['section_body'] . ' : ' . $row['subsection_body'];
	print ' (' . format_date($row['hdate'], LONGDATEFORMAT) . ')';
       	print '</th></tr>';
	print '<tr valign="top"><td>' . $row['question'] . '</td><td>' . $row['answer'] . '</td></tr>';
}
print '</table>';
print '<p></p>';
print $PAGE->page_links($data['info']);
?>
