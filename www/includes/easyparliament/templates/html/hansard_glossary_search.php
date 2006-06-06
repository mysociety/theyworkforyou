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

twfy_debug("TEMPLATE", "hansard_glossary_search.php");

$info = $data['info'];
$searchdescription = $data['searchdescription'];

if (isset($info['total_results']) && $info['total_results'] > 0) {

	$last_result = $info['first_result'] + $info['results_per_page'] - 1;

	if ($last_result > $info['total_results']) {
		$last_result = $info['total_results'];
	}

	//print "\t\t\t\t<h3>Results " . number_format($info['first_result']) . '-' . number_format($last_result) . ' of ' . number_format($info['total_results']) . " items " . htmlentities($searchdescription) . "</h3>\n";

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
		
		if (isset($row['speaker']['first_name'])) {
			echo "<em>" . $row['speaker']['first_name'] . ' ' . $row['speaker']['last_name'] . "</em>: ";
		} 
		
		echo $row['body'] . "</p></dd>\n";
	
	}
	
	?>
				</dl> <!-- end searchresults -->

<?php 


}
// else, no results.


?>
