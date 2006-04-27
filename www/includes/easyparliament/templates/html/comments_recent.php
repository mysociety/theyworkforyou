<?php
/*	For listing, say, the most recent comments on the site.

	Remember, we are currently within the COMMENTLIST class,
	in the render() function.
	
	We cycle through the $data array and output the comment(s). 
	
	See the comments.php template for an example of $data.

*/
global $PAGE, $DATA, $this_page;

if (isset($data['comments']) && count($data['comments']) > 0) {
	$title = 'Most recent comments';
	if (isset($data['full_name'])) {
		$title .= ' on things by ' . $data['full_name'];
	}
	$PAGE->block_start(array('id'=>'recentcomments', 'title'=>$title));
	
	if ($this_page != 'home') $PAGE->page_links($data);
	$USERURL = new URL('userview');
	?>
						<ul>
<?php
	foreach ($data['comments'] as $n => $comment) {
		?>	

						<li><?php
		
		$commenttext = trim_characters($comment['body'], 0, 200);
		list($date, $time) = explode(' ', $comment['posted']);
		$date = format_date($date, SHORTDATEFORMAT);
		$time = format_time($time, TIMEFORMAT);
		
		$count = $n+1;
		
		$USERURL->insert(array('u'=>$comment['user_id']));
		
		?><a name="c<?php echo $count; ?>"></a><strong><?php echo htmlentities($comment['firstname'] . ' ' . $comment['lastname']); ?>:</strong> <?php echo $commenttext; ?> <small>(<?php echo relative_time($comment['posted']); ?>)</small><br><a href="<?php echo $comment['url']; ?>">Read comment</a> | <a href="<?php echo $USERURL->generate(); ?>" title="See more information about this user">All by this user</a> </li>
<?php
	}
	?>
						</ul>
<?php

	if ($this_page == 'home') {
		$MOREURL = new URL('comments_recent');
		?>
						<p><a href="<?php echo $MOREURL->generate(); ?>#c<?php echo count($data['comments'])+1; ?>">See more comments posted recently</a></p>
<?php
	}
	if ($this_page != 'home') $PAGE->page_links($data);
	$PAGE->block_end();
}
?>
