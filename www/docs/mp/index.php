<?php

/* For displaying info about a person for a postcode or constituency.

	This page accepts either 'm' (a member_id), 'pid' (a person_id),
	'c' (a postcode or constituency), or 'n' (a name).
	
	First, we check to see if a person_id's been submitted.
	If so, we display that person.
	
	Else, we check to see if a member_id's been submitted.
	If so, we display that person.
	
	Otherwise, we then check to see if a postcode's been submitted.
	If it's valid we put it in a cookie.
	
	If no postcode, we check to see if a constituency's been submitted.
	
	If neither has been submitted, we see if either the user is logged in 
	and has a postcode set or the user has a cookied postcode from a previous
	search.
		
	If we have a valid constituency after all this, we display its MP.
	
	Either way, we print the forms.

*/

include_once "../../includes/easyparliament/init.php";
include_once INCLUDESPATH."easyparliament/member.php";

// From http://cvs.sourceforge.net/viewcvs.py/publicwhip/publicwhip/website/
include_once INCLUDESPATH."postcode.inc";
include_once INCLUDESPATH . 'technorati.php';
include_once '../api/api_getGeometry.php';
include_once '../api/api_getConstituencies.php';

twfy_debug_timestamp("after includes");

$errors = array();

// Some legacy URLs use 'p' rather than 'pid' for person id. So we still
// need to detect these.
$pid = get_http_var('pid') != '' ? get_http_var('pid') : get_http_var('p');
$name = strtolower(str_replace(array('_'), array(' '), get_http_var('n')));
$cconstituency = strtolower(str_replace(array('_','.',' and '), array(' ','&amp;',' &amp; '), get_http_var('c'))); # *** postcode functions use global $constituency!!! ***
if ($cconstituency == 'mysociety test constituency') {
	header("Location: stom.html");
	exit;
}

# Special case names
if ($name == 'sion simon') $name = "si&ocirc;n simon";
if ($name == 'sian james') $name = "si&acirc;n james";
if ($name == 'lembit opik') $name = "lembit &ouml;pik";
if ($name == 'bairbre de brun') $name = "bairbre de br&uacute;n";

# Special stuff for Ynys Mon
if ($cconstituency == 'ynys mon') $cconstituency = "ynys m&ocirc;n"; # Stop infinite loop
# And cope with Unicode URL
if (preg_match("#^ynys m\xc3\xb4n#i", $cconstituency))
	$cconstituency = "ynys m&ocirc;n";

// Redirect for MP recent appearanecs
if (get_http_var('recent')) {
	if ($THEUSER->postcode_is_set() && !$pid) {
		$MEMBER = new MEMBER(array('postcode' => $THEUSER->postcode()));
		if ($MEMBER->person_id())
			$pid = $MEMBER->person_id();
	} 
	if ($pid) {
		$URL = new URL('search');
		$URL->insert( array('pid'=>$pid, 'pop'=>1) );
		header('Location: http://' . DOMAIN . $URL->generate('none'));
		exit;
	}
}

/////////////////////////////////////////////////////////
// CHECK SUBMITTED MEMBER (term of office) ID.

if (get_http_var('c4')) $this_page = 'c4_mp';
elseif (get_http_var('c4x')) $this_page = 'c4x_mp';
elseif (get_http_var('peer')) $this_page = 'peer';
elseif (get_http_var('mla')) $this_page = 'mla';
else $this_page = 'mp';

