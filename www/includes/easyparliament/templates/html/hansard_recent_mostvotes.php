<?php
// For displaying most highly-voted on recent speeches.
// Used on the home page.

// Remember, we are currently within the DEBATELIST or WRANSLISTS class,
// in the render() function.

// The array $data will be packed full of luverly stuff about hansard objects.
// See the bottom of hansard_gid for information about its structure and contents...

global $PAGE;

debug ("TEMPLATE", "hansard_recentvotes.php");

if (isset ($data['rows']) && count($data['rows']) > 0) {
	$PAGE->block_start(array('title'=>'Most interesting speeches from past ' . $data['info']['days'] . ' days'));
?>
						<ol>
<?php

	foreach ($data['rows'] as $n => $row) {
		
		// While we're linking to individual speeches,
		// the text is the body of the parent, ie (sub)section.
		$title = $row['parent']['body'];
		
		if (isset($row['listurl'])) {
			$title = "<a href=\"" . $row['listurl'] . "\">$title</a>";
		}
		
		if (isset($row['speaker']) && isset($row['speaker']['member_id'])) {
			$URL = new URL('member');
			$URL->insert(array('id'=>$row['speaker']['member_id']));
			$member = '<a href="' . $URL->generate() . '">' . $row['speaker']['first_name'] . ' ' . $row['speaker']['last_name'] . '</a>: ';
		} else {
			$member = '';
		}
		?>
						<li><p><strong><?php echo $title; ?></strong><br />
							<?php echo $member; ?>&#8220;<?php echo trim_characters($row['body'], 0, 200); ?>&#8221;</p></li>
<?php
		
	}
	
	?>
						</ol>
<?php
	$PAGE->block_end();


} // End display of rows.


?>
