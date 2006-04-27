<?php
// For displaying the list of recent Debates/Wrans items.

// Remember, we are currently within the DEBATELIST or WRANSLISTS class,
// in the render() function.

// The array $data will be packed full of luverly stuff about hansard objects.
// See the bottom of hansard_gid for information about its structure and contents...

global $PAGE;

debug ("TEMPLATE", "hansard_recent.php");


// Will set the page headings and start the page HTML if it hasn't 
// already been started.
// Includes the next/prev links.
$PAGE->hansard_page_start($data['info']);

?>
<div class="hansard">

<?php


if (isset ($data['rows']) && $data['rows'] > 0) {

	?>
		<ul class="dates">
<?php

	foreach ($data['rows'] as $n => $row) {
		
		if (isset($row['listurl'])) {
			print "\t\t<li><a href=\"" . $row['listurl'] . "\">" . $row['body'] . "</a></li>\n";
		} else {
			print "\t\t<li>" . $row['body'] . "</li>\n";
		}
	}
	
	?>
		</ul>
<?php


} // End display of rows.

else {

	?>
<p>No data to display.</p>
<?php
}


?>
	<div class="break"></div>
</div> <!-- end hansard -->
