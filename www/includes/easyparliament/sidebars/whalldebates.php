<?php
// This sidebar is on the pages that show calendars of dates Westminster Hall debates have occurred on.

$URL = new URL('help');
$helpurl = $URL->generate();
$this->block_start(array('id'=>'help', 'title'=>"What are Westminster Hall Debates?"));
?>

<p>In December 1999, a new meeting place was opened up for debates - <strong>Westminster Hall</strong>. 

<p>Westminster Hall sits alongside the main Chamber, and is aimed at fostering a new style of debate. Sessions are open to all MPs, who sit in a horseshoe arrangement which is meant to encourage <strong>constructive rather than confrontational debate</strong> </p>
<p>
The meetings are presided over by a Deputy Speaker and there are no votes.</p>
<?php
$this->block_end();
?>