<ul>
<?php

$count = 0;

foreach ($data as $date => $arr) {
	# $extrainfo = array();
	/*if ($debate['totalcomments'] > 0) {
		$plural = $wran['totalcomments'] == 1 ? 'comment' : 'comments';
		$totalcomments = ' <small>(' . $wran['totalcomments'] . ' ' . $plural . ')</small>';
	} else {
		$totalcomments = '';
	}
	
	$speaker = $wran['child']['speaker'];
	?>
	<dt><a name="w<?php echo $count; ?>"></a><strong><a href="<?php echo $wran['list_url']; ?>"><?php echo $wran['parent']['body'] . ': ' . $wran['body']; ?></a></strong> <?php echo format_date($wran['hdate'], LONGDATEFORMAT) . ' ' .$totalcomments; ?></dt>
				<dd><?php if (sizeof($speaker)) { ?><a href="<?php echo $speaker['url']; ?>"><?php echo member_full_name($speaker['house'], $speaker['title'], $speaker['first_name'], $speaker['last_name'], $speaker['constituency']); ?></a>: <?php }
				echo trim_characters($wran['child']['body'], 0, 200); ?></dd>
<?php
	*/
	print '<li><strong>' . $date . '</strong> <ul>';
	foreach ($arr as $debate) {
		print '<li><a href="' . $debate['url'] . '">' . $debate['bill'] . '</a>, '.$debate['sitting'];
	}
	print '</ul>';
}
?>
				</ul>
