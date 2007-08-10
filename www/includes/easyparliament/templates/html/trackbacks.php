<?php
// For displaying the trackbacks. Duh.

// Remember, we are currently within TRACKBACK class,
// in the render() function.

// The array $data will be packed full of luverly stuff about trackbacks:

/*
	$data = array (
	'info' => array(
		'view' => 'recent',
		'num' => 30
	),
	'data' => array (
		array (
			'trackback_id'	=> '37',
			'gid' 			=> '2003-02-28.475.3',
			'url' 			=> 'http://www.gyford.com/weblog/my_entry.html',
			'blog_name' 	=> "Phil's weblog",
			'title' 		=> 'Interesting speech',
			'excerpt' 		=> 'My MP has posted an interesting speech, etc',
			'posted'		=> '2003-12-31 23:00:00'
		),
		array (
			[as above]
		),
		etc.
	);
*/
global $PAGE, $this_page, $DATA;

twfy_debug("TEMPLATE", "trackbacks.php");

$info = $data['info'];
$data = $data['data'];

if (count($data) > 0) {

	if ($info['view'] == 'recent') {
		$title = $info['num'] . ' most recent trackbacks';
	} else {
		$title = 'Some sites linking to this page';
	}
	
	if (!$PAGE->within_stripe()) {
		$PAGE->stripe_start();
		$stripe_must_be_ended = true;
	} else {
		$stripe_must_be_ended = false;
	}
	?>
	<a name="trackbacks"></a>
	<div class="trackbacks">
		<h4><?php echo $title; ?></h4>
		<dl>
		
<?php
	foreach ($data as $n => $trackback) {
		
		list($date, $time) = explode(' ', $trackback['posted']);
		$date = format_date($date, SHORTDATEFORMAT);
		$time = format_time($time, TIMEFORMAT);
		?>
		<dt><a href="<?php echo htmlentities($trackback['url']); ?>"><?php echo htmlentities($trackback['title']); ?></a></dt>
		<dd><?php echo htmlentities($trackback['excerpt']); ?><br>
			<small>At <?php echo htmlentities($trackback['blog_name']); ?> on <?php echo $date . ' ' . $time; ?></small>
		</dd>
<?php
	
	} // End cycling through trackbacks.
	
	?>
		</dl>
	</div> <!-- end trackbacks -->
<?php
	
	if ($stripe_must_be_ended) {
		$PAGE->stripe_end();
	}
} // End display of trackbacks.


// If there are no trackbacks to display, we display nothing at all.
