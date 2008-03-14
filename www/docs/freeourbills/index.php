<?php
include_once "../../includes/easyparliament/init.php";

function signup_form() {
?>
	<form method="post" action="action.php">
	<div>
	<input type="hidden" name="posted" id="posted" value="1" />
	<table>
	<tr>
	<td colspan="2"><strong>
	This campaign can only succeed if TheyWorkForYou's users sign up and get involved directly. We need you!
	</strong></td>
	</tr><tr>
	<td><b><label for="email">Your email:</label></b></td>
	<td><input type="text" name="email" id="email" value="" size="30" /></td>
	</tr><tr>
	<td><b><label for="postcode">Your postcode:</label></b></td>
	<td><input type="text" name="postcode" id="postcode" value="" size="10" /></td>
	</tr>
	<tr>
	<td>&nbsp;</td>
	<td>
	<input type="submit" class="submit" value="Join up" />
	</td></tr>
	</table>
	</div>
	</form>
<?
}
?>

<?
$PAGE->page_start();
$PAGE->stripe_start();
$PAGE->block_start(array ('id'=>'intro', 'title'=>'We need your help:'));

?>


<!-- <div style="background-color: #E8FDCB;"> -->
<h2 style="text-align: center">Free our Bills!</h2>

<p style="text-align: center"><em>The Nice Polite Campaign to Gently Encourage Parliament
<br>to Publish Bills in a 21st Century Way, Please. Now.</em></p>
<!-- </div> -->

<h3>What the...?</h3>

<p style="text-align: center"><img title="Duck-billed platypus" src="bill3.jpg" align="right" alt="" hspace="5" vspace="10"></p>

<p>Writing, discussing and voting on bills is what 
we employ our MPs to do. If enough <strong>MPs vote on bills</strong> they become the law,
meaning you or I can get <strong>locked up</strong> if they pass a bad one. 
</p>

<p>Bills are, like, <em>so</em> much more important than what MPs spend
<strong>on furniture</strong>.</p>

<p>The problem is that the way in which Bills are put out is completely
<strong>incompatible with the Internet</strong> era, so nobody out there ever
knows what the heck people are actually voting for or against. We need to free
our Bills in order for most people to be able to understand what matters about
them.
</p>

<h3>We need you!</h3>

<? signup_form() ?>

<p></p>

<h3>"Why?"</h3>

<p>Being the people who run TheyWorkForYou we spend lots of our time
taking rubbish, broken information from Parliament and fixing it up so
that it makes a nice, usable site so you can find out whether your MP
is actually working for you or not. Lots of people seem to like it,
nearly 2 million came to visit last year. <del>Some of them weren't even
MPs obsessively checking their own stats.</del>

<p>
It's time for Parliament to improve its act and start publishing these vital
documents properly in the first place. Quite apart from the fact that we're a
tiny charity without many resources to fix this information, 
<em>you're paying</em> for them to produce it in a uselessly old
fashioned way.  Unless Parliament produces better bills:

<ul>
<li style="font-weight: normal">We can't give you <strong>email alerts</strong> to tell you when a bill mentions
something you might be interested in.
<li style="font-weight: normal">We can't tell you what <strong>amendments your own MP</strong> is asking for, or voting on.
<li style="font-weight: normal">We can't help people who know about bills <strong>annotate them</strong> to explain
what they're really going on about for everyone else.
<li style="font-weight: normal">We can't build services that would help MPs and their staff notice
when they were being asked to vote on dumb or <strong>dubious things</strong>.
<li style="font-weight: normal">We can't really give a <strong>rounded view</strong> of how useful your MP is if we
can't see their involvement with the bill making process.
<li style="font-weight: normal">We can't do about <strong>12 zillion</strong> other things that we're not even bright
enough to think of yet.
</ul>

<br><!-- yuk -->

<h3>"Why won't Parliament do this?"</h3>

<p>We tried, my dears, we really did. We had meetings, and heard
encouraging words. We wrote a proposal on what they should do,
explaining the merits. We wore suits and polished our shoes and used
long words to make them feel comfortable. We met lots of nice people
who really want Parliament to get better at this stuff

<p>And then we got nowhere.

<p>And you know, we're really not bad at working with bits of government
either. But no dice. Nada. Bupkis.

<p>(There's some vague notion that it'll all get done one day, as part of
some miraculous project plan to make everything OK,  but we understand
'sod off', even when spoken in Whitehall-speak.)

<h3>"Isn't it really expensive?"</h3>

<p>No. This needs about &pound;10,000 worth of programming to build a tool to
convert bills to the right format, and probably a Parliamentary staff
member putting between 10% and 100% of their day into operating it,
whilst Parliament is actually in session. They can do what they want
in the holidays &ndash; we aren't slave drivers. Oh yes, 5,000 people work in
Parliament too, over 250 in the computers bit, so we really think they
can afford this.

<h3>"Won't this disrupt the delicate process of writing bills?"</h3>

<p>Nope, the improved publication we're talking about has nothing to do
with the actual legal contents of bills. It's about how it gets
translated into an electronic format once they've finished.

<h3>"Isn't this an embarrassingly obscure thing to be campaigning about?
Can't you campaign about saving puppies or something?"</h3>

<p>Hey - <strong>you're</strong> the one who just read all the way down to this point.
Suck it up and sign up, soldier.

<h3>We need you!</h3>

<? signup_form() ?>

<? $PAGE->block_end(); ?>
<? $PAGE->stripe_end(); ?>
<? $PAGE->page_end (); ?>


