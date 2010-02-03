<?php

$rep = preg_replace('#S$#', 's', strtoupper($this_page));

$URL = new URL($this_page);
$URL->insert(array('f'=>'csv'));
$csvurl = $URL->generate();

$URL->reset();
$URL->insert(array('all'=>1));
$allurl = $URL->generate();

$this->block_start(array('title'=>'Relevant links'));
echo "<ul><li><a href='$csvurl'>Download a CSV file that you can load into Excel</a></li>";
if ($this_page == 'mps') {
?>
<li><a href="?date=2005-05-05">MPs at 2005 general election</a></li>
<li><a href="?date=2001-06-07">MPs at 2001 general election</a></li>
<li><a href="?date=1997-05-01">MPs at 1997 general election</a></li>
<li><a href="?date=1992-04-09">MPs at 1992 general election</a></li>
<li><a href="?date=1987-06-11">MPs at 1987 general election</a></li>
<li><a href="?date=1983-06-09">MPs at 1983 general election</a></li>
<li><a href="?date=1979-05-03">MPs at 1979 general election</a></li>
<li><a href="?date=1974-10-10">MPs at Oct 1974 general election</a></li>
<li><a href="?date=1974-02-28">MPs at Feb 1974 general election</a></li>
<li><a href="?date=1970-06-18">MPs at 1970 general election</a></li>
<li><a href="?date=1966-03-31">MPs at 1966 general election</a></li>
<li><a href="?date=1964-10-15">MPs at 1964 general election</a></li>
<li><a href="?date=1959-10-08">MPs at 1959 general election</a></li>
<li><a href="?date=1955-05-26">MPs at 1955 general election</a></li>
<li><a href="?date=1951-10-25">MPs at 1951 general election</a></li>
<li><a href="?date=1950-02-23">MPs at 1950 general election</a></li>
<li><a href="?date=1945-07-05">MPs at 1945 general election</a></li>
<li><a href="?date=1935-11-14">MPs at 1935 general election</a></li>
<li>
<form method="get" action="/mps/">
Earlier/ other date:
<input type="text" name="date" value="">
<input type="submit" value="Go">
</form>

<?
} else {
	echo "<li><a href='$allurl'>Historical list of all $rep</a></li>";
}
echo '</ul>';
$this->block_end();
