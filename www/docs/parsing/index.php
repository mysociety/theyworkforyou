<?php

include_once "../../includes/easyparliament/init.php";
$DATA->set_page_metadata($this_page, 'heading', 'Parsing status page');
$PAGE->page_start();
$PAGE->stripe_start();
$notloaded = '';
?>
<p>This page shows a brief summary of things to do with the parser used to provide
data for PublicWhip and TheyWorkForYou.</p>
<h4>HTML that hasn't been parsed into XML</h4>
<ul>
<?

$html = '/home/fawkes/parldata/cmpages/';
$xml = '/home/fawkes/parldata/scrapedxml/';
$dir = array('debates', 'wrans', 'wms', 'westminhall', 'lordspages', 'ni');
$majors = array(1,3,4,2,101,5);

$hdates = array();
$db = new ParlDB;
$q = $db->query('SELECT DISTINCT(hdate) AS hdate, major FROM hansard');
for ($i=0; $i<$q->rows(); $i++) {
	$hdates[$q->field($i, 'hdate')][$q->field($i, 'major')] = true;
}
foreach ($dir as $k=>$bit) {
	$out = array();
	$dh = opendir("$html$bit/");
	while (false !== ($filename = readdir($dh))) {
		if (substr($filename, -5)!='.html' || substr($filename, -8, 3)=='tmp') continue;
		#if ($bit=='lordspages' && substr($filename,7,4)!='2005') continue;
		preg_match('#^(.*?)(\d\d\d\d-\d\d-\d\d)(.*?)\.#', $filename, $m);
		$part = ucfirst($m[1]); $date = $m[2]; $version = $m[3];
		$stat = stat("$html$bit/$filename");
		$base = substr($filename, 0, -5);
		if (!is_file("$xml$bit/$base.xml")) {
			if ($date>'2001-05-11')
				$out[$date] = "<li>$date : $part version $version, size $stat[7] bytes, last modified ".date('Y-m-d H:i:s', $stat[9])."</li>\n";
		} else {
			if (!array_key_exists($date, $hdates) || !array_key_exists($majors[$k], $hdates[$date])) {
				$notloaded .= "<li>$date : $part version $version</li>\n";
			}
		}
	}
	closedir($dh);
	ksort($out);
	foreach ($out as $date => $str) {
		print $str;
	}
}

?>
</ul>

<h4>XML that hasn't loaded into the database</h4>
<?
if ($notloaded) print "<ul>$notloaded</ul>";

$PAGE->stripe_end();
$PAGE->page_end();
?>
