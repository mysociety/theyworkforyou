<?

include 'min-init.php';
include_once '../api/api_functions.php';
include_once '../api/api_getMP.php';

$db = new ParlDB;
$_GET['output'] = 'xml';

$party_sites = array(
    'Conservative' => 'http://www.conservatives.com/',
    'Democratic Unionist' => 'http://www.dup.org.uk/',
    'Labour' => 'http://www.labour.org.uk/',
    'Liberal Democrats' => 'http://www.libdems.org.uk/',
    'Plaid Cymru' => 'http://www.plaidcymru.org/',
    'Respect' => 'http://www.respectcoalition.org/',
    'Social Democratic and Labour Party' => 'http://www.sdlp.ie/',
    'Sinn Fein' => 'http://sinnfein.ie/',
    'Scottish National Party' => 'http://www.snp.org/',
    'UKIP' => 'http://www.ukip.org/',
    'Ulster Unionist' => 'http://www.uup.org/',
);

$bbc_cons_id = get_http_var('id');
if (!ctype_digit($bbc_cons_id) || !$bbc_cons_id)
	error('Invalid constituency ID');

$q = $db->query("select house, title, first_name, last_name, member.constituency,
		party, member.person_id, personinfo.data_value as dob
	from consinfo
		join member on consinfo.constituency = member.constituency
			and left_house='9999-12-31' and house = 1
		left join personinfo on member.person_id = personinfo.person_id
			and personinfo.data_key='date_of_birth'
	where consinfo.data_key = 'bbc_constituency_id'
		and consinfo.data_value = " . mysql_escape_string($bbc_cons_id));
if (!$q->rows())
	error('Unknown constituency ID');

$cons = $q->field(0, 'constituency');
$pid = $q->field(0, 'person_id');

$action = get_http_var('action');
if ($action == 'latest') {
	header("Location: http://www.theyworkforyou.com/rss/mp/$pid.rdf");
	exit;
} elseif ($action == 'data') {
	$output = _api_getMP_row($q->row(0));
	foreach (array('house', 'first_name', 'last_name', 'title', 'person_id') as $key) {
		unset($output[$key]);
	}
	if (isset($party_sites[$output['party']])) {
		$output['party_site'] = $party_sites[$output['party']];
	} elseif ($output['full_name'] == 'Richard Taylor') {
    		$output['party_site'] = 'http://www.healthconcern.org.uk/';
	} elseif ($output['full_name'] == 'Dai Davies') {
		$output['party_site'] = 'http://www.blaenaugwentpeoplesvoice.org/';
	} else {
		$output['party_site'] = '';
	}
	if (isset($output['image'])) $output['image'] = 'http://www.theyworkforyou.com' . $output['image'];
	$output['bbc'] = 'http://news.bbc.co.uk/1/shared/mpdb/html/' . $bbc_cons_id . '.stm';
	$output['link'] = 'http://www.theyworkforyou.com/mp/'
		. make_member_url(htmlentities($output['full_name']), $cons, 1); # XXX make_member_url needs it encoded!
	$output['email_alert'] = 'http://www.theyworkforyou.com/alert/?only=1&pid=' . $pid;
	#$output['writetothem'] = 'http://www.writetothem.com/';
	#$output['hearfromyourmp'] = 'http://www.hearfromyourmp.com/';
	# Link to WTT/HFYMP?
	api_output($output);
} else {
	error('Unknown action');
}

function error($error = 'Unknown error') {
	api_error($error);
	exit;
}

