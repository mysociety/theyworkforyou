<?php
$this->block_start(array('id'=>'help', 'title'=>"mySociety news updates"));
?>

<form method=post action="https://secure.mysociety.org/admin/lists/mailman/subscribe/news">
<p>
<label for="txtEmail">mySociety, which runs TheyWorkForYou, has an occasional
news email list, full of titbits and stories. Enter your email address below to
subscribe:</label>
</p>
<input type="text" class="textbox" id="txtEmail" name="email" value="">
<input type="Submit" name="email-button" value="Add me to the list">
</form>

<?php
$this->block_end();
?>
