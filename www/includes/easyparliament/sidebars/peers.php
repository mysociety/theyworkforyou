<?php

$URL = new URL($this_page);
$URL->insert(array('f'=>'csv'));
$csvurl = $URL->generate();

$this->block_start(array('title'=>"This data as a spreadsheet",
	'url' => $csvurl,
	'body'=>''));
?>
<p>
	<?php echo '<a href="'.$csvurl.'">Download a CSV file that you can load into Excel</a>'; ?>.
</p>
<?php
$this->block_end();
?>
