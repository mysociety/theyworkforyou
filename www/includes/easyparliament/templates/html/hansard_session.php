<?php
// For displaying the PBCs of a session.

// Remember, we are currently within the StandingCommittee class
// in the render() function.

// The array $data will be packed full of luverly stuff about hansard objects.
// See the bottom of hansard_gid for information about its structure and contents...

global $PAGE, $DATA, $this_page, $hansardmajors;

twfy_debug("TEMPLATE", "hansard_session.php");

$PAGE->page_start();
$PAGE->stripe_start();

print "\t\t\t\t<ul class=\"hansard-day\">\n";
foreach ($data as $row) {

	// Cycle through each row...
	print "\t\t\t\t<li>";
	print '<a href="' . $row['url'] . '"><strong>' . $row['title'] . '</strong></a> ';

	$plural = $row['contentcount'] == 1 ? 'speech' : 'speeches';
	$moreinfo = array(
		$row['contentcount'] . " $plural"
	);
	#if ($row['totalcomments'] > 0) {
	#	$plural = $row['totalcomments'] == 1 ? 'comment' : 'comments';
	#	$moreinfo[] = $row['totalcomments'] . " $plural";
	#}
	if (count($moreinfo) > 0) {
		print "<small>(" . implode (', ', $moreinfo) . ") </small>";
	}	
}
print "\n\t\t\t\t</ul> <!-- end hansard-day -->\n";

$sidebar = $hansardmajors[$this->major]['sidebar'];

$PAGE->stripe_end(array(
	array (
		'type' 	=> 'nextprev'
	),
	array (
		'type' => 'include',
		'content' => 'calendar_'.$sidebar
	),
	array (
		'type'	=> 'include',
		'content'	=> $sidebar
	)
));

?>
