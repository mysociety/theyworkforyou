<?

# Compile data for MP page in Google gadget

# XXX Lots here copied from elsewhere... Damn you deadlines.

include_once 'min-init.php';
include_once INCLUDESPATH . 'easyparliament/member.php';
include_once '../api/api_functions.php';

$pid = $_GET['pid'];

$db = new ParlDB;
$q = $db->query("select * from member
	where house=1 and person_id = '" . mysql_escape_string($pid) . "'
	order by left_house desc limit 1");
if (!$q->rows()) {
	print '<error>Unknown ID</error>'; exit;
}

$row = $q->row(0);
$row['full_name'] = member_full_name($row['house'], $row['title'], $row['first_name'],
	$row['last_name'], $row['constituency']);
if (isset($parties[$row['party']]))
	$row['party'] = $parties[$row['party']];
list($image,$sz) = find_rep_image($row['person_id'], true);
if ($image) $row['image'] = $image;

$q = $db->query("SELECT position,dept FROM moffice WHERE to_date='9999-12-31'
	and source='chgpages/selctee' and person=" .
	mysql_escape_string($pid) . ' ORDER BY from_date DESC');
for ($i=0; $i<$q->rows(); $i++) {
	$row['selctee'][] = prettify_office($q->field($i, 'position'), $q->field($i, 'dept'));
}

/*
$q = $db->query("SELECT title,chairman from pbc_members,bills where member_id=".$row['member_id']
	. ' and bill_id=bills.id');
for ($i=0; $i<$q->rows(); $i++) {
	$member = 'Member';
	if ($q->field($i, 'chairman')) {
		$member = 'Chairman';
	}
	$row['selctee'][] = $member . ', ' . $q->field($i, 'title');
}
*/

$q = $db->query("select data_key, data_value from personinfo
	where data_key like 'public\_whip%' and person_id = '" . mysql_escape_string($pid) . "'
	order by data_key"); # order so both_voted is always first...
$none = false;
$output = array();
for ($i=0; $i<$q->rows(); $i++) {
	$key = str_replace(array('public_whip_dreammp', '_distance'), '', $q->field($i, 'data_key'));
	if (preg_match('#_absent$#', $key)) continue;
	if ($none) {
		$none = false;
		$output[$key] = -1;
		continue;
	}
	$value = $q->field($i, 'data_value');
	if (preg_match('#_both_voted$#', $key)) {
	       	if ($value == 0) $none = true;
		continue;
	}
	$output[$key] = $value;
}
$pw = '<ul>';
display_dream_comparison(996, "a <strong>transparent Parliament</strong>");
display_dream_comparison(811, "introducing a <strong>smoking ban</strong>");
display_dream_comparison(230, "introducing <strong>ID cards</strong>", true);
display_dream_comparison(363, "introducing <strong>foundation hospitals</strong>");
display_dream_comparison(367, "introducing <strong>student top-up fees</strong>", true);
display_dream_comparison(258, "Labour's <strong>anti-terrorism laws</strong>", true);
display_dream_comparison(219, "the <strong>Iraq war</strong>", true);
display_dream_comparison(975, "investigating the <strong>Iraq war</strong>");
display_dream_comparison(984, "replacing <strong>Trident</strong>");
display_dream_comparison(358, "the <strong>hunting ban</strong>", true);
display_dream_comparison(826, "equal <strong>gay rights</strong>");
function display_dream_comparison($id, $text, $inverse = false) {
	global $pw, $output;
	if (!array_key_exists($id, $output)) return;
	$score = $output[$id];
	if ($score == -1) {
		$pw .= '<li>Has never voted on';
	} else {
		if ($inverse) $score = 1 - $score;
		$pw .= '<li>Voted <strong>' . score_to_strongly($score) . '</strong>';
	}
	$pw .= ' ' . $text . '</li>';
}

$pw .= '</ul>';

$output = $row;
$output['pw_data'] = $pw;

$q = $db->query("select * from memberinfo where member_id = " . $row['member_id']
	. " and data_key in ('swing_to_lose_seat_today', 'majority_in_seat')");
for ($i=0; $i<$q->rows(); $i++) {
	$key = $q->field($i, 'data_key');
	$output[$key] = number_format($q->field($i, 'data_value'));
}

print '<twfy>' . api_output_xml($output) . '</twfy>';

function score_to_strongly($dmpscore) {
	$dmpdesc = "unknown about";
	if ($dmpscore > 0.95 && $dmpscore <= 1.0)
		$dmpdesc = "very strongly against";
	elseif ($dmpscore > 0.85)
		$dmpdesc = "strongly against";
	elseif ($dmpscore > 0.6)
		$dmpdesc = "moderately against";
	elseif ($dmpscore > 0.4)
		$dmpdesc = "a mixture of for and against";
	elseif ($dmpscore > 0.15)
		$dmpdesc = "moderately for";
	elseif ($dmpscore > 0.05)
		$dmpdesc = "strongly for";
	elseif ($dmpscore >= 0.0)
		$dmpdesc = "very strongly for";
	return $dmpdesc;
}

