<publicwhip>
<?

$domain = 'http://www.niassembly.gov.uk/members';
$file_diss = '../rawdata/ni/mems_dissolution.htm';
$file_ass = '../rawdata/ni/membership03.htm';
$num = 90001; # Start ID number, and why not?

# 1998-2003 assembly
if (!is_file($file_diss))
    exec("curl $domain/mems_dissolution.htm > $file_diss");
$list = file_get_contents($file_diss);
preg_match_all('#<tr>\s+<td[^>]*>(?:<a href="[^"]*"> </a>)?\s*(?:<a name="."></a>)?\s*<a href="(biogs/.*?)">\s*(.*?)\s*</a>.*?</td>\s+<td[^>]*>\s*(.*?)<br>\s*</td>#s', $list, $matches, PREG_SET_ORDER);
foreach ($matches as $r) {
	$url = $domain . '/' . $r[1];
	$person = file_get_contents($url);
	preg_match('#<td[^>]*>(?:<font face=Tahoma>)?<b>Constituency:</b>(?:</font>)?</td>\s+<td[^>]*>(?:<font face=Tahoma>)?\s*(.*?)\s*(?:</font>)?\s*(?:</td>|\s*<tr)#i', $person, $m);
	$constituency = $m[1];
	$name = prettify_name($r[2]);
	list($title, $first_name, $last_name) = explode(' ', $name, 3);
	if (!$last_name) { # Lord
		$last_name = $first_name;
		$first_name = '';
	}
	$party = party_lookup($r[3]);

	$data = array(
		'url' => $url,
		'title' => $title,
		'first_name' => $first_name,
		'last_name' => $last_name,
		'constituency' => $constituency,
		'party' => $party,
	);

	# Special cases, for all the 1998-2003 party changes
	if ($party == 'Speaker') { # Choice of Speaker
		$data['party'] = 'Alliance'; output_xml($num++, $data, '1998-06-25', '1998-07-01', 'general_election', 'changed_party');
		$data['party'] = 'Speaker'; output_xml($num++, $data, '1998-07-01', '2003-04-28', 'changed_party', 'general_election');
	} elseif ($party == 'UUAP') { # Founding of UUAP
		$data['party'] = 'Independent'; output_xml($num++, $data, '1998-06-25', '1998-09-21', 'general_election', 'changed_party');
		$data['party'] = 'UUAP'; output_xml($num++, $data, '1998-09-21', '2003-04-28', 'changed_party', 'general_election');
	} elseif ($party == 'NIUP') { # Founding of NIUP, &c.
		$data['party'] = 'UKUP'; output_xml($num++, $data, '1998-06-25', '1999-01-15', 'general_election', 'changed_party');
		if ($first_name == 'Roger' && $last_name == 'Hutchinson') {
			$data['party'] = 'NIUP'; output_xml($num++, $data, '1999-01-15', '1999-12-01', 'changed_party', 'changed_party');
			$data['party'] = 'Independent'; output_xml($num++, $data, '1999-12-01', '2002-04-01', 'changed_party', 'changed_party');
			$data['party'] = 'DUP'; output_xml($num++, $data, '2002-04-01', '2003-04-28', 'changed_party', 'general_election');
		} else {
			$data['party'] = 'NIUP'; output_xml($num++, $data, '1999-01-15', '2003-04-28', 'changed_party', 'general_election');
		}
	} elseif ($first_name == 'Peter' && $last_name == 'Weir') { # Didn't re-elect Trimble
		$data['party'] = 'UUP'; output_xml($num++, $data, '1998-06-25', '2001-11-09', 'general_election', 'changed_party');
		$data['party'] = 'Independent'; output_xml($num++, $data, '2001-11-09', '2002-04-30', 'changed_party', 'changed_party');
		$data['party'] = 'DUP'; output_xml($num++, $data, '2002-04-30', '2003-04-28', 'changed_party', 'general_election');
	} elseif ($first_name == 'Pauline' && $last_name == 'Armitage') { # ditto
		$data['party'] = 'UUP'; output_xml($num++, $data, '1998-06-25', '2001-11-09', 'general_election', 'changed_party');
		$data['party'] = 'Independent'; output_xml($num++, $data, '2001-11-09', '2003-04-28', 'changed_party', 'general_election');
	} elseif ($first_name == 'Gardiner' && $last_name == 'Kane') {
		$data['party'] = 'DUP'; output_xml($num++, $data, '1998-06-25', '2002-11-11', 'general_election', 'changed_party');
		$data['party'] = 'Independent'; output_xml($num++, $data, '2002-11-11', '2003-04-28', 'changed_party', 'general_election');
	} elseif ($first_name == 'Annie' && $last_name == 'Courtney') { # John Hume resigned, and she changed party too
		$data['party'] = 'SDLP'; $data['title'] = 'Mr';
		$data['first_name'] = 'John'; $data['last_name'] = 'Hume'; output_xml($num++, $data, '1998-06-25', '2000-12-01', 'general_election', 'resigned');
		$data['title'] = 'Mrs';
		$data['first_name'] = 'Annie'; $data['last_name'] = 'Courtney'; output_xml($num++, $data, '2000-12-05', '2003-04-01', 'appointed', 'changed_party');
		$data['party'] = 'Independent'; output_xml($num++, $data, '2003-04-01', '2003-04-28', 'changed_party', 'general_election');
	} elseif ($first_name == 'Tom' && $last_name == 'Hamilton') { # Tom Benson died
		$data['last_name'] = 'Benson'; output_xml($num++, $data, '1998-06-25', '2000-12-24', 'general_election', 'died');
		$data['last_name'] = 'Hamilton'; output_xml($num++, $data, '2001-01-17', '2003-04-28', 'appointed', 'general_election');
	} elseif ($first_name == 'Michael' && $last_name == 'Coyle') { # Arthur Doherty resigned
		$data['first_name'] = 'Arthur'; $data['last_name'] = 'Doherty'; output_xml($num++, $data, '1998-06-25', '2002-09-01', 'general_election', 'resigned');
		$data['first_name'] = 'Michael'; $data['last_name'] = 'Coyle'; output_xml($num++, $data, '2002-09-01', '2003-04-28', 'appointed', 'general_election');
	} else {
		output_xml($num++, $data, '1998-06-25', '2003-04-28', 'general_election', 'general_election');
	}
}

