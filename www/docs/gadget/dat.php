<?

# Compile data for MP page in Google gadget

# XXX Lots here copied from elsewhere... Damn you deadlines.

include_once 'min-init.php';
include_once INCLUDESPATH . 'easyparliament/member.php';
include_once '../api/api_functions.php';

$pid = $_GET['pid'];
if (!$pid) { print '<error>No ID</error>'; exit; }

$member = new MEMBER(array('person_id' => $pid));
if (!$member->valid) { print '<error>Unknown ID</error>'; exit; }
$member->load_extra_info();

$row = array(
	'person_id' => $pid,
	'full_name' => $member->full_name(),
	'constituency' => $member->constituency(),
	'party' => $member->party_text(),
	'majority_in_seat' => number_format($member->extra_info['majority_in_seat']),
	'swing_to_lose_seat_today' => number_format($member->extra_info['swing_to_lose_seat_today']),
);

list($image, $sz) = find_rep_image($pid, true);
if ($image) $row['image'] = $image;

foreach ($member->extra_info['office'] as $office) {
	if ($office['to_date'] == '9999-12-31' && $office['source'] == 'chgpages/selctee') {
		$row['selctee'][] = prettify_office($office['position'], $office['dept']);
	}
}

$none = false;
$output = array();
$pw_keys = array_filter(array_keys($member->extra_info), create_function('$a', '
	if (substr($a, 0, 11) != "public_whip") return false;
	if (substr($a, -7) == "_absent") return false;
	return true;
'));
sort($pw_keys);
foreach ($pw_keys as $key) {
	$value = $member->extra_info[$key];
	$key = str_replace(array('public_whip_dreammp', '_distance'), '', $key);
	if ($none) {
		$none = false;
		$output[$key] = -1;
		continue;
	}
	if (preg_match('#_both_voted$#', $key)) {
	       	if ($value == 0) $none = true;
		continue;
	}
	$output[$key] = $value;
}

$pw = '<ul>';
$pw .= display_dream_comparison(996, "a <strong>transparent Parliament</strong>");
$pw .= display_dream_comparison(811, "introducing a <strong>smoking ban</strong>");
$pw .= display_dream_comparison(230, "introducing <strong>ID cards</strong>", true);
$pw .= display_dream_comparison(363, "introducing <strong>foundation hospitals</strong>");
$pw .= display_dream_comparison(367, "introducing <strong>student top-up fees</strong>", true);
$pw .= display_dream_comparison(258, "Labour's <strong>anti-terrorism laws</strong>", true);
$pw .= display_dream_comparison(219, "the <strong>Iraq war</strong>", true);
$pw .= display_dream_comparison(975, "investigating the <strong>Iraq war</strong>");
$pw .= display_dream_comparison(984, "replacing <strong>Trident</strong>");
$pw .= display_dream_comparison(358, "the <strong>hunting ban</strong>", true);
$pw .= display_dream_comparison(826, "equal <strong>gay rights</strong>");
$pw .= '</ul>';

$row['pw_data'] = $pw;
print '<twfy>' . api_output_xml($row) . '</twfy>';

# ---

function display_dream_comparison($id, $text, $inverse = false) {
	global $output;
	if (!array_key_exists($id, $output)) return;
	$pw = '';
	$score = $output[$id];
	if ($score == -1) {
		$pw .= '<li>Has never voted on';
	} else {
		if ($inverse) $score = 1 - $score;
		$pw .= '<li>Voted <strong>' . score_to_strongly($score) . '</strong>';
	}
	$pw .= ' ' . $text . '</li>';
	return $pw;
}

