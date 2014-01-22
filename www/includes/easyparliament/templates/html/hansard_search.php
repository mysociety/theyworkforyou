<?php
// For displaying the Hansard search results.

// Remember, we are currently within the HANSARDLIST class,
// in the render() function.

// The array $data will be packed full of luverly stuff about hansard objects.
// See the bottom of hansard_gid.php for general information about its structure and contents...

/*

$data['info'] = array (
	'results_per_page' => 10,
	'total_results' => 1240,
	'first_result' => 0,
	'page' => 1,
	's' => 'fox+hunting'
);
*/

global $PAGE, $this_page, $GLOSSARY, $hansardmajors;

twfy_debug("TEMPLATE", "hansard_search.php");

$info = $data['info'];
$searchdescription = $data['searchdescription'];

# XXX Hack
if (preg_match('#budget#', $searchdescription)) { ?>
<p class="informational"><a href="http://www.theyworkforyou.com/debates/?id=2013-03-20a.931.0">Read the Budget speech and debate</a></p>
<?php }

if (isset($info['total_results']) && $info['total_results'] > 0) {

	// If we're getting matches and no glossary entry, we can trigger them to add a definition
	// Obviously, only if it's a proper noun
	if (($info['total_results'] > 0) && ($GLOSSARY['num_search_matches'] == 0)) {
		// I'll be leaving this empty for now, pending search engine improvements...
	}

	$last_result = $info['first_result'] + $info['results_per_page'] - 1;

	if ($last_result > $info['total_results']) {
		$last_result = $info['total_results'];
	}

	print "\t\t\t\t<h3 style='font-weight:normal'>Results <strong>" . number_format($info['first_result']) . '-' . number_format($last_result) . '</strong> of ' . number_format($info['total_results']) . " for <strong>" . htmlentities($searchdescription) . "</strong></h3>\n";

} elseif ($info['total_results'] == 0) {
	echo '<h3 style="font-weight:normal">Your search for <strong>', htmlentities($searchdescription), '</strong> did not match anything.</h3>';
}

if ($info['spelling_correction']) {
        $u = new URL('search');
	$u->insert(array('s' => $info['spelling_correction']));
    	echo '<p><big>Did you mean: <a href="' . $u->generate(), '">', $info['spelling_correction'] . '</a>?</big></p>';
}

if ($match = get_http_var('match')) {
	echo '<p><big>Hansard only refers to previous answers/statements by column number, so we don&rsquo;t know exactly what
was being referred to. Help us out by picking the right result and clicking &ldquo;This is the correct match&rdquo; next to it.
You&rsquo;ll be taken back to the page you came from, but hopefully then the link will go directly to the section you want.</big></p>';
}

if (isset ($data['rows']) && count($data['rows']) > 0) {

	echo '<dl id="searchresults">';
	for ($i=0; $i<count($data['rows']); $i++) {

		$row = $data['rows'][$i];
		echo '<dt><a href="', $row['listurl'], '">';
		if (isset($row['parent']) && count($row['parent']) > 0) {
			echo ('<strong>' . $row['parent']['body'] . '</strong>');
		}
		echo '</a> <small>(' . format_date($row['hdate'], LONGDATEFORMAT) . ')';
		if (isset($row['video_status']) && ($row['video_status'] == 5 || $row['video_status'] == 7)) {
			echo ' <em>has video</em> ';
		}
		if (isset($row['collapsed']) && $row['collapsed'] && $row['subsection_id']) {
		    $URL = new URL('search');
		    $URL->insert(array('s' => $info['s'] . " segment:$row[subsection_id]" ));
		    echo ' <a href="', $URL->generate(), '">See ', $row['collapsed'],
		   	' other result', $row['collapsed']>1?'s':'', ' from this ',
			$hansardmajors[$row['major']]['singular'], '</a>';
		}
		echo '</small>';
		if ($match = get_http_var('match')) {
			echo ' &ndash; <a href="/search/record.php?result=', $row['gid'] , '&amp;match=', htmlspecialchars($match), '">This is the correct match</a>';
		}
		echo '</dt> <dd><p>';
		if (isset($row['speaker']) && count($row['speaker'])) {
			$sp = $row['speaker'];
			echo "<em>" . ucfirst(member_full_name($sp['house'], $sp['title'], $sp['first_name'], $sp['last_name'], $sp['constituency'])) . "</em>";
            if ($row['extract']) echo ": ";
		}

		echo $row['extract'] . "</p></dd>\n";

	}

	echo '</dl> <!-- end searchresults -->';
	$PAGE->page_links($info);
	$PAGE->search_form($info['s']);

}
// else, no results.
