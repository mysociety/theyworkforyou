<?php

include_once '../../includes/easyparliament/init.php';

// News content is in here
require_once "editme.php";

$uri = $_SERVER['REQUEST_URI'];
if (preg_match("#/(\d\d\d\d)/(\d\d)/(\d\d)/([a-z0-9_]+)(\.php)?$#", $uri, $matches)) {
	// Individual item
	list($all, $year, $month, $day, $ref) = $matches;
	if ($ref == "please_build_on_") $ref = "new_full_source_";
	foreach ($all_news as $id => $news_row) {
		list($title, $content, $date) = $news_row;
		if (news_format_ref($title) != $ref) continue;

		$this_page = 'sitenews_individual';
		$DATA->set_page_metadata($this_page, 'title', $title);
		$PAGE->page_start();
		$PAGE->stripe_start();
		print news_format_body($content);
		print "<p>Posted on " . format_date(substr($date, 0, 10), LONGDATEFORMAT) . " at " . substr($date, 11);
		print " | <a href=\"" . news_individual_link($date, $title) . "\">Link to this</a>";
		break;
	}
} elseif (preg_match("#/(\d\d\d\d)/(\d\d)/?(index.php)?$#", $uri, $matches)) {
	// Month index
	list($all, $year, $month) = $matches;
	$this_page = 'sitenews_date';
	$DATA->set_page_metadata($this_page, 'title', format_date("$year-$month-01", "F Y"));
	$PAGE->page_start();
	$PAGE->stripe_start();
	foreach (array_reverse($all_news) as $id => $news_row) {
		list($title, $content, $date) = $news_row;
		if (substr($date, 0, 7) != "$year-$month") continue;
		print "<h3>" . format_date(substr($date, 0, 10), LONGDATEFORMAT) . "</h3>";
		print "<h4>" . $title . "</h4>";
		print news_format_body($content);
		print "<p>Posted at " . substr($date, 11);
		print " | <a href=\"" . news_individual_link($date, $title) . "\">Link to this</a>";
	}
} else {
	// Front page /news
	$this_page = 'sitenews';
	$PAGE->page_start();
	$PAGE->stripe_start();
	$c = 0;
	foreach ($all_news as $id => $news_row) {
		if ($c++ == 10) break;
		list($title, $content, $date) = $news_row;
		print "<h3>" . format_date(substr($date, 0, 10), LONGDATEFORMAT) . "</h3>";
		print "<h4>" . $title . "</h4>";
		print news_format_body($content);
		print "<p>Posted at " . substr($date, 11);
		print " | <a href=\"" . news_individual_link($date, $title) . "\">Link to this</a>";
	}
}

$PAGE->stripe_end(array(
	array(
		'type'=>'include', 
		'content'=>'sitenews'
	)
));
$PAGE->page_end();
?>

