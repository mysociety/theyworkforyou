<?php
// This sidebar is on the individual debate and wrans pages, by the annotation input.

$URL = new URL('houserules');
$rulesurl = $URL->generate();
$this->block_start(array('id'=>'help', 'title'=>"Annotation guidelines"));
?>

<p>Only &lt;em&gt; and &lt;strong&gt; tags are allowed. URLs and email addresses will automatically be turned into links.</p>
<p>Please read our <a href="<?php echo $rulesurl; ?>">House Rules</a> before you post your first annotation.

<p>The short version: Be nice to each other.</p>
<?php
$this->block_end();
?>
