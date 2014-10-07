<?php
$this->block_start(array('id'=>'help', 'title'=>"mySociety news updates"));
?>

<form method="post" action="http://mysociety.us9.list-manage.com/subscribe/post?u=53d0d2026dea615ed488a8834&id=287dc28511">
<label for="txtEmail">mySociety, which runs TheyWorkForYou, has a monthly
news email list, full of titbits and stories. Enter your email address below to
subscribe:</label>
<input type="email" placeholder="Your email address" name="EMAIL" id="txtEmail"/>
<label style="position: absolute; left: -5000px;">
  Leave this box empty: <input type="text" name="b_53d0d2026dea615ed488a8834_287dc28511" tabindex="-1" value="" />
</label>
<input type="submit" value="Subscribe" name="subscribe"/>
</form>

<?php
$this->block_end();
?>
