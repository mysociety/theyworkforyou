<?php
// This sidebar is on the pages that show information about single MPs.

global $MEMBER;

$SEARCHURL = new URL("emailfriend");

if ($MEMBER->person_id()) {
	$pid = $MEMBER->person_id();
	$this->block_start(array('id'=>'emailfriend', 'title'=>"Email this page to a friend"));
	?>
	<style type="text/css">
	label {
		font-weight: bold;
		cursor: pointer;
	}
	</style>
	<form action="<?php echo $SEARCHURL->generate(); ?>" method="post">
	<p align="right">
		<label for="recmail">Their email:</label> <input type="text" name="recipient_mail" id="recmail" value="" size="17">
		<br><label for="sendmail">Your email:</label> <input type="text" id="sendmail" name="sender_mail" value="" size="17">
		<br><label for="sendname">Your name:</label> <input type="text" id="sendname" name="sender_name" value="" size="17">
	<input type="hidden" name="pid" value="<?php echo $pid; ?>">
		<br>(<a href="/privacy/">privacy policy</a>)
	<input type="submit" class="submit" value="Send"></p>
	</form>

<?php
	$this->block_end();
}
?>