if (is_numeric(get_http_var('m'))) {
	// Got a member id, redirect to the canonical MP page, with a person id.
	$MEMBER = new MEMBER(array('member_id' => get_http_var('m')));
	member_redirect($MEMBER);

} elseif (is_numeric($pid)) {

	// Normal, plain, displaying an MP by person ID.
	$MEMBER = new MEMBER(array('person_id' => $pid));
	member_redirect($MEMBER);

/////////////////////////////////////////////////////////
// CHECK SUBMITTED POSTCODE

} elseif (get_http_var('pc') != '') {
	// User has submitted a postcode, so we want to display that. 
	$pc = get_http_var('pc');
	$pc = preg_replace('#[^a-z0-9 ]#i', '', $pc);
	if (validate_postcode($pc)) {
		twfy_debug ('MP', "MP lookup by postcode");
		$constituency = strtolower(postcode_to_constituency($pc));
		if ($constituency == "connection_timed_out") {
			$errors['pc'] = "Sorry, we couldn't check your postcode right now, as our postcode lookup server is under quite a lot of load. Please use the 'All MPs' link above to browse all the MPs.";
		} elseif ($constituency == "") {
			$errors['pc'] = "Sorry, ".htmlentities($pc) ." isn't a known postcode";
			twfy_debug ('MP', "Can't display an MP, as submitted postcode didn't match a constituency");
		} else {
			// Redirect to the canonical MP page, with a person id.
			$MEMBER = new MEMBER(array('constituency' => $constituency));
			if ($MEMBER->person_id()) {
				// This will cookie the postcode.
				$THEUSER->set_postcode_cookie($pc);
			}
			member_redirect($MEMBER);
		}
	} else {
		$errors['pc'] = "Sorry, ".htmlentities($pc) ." isn't a valid postcode";
		twfy_debug ('MP', "Can't display an MP because the submitted postcode wasn't of a valid form.");
	}

/////////////////////////////////////////////////////////
// DOES THE USER HAVE A POSTCODE ALREADY SET?
// (Either in their logged-in details or in a cookie from a previous search.)

} elseif ($THEUSER->postcode_is_set() && $name == '' && $cconstituency == '') {
	$MEMBER = new MEMBER(array('postcode' => $THEUSER->postcode()));
	member_redirect($MEMBER);
} elseif ($name && $cconstituency) {
	$MEMBER = new MEMBER(array('name'=>$name, 'constituency'=>$cconstituency));
	if (!$MEMBER->canonical) {
		member_redirect($MEMBER);
	}
	if ($MEMBER->the_users_mp) {
		$this_page = 'yourmp';
	}
	twfy_debug ('MP', 'Displaying MP by name');
} elseif ($name) {
	$MEMBER = new MEMBER(array('name' => $name));
	if ((($MEMBER->house_disp==1 && $this_page!='mp')
	    || ($MEMBER->house_disp==2 && $this_page!='peer'))
	    && ($MEMBER->valid || !is_array($MEMBER->person_id()))) {
		member_redirect($MEMBER);
	}
} elseif ($cconstituency) {

if ($cconstituency == 'your &amp; my society') {
	header('Location: /mp/stom%20teinberg');
	exit;
}
	$MEMBER = new MEMBER(array('constituency' => $cconstituency));
	member_redirect($MEMBER);
} else {
	// No postcode, member_id or person_id to use.
	twfy_debug ('MP', "We don't have any way of telling what MP to display");
}

	


/////////////////////////////////////////////////////////
// DISPLAY AN MP

