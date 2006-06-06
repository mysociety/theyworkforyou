<?php
// For displaying latest MP stuff, using search db rather than MySQL for quickness.

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

global $hansardmajors;

twfy_debug("TEMPLATE", "hansard_search_min.php");

$info = $data['info'];
$searchdescription = $data['searchdescription'];

if (isset ($data['rows']) && count($data['rows']) > 0) {
	?>
				<dl id="searchresults">
<?php
	foreach ($data['rows'] as $n => $row) {
		?>
					<dt><a href="<?php echo $row['listurl']; ?>"><?php
		if (isset($row['parent']) && count($row['parent']) > 0) {
			echo ('<strong>' . $row['parent']['body'] . '</strong>');
		}
		echo ('</a> <small>(' . format_date($row['hdate'], SHORTDATEFORMAT));
		echo ')</small>';
		?></dt>
					<dd><p>&#8220;<?php
		
		echo $row['body'] . "&#8221;</p></dd>\n";
	}
	?>
				</dl>

<?php 

} else { ?>
<p>No data to display.</p>
<?php
}

