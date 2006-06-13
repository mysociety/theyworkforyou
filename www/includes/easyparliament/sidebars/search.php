<?php
// This sidebar is on the search page.

$this->block_start(array(
	'id'	=> 'help',
	'title'	=> "Search Tips"
));

include  INCLUDESPATH."easyparliament/searchhelp.php";

$this->block_end();
?>