if (isset($MEMBER) && is_array($MEMBER->person_id())) {
	$PAGE->page_start();
	$PAGE->stripe_start();
	print '<p>That name is not unique. Please select from the following:</p><ul>';
	$cs = $MEMBER->constituency();
	$c = 0;
	foreach ($MEMBER->person_id() as $id) {
		print '<li><a href="' . WEBPATH . 'mp/?pid='.$id.'">' . ucwords(strtolower($name)) . ', ' . $cs[$c++] . '</a></li>';
	}
	print '</ul>';

	$MPSURL = new URL('mps');
	$sidebar = array(
		'type' => 'html',
		'content' => '<div class="block">
				<h4><a href="' . $MPSURL->generate() . '">Browse all MPs</a></h4>
			</div>'
	);
	
	$PAGE->stripe_end(array($sidebar));

} elseif (isset($MEMBER) && $MEMBER->person_id()) {
	
	twfy_debug_timestamp("before load_extra_info");
	$MEMBER->load_extra_info();
	twfy_debug_timestamp("after load_extra_info");
	
	$member_name = ucfirst($MEMBER->full_name());

	$subtitle = $member_name;
	if ($MEMBER->house(1)) {
		if (!$MEMBER->current_member(1)) {
			$subtitle .= ', former';
		}
		$subtitle .= ' MP, '.$MEMBER->constituency();
	}
	if ($MEMBER->house(3)) {
		if (!$MEMBER->current_member(3)) {
			$subtitle .= ', former';
		}
		$subtitle .= ' MLA, '.$MEMBER->constituency();
	}
	$DATA->set_page_metadata($this_page, 'subtitle', $subtitle);
	$DATA->set_page_metadata($this_page, 'heading', '');

	// So we can put a link in the <head> in $PAGE->page_start();	
	$feedurl = $DATA->page_metadata('mp_rss', 'url');
	$DATA->set_page_metadata($this_page, 'rss', $feedurl . $MEMBER->person_id() . '.rdf');

	twfy_debug_timestamp("before page_start");
	$PAGE->page_start();
	twfy_debug_timestamp("after page_start");

	twfy_debug_timestamp("before stripe start");
	$PAGE->stripe_start();
	twfy_debug_timestamp("after stripe start");
	
	twfy_debug_timestamp("before display of MP");
	$MEMBER->display();
	twfy_debug_timestamp("after display of MP");
	
	// SIDEBAR.

	// We have to generate this HTML to pass to stripe_end().
	$linkshtml = $PAGE->generate_member_links($MEMBER, $MEMBER->extra_info());
	
	$sidebars = array(
		array('type'=>'include', 'content' => 'donate'),
		array (
			'type'		=> 'include',
			'content'	=> 'mp_email_friend'
		),
		array (
			'type'		=> 'include',
			'content'	=> 'mp_speech_search'
		),
		array (
			'type'		=> 'html',
			'content'	=> $linkshtml
		)
	);

/*
	if ($rssurl = $DATA->page_metadata($this_page, 'rss')) {
		$sidebars[] = array (
			'type' 		=> 'html',
			'content'	=> $PAGE->member_rss_block(array('appearances' => WEBPATH . $rssurl))
		);
	}
*/

	if ($MEMBER->house(1)) {
		$previous_people = $MEMBER->previous_mps();
		if ($previous_people) {
			$sidebars[] = array(
				'type' => 'html',
				'content' => '<div class="block"><h4>Previous MPs in this constituency</h4><div class="blockbody"><ul>' . $previous_people . '</ul></div></div>'
			);
		}
		$future_people = $MEMBER->future_mps();
		if ($future_people) {
			$sidebars[] = array(
				'type' => 'html',
				'content' => '<div class="block"><h4>Succeeding MPs in this constituency</h4><div class="blockbody"><ul>' . $future_people . '</ul></div></div>'
			);
		}
	}

	if ($MEMBER->house(1)) {
		$lat = null; $lon = null;
		$geometry = _api_getGeometry_name($MEMBER->constituency());
		if (isset($geometry['centre_lat'])) {
			$lat = $geometry['centre_lat'];
			$lon = $geometry['centre_lon'];
		}
		if ($lat && $lon) {
			$nearby_consts = _api_getConstituencies_latitude($lat, $lon, 300);
			if ($nearby_consts) {
				$out = '<ul><!-- '.$lat.','.$lon.' -->';
				for ($k=1; $k<=min(5, count($nearby_consts)-1); $k++) {
					$name = $nearby_consts[$k]['name'];
					$dist = $nearby_consts[$k]['distance'];
					$out .= '<li><a href="' . WEBPATH . 'mp/?c=' . urlencode($name) . '">';
					$out .= $nearby_consts[$k]['name'] . '</a>';
					$out .= ' <small title="Centre to centre">(' . round($dist, 1) . ' km)</small>';
					$out .= '</li>';
				}
				$out .= '</ul>';
				$sidebars[] = array(
					'type' => 'html',
					'content' => '<div class="block"><h4>Nearby constituencies</h4><div class="blockbody">' . $out .' </div></div>'
				);
			}
		}
	}

	if (array_key_exists('office', $MEMBER->extra_info())) {
		$office = $MEMBER->extra_info();
		$office = $office['office'];
		$mins = '';
		foreach ($office as $row) {
			if ($row['to_date'] != '9999-12-31') {
				$mins .= '<li>' . prettify_office($row['position'], $row['dept']);
			       	$mins .= ' (';
				if (!($row['source'] == 'chgpages/selctee' && $row['from_date'] == '2004-05-28')
					&& !($row['source'] == 'chgpages/privsec' && $row['from_date'] == '2004-05-13')) {
					if ($row['source'] == 'chgpages/privsec' && $row['from_date'] == '2005-11-10')
						$mins .= 'before ';
					$mins .= format_date($row['from_date'],SHORTDATEFORMAT) . ' ';
				}
				$mins .= 'to ';
				if ($row['source'] == 'chgpages/privsec' && $row['to_date'] == '2005-11-10')
					$mins .= 'before ';
				$mins .= format_date($row['to_date'], SHORTDATEFORMAT);
				$mins .= ')</li>';
			}
		}
		if ($mins) {
			$sidebars[] = array('type'=>'html',
			'content' => '<div class="block"><h4>Other offices held in the past</h4><div class="blockbody"><ul>'.$mins.'</ul></div></div>');
		}
	}

/*	$body = technorati_pretty();
	if ($body) {
		$sidebars[] = array (
			'type' => 'html',
			'content' => '<div class="block"><h4>People talking about this MP</h4><div class="blockbody">' . $body . '</div></div>'
	);
	}
*/
	$sidebars[] = array('type'=>'html',
		'content' => '<div class="block"><h4>Journalist?</h4>
<div class="blockbody"><p>Please feel free to use the data
on this page, but if you do you must cite TheyWorkForYou.com in the
body of your articles as the source of any analysis or
data you get off this site. If you ignore this, we might have to start
keeping these sorts of records on you...</p></div></div>'
	);
	$PAGE->stripe_end($sidebars);

} else {
	// Something went wrong.
	
	/////////////////////////////////////////////////////////
	// DISPLAY FORM

	
	$PAGE->page_start();
	
	$PAGE->stripe_start();

	if (isset($errors['pc'])) {
		$PAGE->error_message($errors['pc']);
	}

	$PAGE->postcode_form();
	
	$PAGE->stripe_end();

}


$PAGE->page_end();



function member_redirect(&$MEMBER) {
	global $this_page;
	// We come here after creating a MEMBER object by various methods.
	// Now we redirect to the canonical MP page, with a person_id.
	if ($MEMBER->person_id()) {
		header('Location: ' . $MEMBER->url() );
		exit;
	}
}
?>
