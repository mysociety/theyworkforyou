<?php
// We're within $WMSLIST->render().

/*
	$data = array (
		'info' => '',
		'data' => array (
			array (
				'body'			=> 'Fisheries',
				'hdate'			=> '2004-03-24',
				'list_url'		=> '/wrans/?id=2004-03-24.342W.234',
				'totalcomments'	=> 2,
				'child'	=> array (
					'body'		=> '<p>To ask the Secretary of State ... </p>'
				),
				'parent'	=> array (
					'body'		=> 'Environment'
				)
			),
			etc.
		)
	);
	
	
*/

debug ("TEMPLATE", "hansard_recent_wms.php");

?>
				<dl class="recent-wrans">
<?php

$count = 0;

foreach ($data['data'] as $wran) {

	$count++;
	
	$extrainfo = array();
	
	if ($wran['totalcomments'] > 0) {
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
}
?>
				</dl>
