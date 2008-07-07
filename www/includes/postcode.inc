<?php

include_once INCLUDESPATH . 'constituencies.inc';

//returns the constituency name, given uh, a postcode.
function postcode_to_constituency($postcode) {
    global $last_postcode, $last_postcode_value;

    if(!defined("POSTCODE_SEARCH_DOMAIN") || !POSTCODE_SEARCH_DOMAIN) {
        return fake_postcode($postcode);
    }

    $postcode = canonicalise_postcode($postcode);

    if ($last_postcode == $postcode) {
        twfy_debug ("TIME", "Postcode $postcode looked up last time, is $last_postcode_value .");
        return $last_postcode_value['WMC'];
    }

    $start = getmicrotime();
    twfy_debug_timestamp();
    $ret = postcode_to_constituency_internal($postcode);
    $duration = getmicrotime() - $start;
    twfy_debug ("TIME", "Postcode $postcode lookup took $duration seconds, returned $ret.");
    twfy_debug_timestamp();

    if (is_string($ret)) return $ret;

    $last_postcode = $postcode;
    $last_postcode_value = $ret;
    return $ret['WMC'];
}

function postcode_to_constituencies($postcode) {
    global $last_postcode, $last_postcode_value;
    $postcode = canonicalise_postcode($postcode);
    if ($last_postcode == $postcode) {
        twfy_debug ("TIME", "Postcode $postcode looked up last time, is $last_postcode_value .");
        return $last_postcode_value;
    }

    $ret = postcode_to_constituency_internal($postcode);
    if (is_string($ret)) return $ret;

    $last_postcode = $postcode;
    $last_postcode_value = $ret;
    return $ret;
}

// map a postcode to an MP, random but deterministic
function fake_postcode ($postcode){
	$db = new ParlDB;
	$fake_cons_id=abs(crc32($postcode)%630);
 	$query = "select name from constituency where main_name and cons_id = '".$fake_cons_id."'";
	$q2 = $db->query($query);
	if ($q2->rows <= 0)
		return false;

	return $q2->field(0, "name");
}

function postcode_to_constituency_internal($postcode) {
	# Try and match with regexp to exclude non postcodes quickly
	if (!validate_postcode($postcode))
		return '';
  
	$db = new ParlDB;

	$q = $db->query('select name from postcode_lookup where postcode = "'. mysql_escape_string($postcode).'"');
	if ($q->rows > 0) {
		$name = $q->field(0, 'name');
		if (postcode_is_scottish($postcode)) {
			$name = explode('|', $name);
			if (count($name)==3)
				return array('WMC' => $name[0], 'SPC' => $name[1], 'SPE' => $name[2]);
		} elseif (postcode_is_ni($postcode)) {
			$name = explode('|', $name);
			if (count($name)==2)
				return array('WMC' => $name[0], 'NIE' => $name[1]);
		} else {
			return array('WMC' => $name);
		}
	}

	# $pid = getmypid();
	# $tracefn = '/tmp/apache.ktrace.' . getmicrotime();
	# system("/home/fawkes/bin/ktracewrap -p $pid -f " . $tracefn);

	$filename = POSTCODE_SEARCH_PATH .  urlencode($postcode);
	$ch = curl_init('http://' . POSTCODE_SEARCH_DOMAIN . $filename);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	$file = curl_exec($ch);
	# system("/home/fawkes/bin/ktracewrap -c -p $pid");
	if (curl_errno($ch)) {
		$errno = curl_errno($ch);
		trigger_error("Postcode database: " . $errno . ' ' . curl_error($ch), E_USER_WARNING);
		return 'CONNECTION_TIMED_OUT';
	}
	curl_close($ch);
	#unlink($tracefn);

	if (!preg_match('#<areas>#', $file, $m)) {
		trigger_error("Postcode database is not working. Content:\n".$file.", request: ". $filename, E_USER_WARNING);
		return '';
	}
	preg_match_all('#<name type="(.*?)">(.*?)</name>#', $file, $m, PREG_SET_ORDER);
	$areas = array();
	foreach ($m as $row) {
		if ($row[1]=='WMC' || $row[1]=='SPC' || $row[1]=='SPE' || $row[1]=='NIE')
			$areas[$row[1]] = $row[2];
	}

	if (!isset($areas['WMC'])) {
		return '';
	}

	# Normalise name - assume SP and NI are already so...
	$normalised = normalise_constituency_name(strtolower($areas['WMC']));
	if ($normalised) {
		$areas['WMC'] = $normalised;
		if (postcode_is_scottish($postcode)) {
			$serialized = "$areas[WMC]|$areas[SPC]|$areas[SPE]";
		} elseif (postcode_is_ni($postcode)) {
			$serialized = "$areas[WMC]|$areas[NIE]";
		} else {
			$serialized = $normalised;
		}
		$db->query('replace into postcode_lookup values("'. mysql_escape_string($postcode).'", "'.mysql_escape_string($serialized).'")');
	} else {
		return '';
	}

	return $areas;
}

function canonicalise_postcode($pc) {
	$pc = str_replace(' ', '', $pc);
	$pc = trim($pc);
	$pc = strtoupper($pc);
	$pc = preg_replace('#(\d[A-Z]{2})#', ' $1', $pc);
	return $pc;
}

function postcode_is_scottish($pc) {
	preg_match('#^(.*?)\d#', canonicalise_postcode($pc), $m);
	if (in_array($m[1], array('AB', 'DD', 'DG', 'EH', 'FK', 'G', 'HS', 'IV', 'KA', 'KY', 'ML', 'PA', 'PH', 'TD', 'ZE', 'KW')))
		return true;
	return false;
}

function postcode_is_ni($pc) {
	$prefix = substr(canonicalise_postcode($pc), 0, 2);
	if ($prefix == 'BT')
		return true;
	return false;
}

