<?php

# Shared function for FreeOurBills

function signup_form() {
?>
<form method="post" action="subscribe">
<input type="hidden" name="posted" value="1">
<p><strong>
This campaign can only succeed if TheyWorkForYou&rsquo;s users sign up and get involved. We need you!
</strong></p>

<p><label for="email">Your email:</label>
<input type="text" name="email" id="email" value="<?=get_http_var('email')?>" size="30">
<br><label for="postcode">Your postcode:</label>
<input type="text" name="postcode" id="postcode" value="<?=get_http_var('postcode')?>" size="10">
&nbsp; <input type="submit" class="submit" value="Join up">
</form>

<?
}

?>
