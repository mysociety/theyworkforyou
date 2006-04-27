<?php

include_once "../../includes/easyparliament/init.php";
include_once INCLUDESPATH."easyparliament/member.php";
include_once INCLUDESPATH."postcode.inc";

$pc = get_http_var('pc');
$pc = preg_replace('#[^a-z0-9 ]#i', '', $pc);
if (!$pc) exit;

if (validate_postcode($pc)) {
	$constituency = strtolower(postcode_to_constituency($pc));
	if ($constituency == "CONNECTION_TIMED_OUT") {
            $errors['pc'] = "Sorry, we couldn't check your postcode right now. Please use the 'All Mps' link above to browse MPs";
	} elseif ($constituency == "") {
            $errors['pc'] = "Sorry, ".htmlentities($pc) ." isn't a known postcode";
            debug ('MP', "Can't display an MP, as submitted postcode didn't match a constituency");
       } else {
            $MEMBER = new MEMBER(array('constituency' => $constituency));
			if ($MEMBER->person_id()) {
				// This will cookie the postcode.
				$THEUSER->set_postcode_cookie($pc);
			}

			if ($MEMBER->person_id()) {
				header('Location: http://' . DOMAIN . '/rss/mp/' . $MEMBER->person_id() . '.rdf');
			}
		}
	} else {
		$errors['pc'] = "Sorry, ".htmlentities($pc) ." isn't a valid postcode";
		debug ('MP', "Can't display an MP because the submitted postcode wasn't of a valid form.");
	}


