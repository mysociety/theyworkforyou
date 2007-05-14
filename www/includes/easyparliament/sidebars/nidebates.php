<?php

$URL = new URL('help');
$helpurl = $URL->generate();
$this->block_start(array('id'=>'help', 'title'=>"What is the Northern Ireland Assembly?"));
?>

<p>"The <strong>Northern Ireland Assembly</strong> was established as part of the Belfast Agreement and meets in Parliament Buildings.
The Northern Ireland Assembly was suspended on the 14th of October 2002, and remained suspended until 8th May 2007.</p>

<p>"Between 8 May 2006 and 22 November 2006 the <strong>Assembly established under the Northern Ireland Act 2006</strong> met.</p>

<p>"The Northern Ireland (St Andrews Agreement) Act 2006 provides for a <strong>Transitional Assembly</strong> to take part in preparations for the restoration of devolved government in Northern Ireland in accordance with the St Andrews Agreement. A person who is a member of the Northern Ireland Assembly is also a member of the Transitional Assembly".</p>

<?php
$this->block_end();
?>
