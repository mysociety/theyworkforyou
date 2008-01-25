<?

/* Nasty way of implementing "by department" stuff with the current schema */

include_once "../../includes/easyparliament/init.php";

$dept = get_http_var('dept');
$PAGE->page_start();
$PAGE->stripe_start();
$db = new ParlDB;

print '<h2>Departments</h2>';

$q = $db->query('select major,body from hansard,epobject
	where hansard.epobject_id=epobject.epobject_id and major in (3,4) and section_id=0
	and hdate>(select max(hdate) from hansard where major in (3,4)) - interval 7 day
	group by body, major
	order by body');
$data = array();
for ($i=0; $i<$q->rows(); $i++) {
	$body = $q->field($i, 'body');
	$major = $q->field($i, 'major');
	$data[$body][$major] = true;
}

print '<p>List of departments who have had questions or statements within the past week</p>';

print '<ul>';
foreach ($data as $body => $arr) {
	$link = strtolower(str_replace(' ','_',$body));
	print '<li>';
	print $body;
	print ' &mdash; ';
	if (isset($arr[3])) {
		print '<a href="'.$link.'/questions">Written Questions</a>';
	}
	if (count($arr)==2)
		print ' | ';
	if (isset($arr[4])) {
		print '<a href="'.$link.'/statements">Written Ministerial Statements</a>';
	}
	print '</li>';
}
print '</ul>';

$PAGE->stripe_end();
$PAGE->page_end();
