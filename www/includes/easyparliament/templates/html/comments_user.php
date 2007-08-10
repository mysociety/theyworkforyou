<?php
/*	For listing, say, a user's most recent comments as links.

	Remember, we are currently within the COMMENTLIST class,
	in the render() function.
	
	We cycle through the $data array and output the comment(s). 
	
	See the comments.php template for an example of $data.
	
*/
global $PAGE, $DATA, $this_page;



if (isset($data['comments']) && count($data['comments']) > 0) {
	
	$PAGE->stripe_start();
	
	?>
				<h4>Most recent comments</h4>
<?php
	$PAGE->page_links($data);
	$PAGE->stripe_end();
	
	$stripecount = 0; // Used to generate stripes.

	foreach ($data['comments'] as $n => $comment) {

		$stripecount++;
		$style = $stripecount % 2 == 0 ? '1' : '2';	
		
		$PAGE->stripe_start($style);
		
		$hansardtext = trim_characters($comment['hbody'], 0, 65);
		
		list($date, $time) = explode(' ', $comment['posted']);
		$date = format_date($date, SHORTDATEFORMAT);
		
		// Get the name of the member whose epobject was commented upon (if any).
		if (isset($comment['speaker']) && $comment['speaker']['first_name'] != '') {
			$member_name = $comment['speaker']['first_name'] . ' ' . $comment['speaker']['last_name'] . ': ';
		} else {
			$member_name = '';
		}
		
		$user_name = htmlentities($comment['firstname'] . ' ' . $comment['lastname']);
		
		// We're grouping things by epobject_id, so we're going to display the number
		// of comments on this epobject.
		$plural = $comment['total_comments'] == 1 ? ' comment' : ' comments';
		
		echo "\t\t\t\t<p><a href=\"$comment[url]\">$comment[total_comments]$plural</a> to <strong>" . $member_name . $hansardtext . "</strong><br>\n";
		echo "\t\t\t\t<small>(posted on $date)</small><br>\n";
		echo "\t\t\t\t" . prepare_comment_for_display($comment['body']) . "</p>"; ?>

<?php
		$PAGE->stripe_end();
	}
	$PAGE->stripe_start();
	$PAGE->page_links($data);
	$PAGE->stripe_end();

} else {

	$PAGE->stripe_start();
	?>
	<p>This user hasn't posted any comments.</p>
<?php
	$PAGE->stripe_end();
}
?>