# 2003- assembly
if (!is_file($file_ass))
    exec("curl $domain/membership03.htm > $file_ass");
$list = file_get_contents($file_ass);
preg_match_all('#
	<td[^>]*>\s*<font\sface="Arial">\s*(?:<p>|&sect;</font>|&\#8225;</font>)?(?:<u>)?(?:<a\shref="([^"]*)">)?(.*?)\s*(?:</a>)?(?:</u>)?\s*(?:</font>)?\s*</td>\s+
	<td[^>]*>\s*(?:<font\sface="Arial">\s*<p>)?\s*(.*?)\s*(?:</font>)?</td>\s+
	<td[^>]*>\s*(?:<font\sface="Arial">\s*(?:</font>)?\s*<p>)?\s*(.*?)\s*(?:</font>)?</td>\s+
	<td[^>]*>\s*(?:<font\sface="Arial">\s*<p>)?\s*(.*?)\s*(?:</font>)?</td>\s+
	#x', $list, $matches, PREG_SET_ORDER);
foreach ($matches as $r) {
	$data = array(
		'url' => $r[1] ? $domain . '/' . $r[1] : '',
		'title' => '',
		'last_name' => prettify_name($r[2]),
		'first_name' => prettify_name($r[3]),
		'constituency' => str_replace('-u', '-U', ucwords(strtolower($r[5]))),
		'party' => party_lookup($r[4]),
	);
	if (substr($data['last_name'], 0, 1) == '*') {
		$data['last_name'] = ucwords(substr($data['last_name'], 1));
		$data['party'] = 'UUP';
		output_xml($num++, $data, '2003-11-26', '2003-12-18', 'general_election', 'changed_party');
		$data['party'] = 'Independent Unionist';
		output_xml($num++, $data, '2003-12-18', '2004-01-15', 'changed_party', 'changed_party');
		$data['party'] = 'DUP';
		output_xml($num++, $data, '2004-01-15', '9999-12-31', 'changed_party', 'still_in_office');
	} elseif (substr($data['last_name'], 0, 7) == '&#8224;') {
		$data['last_name'] = ucwords(substr($data['last_name'], 7));
		$data['party'] = 'DUP';
		output_xml($num++, $data, '2003-11-26', '2006-02-20', 'general_election', 'changed_party');
		$data['party'] = 'Independent';
		output_xml($num++, $data, '2006-02-21', '9999-12-31', 'changed_party', 'still_in_office');
	} elseif ($data['first_name'] == 'Raymond' && $data['last_name'] == 'McCartney') {
		output_xml($num++, array(
				'first_name'=>'Mary', 'last_name'=>'Nelis',
				'constituency' => 'Foyle', 'party'=>'Sinn Féin',
			), '2003-11-26', '2004-07-14', 'general_election', 'resigned');
		output_xml($num++, $data, '2004-07-15', '9999-12-31', 'appointed', 'still_in_office');
	} elseif ($data['first_name'] == 'Sue' && $data['last_name'] == 'Ramsey') {
		output_xml($num++, array(
				'first_name'=>'Bairbre', 'last_name'=>'de Brun',
				'constituency' => 'Belfast West', 'party'=>'Sinn Féin',
			), '2003-11-26', '2004-10-27', 'general_election', 'resigned');
		output_xml($num++, $data, '2004-11-29', '9999-12-31', 'appointed', 'still_in_office');
	} else {
		output_xml($num++, $data, '2003-11-26', '9999-12-31', 'general_election', 'still_in_office');
	}
}
print "</publicwhip>\n";

function output_xml($num, $data, $from_date, $to_date, $from_why, $to_why) {
	if ($data['url']) $data['url'] = "\turl=\"" . $data['url'] . "\"\n";

	# Patches
	$data['constituency'] = strip_tags($data['constituency']);
	if ($data['first_name'] == 'Pj') $data['first_name'] = 'P J';
	if ($data['last_name'] == 'Shipley-dalton') $data['last_name'] = 'Shipley-Dalton';
	if ($data['last_name'] == 'De Brun') $data['last_name'] = 'de Brun';
	if ($data['constituency'] == 'Fermanagh-South Tyrone' || $data['constituency'] == 'Fermanagh/south Tyrone')
		$data['constituency'] = 'Fermanagh and South Tyrone';
	if ($data['constituency'] == 'Mid-Ulster') $data['constituency'] = 'Mid Ulster';
	if ($data['constituency'] == 'Newry And Armagh') $data['constituency'] = 'Newry and Armagh';
	if ($data['constituency'] == '&nbsp;') $data['constituency'] = 'North Down'; # Eileen Bell
	if ($data['first_name'] == 'Ian Jnr') { $data['first_name'] = 'Ian'; $data['last_name'] = 'Paisley Jnr'; }
	if ($data['last_name'] == 'Beggs') $data['last_name'] = 'Beggs Jnr';
	if ($data['first_name'] == 'Jeffrey' && $data['last_name'] == 'Donaldson') $data['first_name'] = 'Jeffrey M';
	if ($data['first_name'] == 'Lord') { $data['first_name'] = ''; $data['title'] = 'Lord'; }
	if ($data['last_name'] == 'Ian Paisley') {
		$data['last_name'] = 'Paisley'; $data['first_name'] = 'Ian'; $data['title'] = 'Rev Dr';
	}
	if ($data['title'] == 'Mr' && $data['last_name'] == 'Paisley')
		$data['last_name'] = 'Paisley Jnr';
	if (preg_match('#Trimble$#', $data['last_name'])) {
		$data['title'] = 'Rt Hon'; $data['first_name'] = 'David'; $data['last_name'] = 'Trimble';
	}
?>
<member_ni
	id="uk.org.publicwhip/member/<?=$num ?>"
<?=$data['url'] ?>
	title="<?=$data['title'] ?>" firstname="<?=$data['first_name'] ?>" lastname="<?=$data['last_name'] ?>"
	constituency="<?=htmlspecialchars($data['constituency']) ?>" party="<?=$data['party'] ?>"
	fromdate="<?=$from_date ?>" todate="<?=$to_date ?>"
	fromwhy="<?=$from_why ?>" towhy="<?=$to_why ?>"
/>
<?
}

function prettify_name($n) {
	$n = preg_replace('#\s+#', ' ', $n);
	$n = str_replace("\x92", "'", $n);
	$n = strtolower($n);
	$n = ucwords($n);
	$n = preg_replace('#Mc(.)#e', '"Mc".strtoupper($1)', $n);
	$n = preg_replace("#'(.)#e", '"\'".strtoupper($1)', $n);
	return $n;
}

function party_lookup($p) {
	if (strlen($p)<=4) return $p;
	$party_lookup = array(
		'SINN FEIN' => "Sinn Féin",
		'SPEAKER' => 'Speaker',
		'INDEPENDENT UNIONIST' => 'Independent Unionist',
		'INDEPENDENT' => 'Independent',
		'ALLIANCE' => 'Alliance'
	);
	return $party_lookup[$p];
}

?>
