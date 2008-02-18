<?php
// This sidebar is on the search page.

$rss = $DATA->page_metadata($this_page, 'rss');

global $SEARCHENGINE;
$email_text = '';
if ($SEARCHENGINE) {
	$value = get_http_var('s');
	$person_id = get_http_var('pid');
	$email_link = '/alert/?only=1' . ($value ? '&amp;keyword='.urlencode($value) : '') .
		($person_id ? '&amp;pid='.urlencode($person_id) : '');
	$email_text = $SEARCHENGINE->query_description_long();
}

if ($email_text || $rss) {
	$this->block_start(array( 'title' => "Being alerted to new search results"));
	echo '<ul id="search_links">';
	if ($email_text) {
		echo '<li id="search_links_email"><a href="', $email_link, '">Subscribe to an email alert</a> for ', $email_text, '</li>';
	}
	if ($rss) {
		echo '<li id="search_links_rss">Or <a href="/', $rss, '">get an RSS feed</a></li>';
	}
	echo '</ul>';
	$this->block_end();
}
