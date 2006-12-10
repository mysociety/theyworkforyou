<?php
// This sidebar is on the very front page of the site.

$this->block_start(array('id'=>'help', 'title'=>"What's all this about?"));

$URL = new URL('about');
$abouturl = $URL->generate();

$URL = new URL('help');
$helpurl = $URL->generate();
?>

<p><a href="<?php echo $abouturl; ?>" title="link to About Us page">TheyWorkForYou.com</a> is a non-partisan, partly volunteer-run website which aims to make it easy for people to keep tabs on their elected and unelected representatives in Parliament, and other assemblies.</p>

<?php
$this->block_end();
?>
