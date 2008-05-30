<?php
/*
 * For displaying day of MP stuff for matching against playing video
 */
 
global $gid_want; # XXX

twfy_debug("TEMPLATE", "hansard_search_video.php");

?>

<p style="font-size:larger;background-color:#ccccff;padding:6px;">Okay, here
are the search results for that for the current day. Hopefully you recognise
one of them!</p>

<?

if (isset ($data['rows']) && count($data['rows']) > 0) {
	echo '<dl id="searchresults">';
	foreach ($data['rows'] as $n => $row) {
		echo '<dt>';
		if (isset($row['parent']) && count($row['parent']) > 0) {
			echo ('<strong>' . $row['parent']['body'] . '</strong>');
		}
		echo '</dt> <dd>';
		if (isset($row['speaker']) && count($row['speaker'])) {
			$sp = $row['speaker'];
			echo "<em>" . ucfirst(member_full_name($sp['house'], $sp['title'], $sp['first_name'], $sp['last_name'], $sp['constituency'])) . "</em>: ";
		} 
		
		echo '&#8220;' . $row['body'] . "&#8221;</dd>\n";
		echo '<dd><a href="distance.php?gid=' . $gid_want . '&amp;at=' . $row['gid'] . '">This speech is playing</a></dd>';
	}
	echo '</dl>';
} else {
	echo '<p>No data to display.</p>';
}

