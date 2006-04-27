<?php
/*	Remember, we are currently within the GLOSSARYLIST class,
	in the render() function.
	
	We cycle through the $data array and output the comment(s). 
	It should be something like this:
	
	$data = array (
		'info'	=> array (
			'user_id' => '34'		//  Might be used to highlight the user's comments.
		),
		'terms' => array (
			0 => array (
				'comment_id'	=> '1',
				'user_id'		=> '4',
				'epobject_id'	=> '304',
				'body'			=> 'My comment goes here...',
				'posted'		=> '2003-12-31 23:00:00',
				'firstname'		=> 'Guy',
				'lastname'		=> 'Fawkes',
				'url'			=> '/permalink/to/this/comment/?id=304#c1',
				'hbody'			=> 'To answer the honourable gentleman...',
				'major'			=> 1,
				'first_name'	=> 'Tony',
				'last_name'		=> 'Blair'
			),
			1 => array (
				etc....
			)
		)
	);

	
*/
global $PAGE, $DATA, $this_page, $THEUSER;


// Something's telling me these subheadings shouldn't be in this template...!

if (isset($data['comments'][0]['preview']) && $data['comments'][0]['preview'] == true) {
	// If we're just previewing a comment, we passed in 'preview' => true.
	$subheading = 'Your comment would look like this:';

} elseif ($this_page == 'addcomment') {
	$subheading = 'Previous comments';

} elseif ($this_page == 'commentreport' || $this_page == 'admin_commentreport') {
	$subheading = "";
	
} else {
	$subheading = 'Comments';
}


?>
<div class="comments" id="comments">
	<a name="comments"></a>
	<?php if ($subheading != '') { echo "<h4>$subheading</h4>"; }?>
<?php

if (isset($data['comments']) && count($data['comments']) > 0) {

	foreach ($data['comments'] as $n => $comment) {
		$style = $n % 2 == 0 ? '1' : '2';

		if (isset($data['info']['user_id']) && 
			$comment['user_id'] == $data['info']['user_id']) {
			$style .= '-on';
		}
		?>
	<div class="block<?php echo $style; ?>"<?php
		if (isset($comment['comment_id'])) {
			?> id="c<?php echo $comment['comment_id']; ?>"><a name="c<?php echo $comment['comment_id']; ?>"></a>
<?php
		} else {
			echo ">\n";
		}
		
		$USERURL = new URL('userview');
		$USERURL->insert(array('u'=>$comment['user_id']));
		?>
		<div class="comment">
			<p><a href="<?php echo $USERURL->generate(); ?>" title="See information about this user"><strong><?php echo htmlentities($comment['firstname']) .' '. htmlentities($comment['lastname']); ?></strong></a><br />
<?php
		// Make URLs into links and do <br>s.
		$body = prepare_comment_for_display($comment['body']); // In utility.php
		
		echo $body;
		?></p>
		
		</div>
		<div class="sidebar">
		<p> Posted on 
<?php
		list($date, $time) = explode(' ', $comment['posted']);
		$date = format_date($date, SHORTDATEFORMAT);
		$time = format_time($time, TIMEFORMAT);
		
		echo $date; ?>, 
<?php
		if (isset($comment['url'])) {
			?>
		<a href="<?php echo $comment['url']; ?>" title="Link to this comment"><?php echo $time; ?></a>
<?php
		} else {
			// There won't be a URL when we're just previewing a comment.
			echo "\t\t$time";
		}

		if (($this_page != 'commentreport' && 
			$this_page != 'addcomment'  && 
			$this_page != 'admin_commentreport') 
			&& $THEUSER->is_able_to('reportcomment')
			&& !$comment['modflagged']
			) {
			
			// The comment hasn't been reported and we're on a page where we want to 
			// display this link.

			$URL = new URL('commentreport');
			$URL->insert(array(
				'id'	=> $comment['comment_id'],
				'ret' 	=> $comment['url']
			));
			
			?><br />
		<a href="<?php echo $URL->generate(); ?>" title="Notify moderators that this comment needs editing or deleting">Report this comment</a>
<?php

		} elseif ($comment['modflagged']) {
			?><br />
		This comment has been reported
<?php
		}
		
		?>
		</p>
		</div>
		<div class="break">&nbsp;</div>
	</div>
<?php
	
	}



} else {

	?>
	<p>No comments</p>
<?php
}
?>
	<div class="break"></div>
</div> <!-- end comments -->

