<?php
// We're within $DEBATESLIST->render().

/*
	$data = array (
		'info' => '',
		'data' => array (
			array (
				'contentcount' 	=> 128,
				'body'			=> 'My big bill',
				'hdate'			=> '2004-03-24',
				'list_url'		=> '/debates/?id=2004-03-24.342.234',
				'totalcomments'	=> 2,
				'parent'	=> array (
					'body'		=> 'My new clause 23'
				)
			),
			etc.
		)
	);
	
	The 'parent' element is optional.
	
*/

twfy_debug("TEMPLATE", "hansard_biggest_debates.php");

?>
				<dl class="big-debates">
<?php

$count = 0;

foreach ($data['data'] as $debate) {

	$count++;
	
	$extrainfo = array();
	
	$plural = $debate['contentcount'] == 1 ? 'speech' : 'speeches';
	$extrainfo[] = $debate['contentcount'] . ' ' . $plural;
	
	if ($debate['totalcomments'] > 0) {
		$plural = $debate['totalcomments'] == 1 ? 'comment' : 'comments';
		$extrainfo[] = $debate['totalcomments'] . ' ' . $plural;
	}
	
	$debateline = '<strong><a href="' . $debate['list_url'] . '">' . $debate['body'] . '</a></strong> <small>(' . implode(', ', $extrainfo) . ')</small>';

	if (isset($debate['parent'])) {
		$firstline = '<strong><a href="' . $debate['list_url'] . '">' . $debate['parent']['body'] . '</a></strong>';
		$secondline = $debateline . "<br />\n\t\t\t\t";
	} else {
		$firstline = $debateline;
		$secondline = '';
	}
	$secondline .= format_date($debate['hdate'], LONGERDATEFORMAT);
	
	?>
				<dt><a name="d<?php echo $count; ?>"></a><?php echo $firstline; ?></dt>
				<dd><?php echo $secondline; ?></dd>
<?php
}
?>
				</dl>
