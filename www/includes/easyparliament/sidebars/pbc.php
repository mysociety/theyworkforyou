<?php

$URL = new URL('help');
$helpurl = $URL->generate();
$this->block_start(array('id'=>'help', 'title'=>"What are Public Bill Committees?"));
?>

<p>Previously called Standing Committees, <strong>Public Bill Commitees</strong>
study proposed legislation (Bills) in detail, debating each clause and reporting any
amendments to the Commons for further debate.
</p>

<p>There are at least 16 MPs on a Committee, and the proportion of parties reflects
the House of Commons, so the government always has a majority.
</p>

<?php
$this->block_end();
