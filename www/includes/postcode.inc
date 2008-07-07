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
	if (!preg_match('#^([A-Z]{1,2})(\d+) (\d)([A-Z]{2})#', canonicalise_postcode($pc), $m))
		return false;

	# Check for Scottish postal areas
	if (in_array($m[1], array('AB', 'DD', 'DG', 'EH', 'FK', 'G', 'HS', 'IV', 'KA', 'KW', 'KY', 'ML', 'PA', 'PH', 'ZE')))
		return true;

	# Damn postcodes crossing country boundaries
	if ($m[1]=='TD') {
		if ($m[2]!=15 && $m[2]!=12 && $m[2]!=9) return true; # TD1-8, 10-11, 13-14 all in Scotland
		if ($m[2]==9) {
			if ($m[3]!=0) return true; # TD9 1-9 all in Scotland
			if (!in_array($m[4], array('TJ','TP','TR','TS','TT','TU','TW'))) return true; # Nearly all of TD9 0 in Scotland
		}
		$m[5] = substr($m[4], 0, 1);
		if ($m[2]==12) { # $m[3] will be 4 currently.
			if ($m[4]=='XE') return true;
			if (in_array($m[5], array('A','B','D','E','H','J','L','N','W','Y'))) return true; # These bits of TD12 4 are in Scotland, others (Q, R, S, T, U, X) in England
		}
		# TD15 is mostly England
		if ($m[2]==15) {
			if ($m[3]!=1) return false; # TD15 2 and 9 are in England
			if (in_array($m[4], array('SU','SZ','UF','UG','UH','UJ','UL','US','UZ','WY','WZ'))) return true;
			if ($m[5]=='T') return true; # TD15 1T* in Scotlan
			if ($m[5]=='X' && $m[4]!='XX') return true; # TD15 1XX in England
		}
	}

	# Not in Scotland
	return false;
}

function postcode_is_ni($pc) {
	$prefix = substr(canonicalise_postcode($pc), 0, 2);
	if ($prefix == 'BT')
		return true;
	return false;
}

