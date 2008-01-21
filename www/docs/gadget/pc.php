<?

include_once 'min-init.php';
include_once INCLUDESPATH . 'easyparliament/member.php';

$pc = $_GET['pc'];
$pc = preg_replace('#[^a-z0-9 ]#i', '', $pc);
if (validate_postcode($pc)) {
	$constituency = postcode_to_constituency($pc);
	if ($constituency == 'CONNECTION_TIMED_OUT') {
		error('Connection timed out');
	} elseif ($constituency) {
		$pid = get_person_id($constituency);
		echo 'pid,', $pid;
	} else {
		error('Unknown postcode');
	}
} else {
	error('Invalid postcode');
}

function error($s) {
	echo 'error,', $s;
}

function get_person_id($c) {
	$db = new ParlDB;
	if ($c == '') return false;
	if ($c == 'Orkney ') $c = 'Orkney &amp; Shetland';
	$n = normalise_constituency_name($c); if ($n) $c = $n;
	$q = $db->query("SELECT person_id FROM member
		WHERE constituency = '" . mysql_escape_string($c) . "'
		AND left_reason = 'still_in_office' AND house=1");
	if ($q->rows > 0)
		return $q->field(0, 'person_id');
	return false;
}

