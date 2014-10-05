<?php
// This sidebar is on the page the user sees after they've successfully clicked the link in their 'confirm' email, after joining.

$URL = new \MySociety\TheyWorkForYou\Url('houserules');
$houserulesurl = $URL->generate();

$this->block_start(array('id'=>'help', 'title'=>"Every Community Needs A Few Rules"));
?>

<p>Before you add an annotation, please read our <a href="<?php echo $houserulesurl; ?>">House Rules</a> for advice on the kind of language, tone and behaviour we want to encourage.</p>

<p>Every healthy community needs a few rules. <a href="<?php echo $houserulesurl; ?>">These</a> are ours. </p>

<?php
$this->block_end();
?>
