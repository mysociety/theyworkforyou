<?php
/*	Remember, we are currently within the COMMENTLIST class,
	in the render() function.
	
	We cycle through the $data array and output the comment(s). 
	It should be something like this:
	
*/

?>
				<a name="comments"></a>
<?php

if (isset($data['comments']) && count($data['comments']) > 0) {
	print '<h3>Comments matching ' . $data['search'] . "</h3>\n<ul>";
	foreach ($data['comments'] as $n => $comment) {
		$commenttext = trim_characters($comment['body'], 0, 200);
		list($date, $time) = explode(' ', $comment['posted']);
		$date = format_date($date, SHORTDATEFORMAT);
		$time = format_time($time, TIMEFORMAT);
		$count = $n+1;
		?><li><p><a name="c<?php echo $count; ?>"></a><strong><?php echo htmlentities($comment['firstname'] . ' ' . $comment['lastname']); ?>:</strong> <?php echo $commenttext; ?> <small>(<?php echo relative_time($comment['posted']); ?>)</small> <a href="<?php echo $comment['url']; ?>">Read comment</a></p></li>
<?php
	}
	print '</ul>';
}
?>
