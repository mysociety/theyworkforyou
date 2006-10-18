<?php

// NOTE: This is now the master copy, the file postcode.php here:
// http://cvs.sourceforge.net/viewcvs.py/publicwhip/publicwhip/website/
// is copied from this theyworkforyou.com version.

include_once INCLUDESPATH."constituencies.inc";


// Whether the form of the postcode is one or not
function is_postcode($postcode)
{
    //return preg_match("/^[A-Z]{1,2}\d[A-Z\d]? ?\d[ABD-HJLNP-UW-Z]{2}$/i", $postcode);
	return validate_postcode($postcode);	// in utility.php
}

//returns the constituency name, given uh, a postcode.
function postcode_to_constituency($postcode) {
    global $last_postcode;
    global $last_postcode_value;

if(!defined("POSTCODE_SEARCH_DOMAIN")){
        		return fake_postcode($postcode);
}

	$postcode = canonicalise_postcode($postcode);

    if ($last_postcode == $postcode) {
        twfy_debug ("TIME", "Postcode $postcode looked up last time, is $last_postcode_value .");
        return $last_postcode_value;
    }

    $start = getmicrotime();
    twfy_debug_timestamp();
    $ret = postcode_to_constituency_internal($postcode);
    $duration = getmicrotime() - $start;
    twfy_debug ("TIME", "Postcode $postcode lookup took $duration seconds, returned $ret.");
    twfy_debug_timestamp();
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
	if (!is_postcode($postcode))
		return '';
  
	$db = new ParlDB;

	$q = $db->query('select name from postcode_lookup where postcode = "'. mysql_escape_string($postcode).'"');
	if ($q->rows > 0) {
		$name = $q->field(0, 'name');
		return $name;
	}

# Otherwise hit the server (URL is private)

	$pid = getmypid();
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

  // Back to the original code.
  $file = str_replace("&", "&amp;", $file);
  $file = str_replace("&amp;amp;", "&amp;", $file);
  
  global $tags, $constituency;
  $tags = array();
  $constituency = null;

  if (!preg_match('#<FAXYOURMP>#', $file, $m)) {
      trigger_error("Postcode database is not working. Content:\n".$file.", request: ". $filename, E_USER_WARNING);
      return '';
  }
  if (!preg_match('#<CONSTITUENCY_NAME>(.*?)</CONSTITUENCY_NAME>#', $file, $m)) {
      return '';
    }
  $constituency = str_replace("  ", " ", $m[1]);
  
# Convert to canonical name
#    print "<pre>$file</pre><br>$constituency<br>";
    $normalised = normalise_constituency_name(strtolower($constituency));
    if ($normalised) {
	$db->query('replace into postcode_lookup values("'. mysql_escape_string($postcode).'", "'.mysql_escape_string($normalised).'")');
        return $normalised;
    }
    else
        return '';

}

function canonicalise_postcode($pc) {
	$pc = str_replace(' ', '', $pc);
	$pc = trim($pc);
	$pc = strtoupper($pc);
	$pc = preg_replace('#(\d[A-Z]{2})#', ' $1', $pc);
	return $pc;
}

?>
