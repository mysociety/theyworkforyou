<?php

global $all_news;

// News content is in here
require_once "editme.php";

$c = 0;
foreach ($all_news as $id => $news_row) {
	if ($c++ == 2) break;
	list($title, $content, $date) = $news_row;
	$url = news_individual_link($date, $title);
	print "<h5><a href=\"$url\">" . $title . "</a></h5>";
	print "<p>";
	print trim_characters(news_format_body($content), 0, 250);
	print " <a href=\"$url\">Read more...</a>";
	print "</p>";
}

?>

<p>
<a href="/news/index.rdf">Site News as RSS</a></p>


