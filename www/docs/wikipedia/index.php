<?php

include_once '../../includes/easyparliament/init.php';
$site = array(
	'194.60.38.10' => 'Parliament',
	'194.203.158.97' => 'Conservatives Central Headquarters',
	'217.207.36.186' => 'Plaid Cyrmu',
	'195.224.195.66' => 'Labour Party',
	'212.35.252.2' => 'Liberal Democrats',
);
$ip = get_http_var('ip');
if (!array_key_exists($ip, $site))
	$ip = '194.60.38.10';

$this_page = 'wikipedia';
$DATA->set_page_metadata($this_page, 'title', 'Latest 100 changes to Wikipedia by non-logged-in computers in ' . $site[$ip]);
$PAGE->page_start();
$PAGE->stripe_start();
?>
<p>
This page shows the changes made to Wikipedia by people who thought they were anonymous, but
were actually slightly less so due to their organisation using a web proxy or gateway.
So we can see that someone within Parliament keeps removing nasty truths from Anne Milton's entry,
and that someone in CCHQ has done similar for Oliver Letwin. We can see small, nice edits, and
we can see that someone on an idle Friday early lunch decided to just vandalize Andy Murray's page.
If you know any more IP addresses for political organisations we could add, do <a href="mailto:beta&#64;theyworkforyou.com">let us know at the usual address</a>.</p>
<p>And we haven't even touched on people <a href="http://www.masonskill.co.uk/">sending emails from a Parliament computer winding up a Freemason conspiracy theorist</a>...</p>
<p align="center"><?
$out = array();
foreach ($site as $k => $v) {
	if ($k == $ip)
		$o = "<strong>$v</strong>";
	else
		$o = "<a href=\"./?ip=$k\">$v</a>";
	$out[] = $o;
}
print join(' | ', $out);
?></p>
<style type="text/css">
table.diff, td.diff-otitle, td.diff-ntitle { background-color: white; }
td.diff-addedline { background: #cfc; }
td.diff-deletedline { background: #ffa; }
td.diff-context { background: #eee; }
span.diffchange { color: red; font-weight: bold; }
h3 { margin-top: 1em; }
</style>
<hr />

<?
$file = file_get_contents("cache/$ip");
preg_match_all('#<li>.*? \(<a[^>]*>hist</a>\) \(<a href=".*?title=(.*?)&.*?oldid=(.*?)"[^>]*>diff</a>\)  <a[^>]*>(.*?)</a> .*?</li>#', $file, $m, PREG_SET_ORDER);
foreach ($m as $row) {
	$file = file_get_contents("cache/$row[1].$row[2]");
	$file = str_replace(array("\xe2\x86\x90","\xe2\x86\x92"), array('&larr;', '&rarr;'), $file);
	print "<h3>$row[3]</h3>";
	if (preg_match('#<table.*?</table>#s', $file, $m))
		print preg_replace('#href=(\'|")(.*?)\1#', 'href=\1http://en.wikipedia.org\2\1', $m[0]);
	elseif (preg_match('#<div class="firstrevisionheader.*?</div>#s', $file, $m))
		print preg_replace('#href=(\'|")(.*?)\1#', 'href=\1http://en.wikipedia.org\2\1', $m[0]);
}


$PAGE->stripe_end();
$PAGE->page_end();

?>
