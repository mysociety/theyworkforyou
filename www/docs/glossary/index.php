<?php
if ($term) {
	$this_page = 'glossary_item';
}
else {
	$this_page = "glossary";
}

include_once "../../includes/easyparliament/init.php";
include_once INCLUDESPATH."easyparliament/glossary.php";


$args = array(
	'sort'				=> "regexp_replace",
	'glossary_id'		=> ""
);

if (get_http_var('gl')) {
	// We've already got something, so display it.
	$this_page = 'glossary';
	if (is_numeric(get_http_var('gl'))) {
		$args['glossary_id'] = filter_user_input(get_http_var('gl'), 'strict');
	}
}

// Stop the title generator making nasty glossary titles.
//$DATA->set_page_metadata ('help_us_out', 'title', '');

$GLOSSARY = new GLOSSARY($args);

$term = $GLOSSARY->current_term;

// Check if we're on a letter index page
if ((get_http_var('az') != '') && is_string(get_http_var('az'))) {
	// we have a letter!
	// make sure it's only one and uppercase
	$az = strtoupper(substr(get_http_var('az'), 0, 1));
}

// Now check it's in the populated glossary alphabet
if (isset($az) && array_key_exists($az, $GLOSSARY->alphabet)) {
	$GLOSSARY->current_letter = $az;
// Otherwise make it the first letter of the current term
} elseif (isset($term)) {
	$GLOSSARY->current_letter = strtoupper($term['title']{0});
// Otherwise make it "A" by default
} else {
	$GLOSSARY->current_letter = "A";
}

if ($term) {
	$DATA->set_page_metadata($this_page, 'title', $term['title'].': Glossary item');
	$DATA->set_page_metadata($this_page, 'heading', $term['title']);
}
else {
	$DATA->set_page_metadata ($this_page, 'title', $GLOSSARY->current_letter.': Glossary index');
	$DATA->set_page_metadata ($this_page, 'heading', 'Glossary index');
}

$PAGE->page_start();
$PAGE->stripe_start();

$PAGE->glossary_atoz($GLOSSARY);

// Hiding the search box for now...
/*
$args['action'] = "help_us_out";
$PAGE->glossary_search_form($args);
*/

if($GLOSSARY->glossary_id != '') {
// Deal with a single instance in the form of a glossary_id
	
	// Set up next/prev for this page.
	$URL = new URL('glossary');
	$up_link = $URL->generate();
	$URL->insert(array("gl" => $GLOSSARY->previous_term['glossary_id']));
	$previous_link = $URL->generate('url');
	$URL->update(array("gl" => $GLOSSARY->next_term['glossary_id']));
	$next_link = $URL->generate('url');

	$nextprev = array (
		'next'	=> array (
			'url'	=> $next_link,
			'title'	=> 'Next term',
			'body'	=> $GLOSSARY->next_term['title']
		),
		'prev'	=> array (
			'url'	=> $previous_link,
			'title'	=> 'Previous term',
			'body'	=> $GLOSSARY->previous_term['title']
		)
		// Hiding this for the moment because "up" is fairly meaningless in this context
		/*,
		'up'	=> array (
			'url'	=> $up_link,
			'body'	=> 'Glossary front page',
			'title'	=> ''
		)*/
	);
	$DATA->set_page_metadata($this_page, 'nextprev', $nextprev);

	$PAGE->glossary_display_term($GLOSSARY);
	
} else {

	
	// Display the results
	if (isset($GLOSSARY->terms)) {
		?><ul class="glossary"><?
		$URL = new URL('glossary');
		foreach ($GLOSSARY->alphabet[$GLOSSARY->current_letter] as $glossary_id) {
			$URL->insert(array('gl' => $glossary_id));
			$term_link = $URL->generate('url');
			?><li><a href="<?php echo $term_link ?>"><?php echo $GLOSSARY->terms[$glossary_id]['title']; ?></a></li><?
		}
		?></ul><?
	}
}

$URL = new URL('glossary_addterm');
$add_url = $URL->generate();
print "<p>Think you know a phrase that should be here? Help us improve the site by <a href=\"".$add_url."\">adding it</a>.</p>";

$PAGE->stripe_end(array (
	array (
		'type'		=> 'nextprev',
		'content'	=> ''
	)
));

$PAGE->page_end();
?>