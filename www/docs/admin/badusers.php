<?php

include_once "../../includes/easyparliament/init.php";
include_once (INCLUDESPATH."easyparliament/commentreportlist.php");

$this_page = "admin_badusers";

$db = new ParlDB;


$PAGE->page_start();


$PAGE->stripe_start();



///////////////////////////////////////////////////////////////

?>
<h4>Users with lots of deleted comments</h4>
<?php

// Get a list of the users who have the most deleted comments.
$q = $db->query("SELECT COUNT(*) AS deletedcount, 
						u.user_id,
						u.firstname,
						u.lastname,
						u.email
				FROM 	comments c, users u
				WHERE 	c.visible = 0 
				AND		c.user_id = u.user_id
				GROUP BY user_id
				ORDER BY deletedcount DESC");

$rows = array();
$USERURL = new URL('userview');
	
for ($row=0; $row<$q->rows(); $row++) {
	
	$user_id = $q->field($row, 'user_id');
	
	// Get the total comments posted for this user.
	$r = $db->query("SELECT COUNT(*) AS totalcount
					FROM	comments
					WHERE	user_id = '" . $user_id . "'");
	
	$totalcomments = $r->field(0, 'totalcount');
	
	$percentagedeleted = ( $q->field($row, 'deletedcount') / $totalcomments ) * 100;
	
	
	// Get complaints made about this user's comments, but not upheld.
	$r = $db->query("SELECT COUNT(*) AS count
					FROM commentreports, comments
					WHERE	commentreports.comment_id = comments.comment_id
					AND		comments.user_id = '$user_id'
					AND		commentreports.resolved IS NOT NULL
					AND		commentreports.upheld = '0'");
	
	$notupheldcount = $r->field(0, 'count');
	
	
	$USERURL->insert(array('u'=>$user_id));
	
	$rows[] = array (
		'<a href="' . $USERURL->generate() . '">' . $q->field($row, 'firstname') . ' ' . $q->field($row, 'lastname') . '</a>',
		$totalcomments,
		$q->field($row, 'deletedcount'),
		$percentagedeleted.'%',
		$notupheldcount
	);
}

$tabledata = array (
	'header' => array (
		'Name',
		'Total comments',
		'Number deleted',
		'Percentage deleted',
		'Reports against not upheld'
	),
	'rows' => $rows
);
$PAGE->display_table($tabledata);





///////////////////////////////////////////////////////////////

?>
<h4>Users who've made most rejected reports</h4>
<?php



$q = $db->query("SELECT COUNT(*) AS rejectedcount,
						cr.user_id,
						u.firstname,
						u.lastname
				FROM	commentreports cr, users u
				WHERE	cr.resolved IS NOT NULL
				AND		cr.upheld = '0'
				AND		cr.user_id = u.user_id
				AND		cr.user_id != 0
				GROUP BY cr.user_id
				ORDER BY rejectedcount DESC");

$rows = array();
$USERURL = new URL('userview');
	
for ($row=0; $row<$q->rows(); $row++) {

	$user_id = $q->field($row, 'user_id');
	
	$USERURL->insert(array('u'=>$user_id));

	// Get how many valid complaints they've submitted.
	$r = $db->query("SELECT COUNT(*) AS upheldcount
					FROM commentreports
					WHERE	user_id = '$user_id'
					AND		upheld = '1'");
	
	$rows[] = array (
		'<a href="' . $USERURL->generate() . '">' . $q->field($row, 'firstname') . ' ' . $q->field($row, 'lastname') . '</a>',
		$q->field($row, 'rejectedcount'),
		$r->field(0, 'upheldcount')
	);
	
}
$tabledata = array (
	'header' => array (
		'Name',
		'Reports not upheld',
		'Reports upheld'
	),
	'rows' => $rows
);

$PAGE->display_table($tabledata);





$menu = $PAGE->admin_menu();

$PAGE->stripe_end(array(
	array(
		'type'		=> 'html',
		'content'	=> $menu
	)
));


$PAGE->page_end();
