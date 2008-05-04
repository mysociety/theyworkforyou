<?php

$rep = preg_replace('#S$#', 's', strtoupper($this_page));

$URL = new URL($this_page);
$URL->insert(array('f'=>'csv'));
$csvurl = $URL->generate();

$URL->reset();
$URL->insert(array('all'=>1));
$allurl = $URL->generate();

$this->block_start(array('title'=>'Relevant links'));
echo '<ul><li><a href="', $csvurl, '">Download a CSV file that you can load into Excel</a></li>';
echo '<li><a href="', $allurl, '">Historical list of all ', $rep, '</a></li>';
echo '</ul>';
$this->block_end();
