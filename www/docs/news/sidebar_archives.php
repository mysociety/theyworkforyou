<?php

// News content is in here
require_once "editme.php";

print "<p>";

$last = "";
global $all_news;
foreach ($all_news as $id => $news_row) {
	list($title, $content, $date) = $news_row;

	$ym = substr($date, 0, 7);
	if ($ym != $last) {
		$url = WEBPATH . "news/archives/".str_replace("-", "/", $ym);
		print "<a href=\"$url\">".format_date($ym."-01", "F Y")."</a>";
		print "<br>";
		$last = $ym;
	}
	
	#	print "<h3>" . format_date(substr($date, 0, 10), LONGDATEFORMAT) . "</h3>";
}

print "</p>";

?>

