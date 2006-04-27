<?php
// For displaying recent Hansard items for MPs.

// Remember, we are currently within the HANSARDLIST, DEBATELIST or WRANSLISTS class,
// in the render() function.

// The array $data will be packed full of luverly stuff about hansard objects.
// See the bottom of hansard_gid for information about its structure and contents...

global $PAGE, $hansardmajors;

debug ("TEMPLATE", "hansard_person.php");


if (isset ($data['rows']) && count($data['rows']) > 0) {

	foreach ($data['rows'] as $n => $row) {
		
		// While we're linking to individual speeches,
		// the text is the body of the parent, ie (sub)section.
		$text = $row['parent']['body'];
		
		if (isset($row['listurl'])) {
			// So we can link to the 'More recent appearances' precisely.
			$count = $n + 1;
			$text = "<a name=\"n$count\"></a><strong><a href=\"" . $row['listurl'] . "\">$text</a></strong> ";
		}
				
		$text .= '<small>' . format_date($row['hdate'], SHORTDATEFORMAT);

		if ($hansardmajors[$row['major']]['type'] == 'debate') {
			$plural = $row['total_speeches'] == 1 ? 'speech' : 'speeches';
			$text .= ' (' . $row['total_speeches'] . " $plural)";
		}
		
		$text .= '</small>';
		
		$text = "\t\t\t\t<p>$text<br />\n\t\t\t\t&#8220;" . trim_characters($row['body'], 0, 200) . "&#8221;</p>\n";
		
		print $text;
	}


} // End display of rows.

else {

	?>
<p>No data to display.</p>
<?php
}


?>
