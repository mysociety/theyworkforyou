<?php
// This sidebar is on the search page.

$rss = $DATA->page_metadata($this_page, 'rss');
$email_text = '';
$email_text_anywhere = '';

# XXX global $searchstring is horrible
global $SEARCHENGINE, $searchstring;
if ($SEARCHENGINE) {
    $email_link = '/alert/?' . ($searchstring ? 'alertsearch='.urlencode($searchstring) : '');
    $email_text = $SEARCHENGINE->query_description_long();
}

$filter_ss = $searchstring;
$section = get_http_var('section');
if (preg_match('#\s*section:([a-z]*)#', $filter_ss, $m)) {
    $section = $m[1];
    $filter_ss = preg_replace("#\s*section:$section#", '', $filter_ss);
}
if ($section) {
    $search_engine = new SEARCHENGINE($filter_ss);
    $email_link_anywhere = '/alert/?' . ($filter_ss ? 'alertsearch='.urlencode($filter_ss) : '');
    $email_text_anywhere = $search_engine->query_description_long() . ' anywhere';
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
    if ($email_text_anywhere) {
        echo '<li id="search_links_email"><a href="', $email_link_anywhere, '">Subscribe to an email alert</a> for ', $email_text_anywhere, '</li>';
    }
    echo '</ul>';
    $this->block_end();
}
