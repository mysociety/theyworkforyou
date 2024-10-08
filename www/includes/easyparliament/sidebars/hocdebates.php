<?php
// This sidebar is on the pages that show calendars of dates House of Commons debates have occurred on.

$URL = new \MySociety\TheyWorkForYou\Url('help');
$helpurl = $URL->generate();
$this->block_start(['id' => 'help', 'title' => "What are Debates?"]);
?>

<p><strong>Debates</strong> in the House of Commons are an opportunity for MPs from all parties to <strong>scrutinise</strong> government legislation and <strong>raise important local, national or topical issues</strong>.</p>
<p>And sometimes to shout at each other.</p>
<p>
<!--For more about debates, click <a href="<?php echo $helpurl; ?>#debates">here</a>.-->
</p>

<?php
$this->block_end();
?>
