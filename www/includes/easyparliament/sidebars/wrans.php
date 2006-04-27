<?php
// This sidebar is on the pages that show calendars of when Written Answers have occurred.

$URL = new URL('help');
$helpurl = $URL->generate();

$this->block_start(array('id'=>'help', 'title'=>"What are Written Answers?"));
?>

<p>The <strong>parliamentary question</strong> is a great way for MPs and Peers to discover information which the government may not wish to reveal. Ministers reply via <strong>written answers</strong>, a list of which gets published daily. 
</p>
<p>We let you vote on whether or not the answer given is adequate.
</p>
<!--<p>You can read more about written answers <a href="<?php echo $helpurl; ?>#wrans">here</a>.
</p>-->
<?php
$this->block_end();
?>
