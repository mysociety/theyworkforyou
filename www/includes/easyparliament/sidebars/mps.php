<?php

$URL = new URL($this_page);
$URL->insert(array('f'=>'csv'));
$csvurl = $URL->generate();

$this->block_start(array('title'=>"This data as a spreadsheet",
	'url' => $csvurl,
	'body'=>''));
?>
<p>
	Vacher Dods charge <a href="http://www.dods.co.uk/engine.asp?showPage=article&id=2564"> 95 pounds </a> for this list of MPs names. We think democracy should be free. Click <?php echo '<a href="'.$csvurl.'">here</a>'; ?> to download a CSV (Comma Separated Values) file that you can load into Excel.
</p>
<?php
$this->block_end();
?>
