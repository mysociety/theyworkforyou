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
    $cur_year = intval(strftime('%G'));
	foreach ($data['rows'] as $n => $row) {
		?>
					<dt><a href="<?php echo $row['listurl']; ?>"><?php
		if (isset($row['parent']) && count($row['parent']) > 0) {
			echo ('<strong>' . $row['parent']['body'] . '</strong>');
		}
		echo '</a>';
		if (isset($row['video_status']) && ($row['video_status'] == 5 || $row['video_status'] == 7)) {
			echo ' <small><em>has video</em></small> ';
		}
    $year = 0; intval(format_date($row['hdate'], 'y'));
		?></dt>
        <dd>
        <div class="appearance-date">
            <div class="day"><?php echo format_date($row['hdate'], 'j' ) ?></div><!--
            <?php if ( $year < $cur_year ) { ?>
            --><div class="month"><?php echo format_date($row['hdate'], 'M y' ) ?></div>
            <?php } else { ?>
            --><div class="month"><?php echo format_date($row['hdate'], 'M' ) ?></div>
            <?php } ?>
        </div>

        <p>&#8220;<?php
		
		echo $row['extract'] . "&#8221;</p></dd>\n";
	}
	?>
				</dl>

<?php 

} else { ?>
<p>No data to display.</p>
<?php
}

