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

global $PAGE, $this_page, $GLOSSARY;

twfy_debug("TEMPLATE", "hansard_search.php");

$info = $data['info'];
$searchdescription = $data['searchdescription'];

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

	print "\t\t\t\t<h3>Results " . number_format($info['first_result']) . '-' . number_format($last_result) . ' of ' . number_format($info['total_results']) . " items " . htmlentities($searchdescription) . "</h3>\n";

} elseif ($info['total_results'] == 0) {
	?>
	<h3>Your search for items <?php echo htmlentities($searchdescription); ?> did not match anything in Hansard</h3>

<?php
}

if (isset ($data['rows']) && count($data['rows']) > 0) {

	?>
				<dl id="searchresults">
<?php
	for ($i=0; $i<count($data['rows']); $i++) {
	
		$row = $data['rows'][$i];
		
		?>
					<dt><a href="<?php echo $row['listurl']; ?>"><?php
		if (isset($row['parent']) && count($row['parent']) > 0) {
			echo ('<strong>' . $row['parent']['body'] . '</strong>');			
		}
		echo ('</a> <small>(' . format_date($row['hdate'], SHORTDATEFORMAT) . ')</small>');
		?></dt>
					<dd><p><?php
		
		if (isset($row['speaker']) && count($row['speaker'])) {
			$sp = $row['speaker'];
			echo "<em>" . ucfirst(member_full_name($sp['house'], $sp['title'], $sp['first_name'], $sp['last_name'], $sp['constituency'])) . "</em>: ";
		} 
		
		echo $row['body'] . "</p></dd>\n";
	
	}
	
	?>
				</dl> <!-- end searchresults -->

<?php 

	$PAGE->page_links($info);
	
	$PAGE->search_form();

}
// else, no results.


?>
