<?php
# vim:sw=4:ts=4:et:nowrap

include_once "../../../includes/easyparliament/init.php";
include_once INCLUDESPATH."easyparliament/member.php";
include_once INCLUDESPATH."easyparliament/glossary.php";

// From http://cvs.sourceforge.net/viewcvs.py/publicwhip/publicwhip/website/
include_once INCLUDESPATH."postcode.inc";

if (get_http_var('s') != '' or get_http_var('maj') != '' or get_http_var('pid') != '') {

	// We're searching for something.
	
	$this_page = 'search';	

	$searchstring = trim(get_http_var('s'));
	// Get rid of any HTML.
	$searchstring = filter_user_input($searchstring, 'strict');
	$searchspeaker = trim(get_http_var('pid'));
	if ($searchspeaker) {
		$searchstring .= " speaker:" . $searchspeaker;
    }
	$searchmajor = trim(get_http_var('section'));
	if (!$searchmajor) {
        // Legacy URLs used maj
		$searchmajor = trim(get_http_var('maj'));
	}
    if ($searchmajor) {
        $searchstring .= " section:" . $searchmajor;
    }
   	$searchgroupby = trim(get_http_var('groupby'));
    if ($searchgroupby) {
        $searchstring .= " groupby:" . $searchgroupby;
    } // We have only one of these, rather than one in HANSARDLIST also
    global $SEARCHENGINE;
    $SEARCHENGINE = new SEARCHENGINE($searchstring); 
	
	$pagetitle = "Search: " . $SEARCHENGINE->query_description_short();
	
	$pagenum = get_http_var('p');
	if (is_numeric($pagenum) && $pagenum > 1) {
		$pagetitle .= " page $pagenum";
	}
	
	$DATA->set_page_metadata($this_page, 'title', $pagetitle);
	
	$args = array (
		's' => $searchstring,
		'p' => $pagenum,
        'pop' => get_http_var('pop'),
        'o' => get_http_var('o'),
	);
	
	$LIST = new HANSARDLIST();
	$LIST->display('search', $args, 'rss');
}

?>
