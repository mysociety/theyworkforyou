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
# We use fsockopen rather than just fopen or file_get_contents so we can do timeouts
  $filename = POSTCODE_SEARCH_PATH .  urlencode($postcode);
  $file = ""; // just to be safe. Dunno, if this is really needed
  // try several times with low timeout
  for ($i = 0; $i < 4; $i++) {
	$fp = @fsockopen(POSTCODE_SEARCH_DOMAIN, POSTCODE_SEARCH_PORT, $errno, $errstr, 10);
	if ($fp) {
		break;
	}
  }
  if ($fp) {
    stream_set_timeout($fp, 10);
    $sockstart = getmicrotime(); # feof doesn't time out, so we have to measure ourselves also
        $out = "GET $filename HTTP/1.0\r\n";
        $out .= "Host: " . POSTCODE_SEARCH_DOMAIN. "\r\n";
        $out .= "Connection: Close\r\n\r\n";

        fwrite($fp, $out);
        $inbody = false;
        while (!feof($fp) and (getmicrotime() < $sockstart + 10)) {
            $line = fgets($fp, 1024);
            if ($line == "\r\n")
                $inbody = true;
            if ($inbody)
                $file .= $line;
        }
        // give ourselves 10 seconds for a response
        if (getmicrotime() >= $sockstart + 10) {
            trigger_error("Postcode database:  Connection timed out.", E_USER_WARNING);
            return 'CONNECTION_TIMED_OUT';
        }
        
        fclose($fp);
  } else {
	trigger_error("Postcode database: Socket error no. $errno, $errstr.", E_USER_WARNING);
        return 'CONNECTION_TIMED_OUT';
  }
	
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
