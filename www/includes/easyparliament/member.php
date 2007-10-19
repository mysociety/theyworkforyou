<?php

include_once INCLUDESPATH."postcode.inc";
include_once INCLUDESPATH."easyparliament/glossary.php";

class MEMBER {

	var $member_id;
	var $person_id;
	var $first_name;
	var $title;
	var $last_name;
	var $constituency; 
	var $party;
	var $other_parties;
	var $houses = array();
	var $entered_house = array();
	var $left_house = array();
	var $extra_info = array();
	// Is this MP THEUSERS's MP?
	var $the_users_mp = false;
	var $canonical = true;
	var $house_disp = 0; # Which house we should display this person in
	
	// Mapping member table 'house' numbers to text.
	var $houses_pretty = array(
		0 => 'Royal Family',
		1 => 'House of Commons',
		2 => 'House of Lords',
		3 => 'Northern Ireland Assembly',
	);
	
	// Mapping member table reasons to text.
	var $reasons = array(
		'became_peer'		=> 'Became peer',
		'by_election'		=> 'Byelection',
		'changed_party'		=> 'Changed party',
		'declared_void'		=> 'Declared void',
		'died'			=> 'Died',
		'disqualified'		=> 'Disqualified',
		'general_election' 	=> 'General election',
		'general_election_standing' 	=> array('General election (standing again)', 'General election (stood again)'),
		'general_election_not_standing' 	=> 'did not stand for re-election',
		'reinstated'		=> 'Reinstated',
		'resigned'		=> 'Resigned',
		'still_in_office'	=> 'Still in office',
		'dissolution'		=> 'Dissolved for election'
	);
	
	function MEMBER ($args) {
		// $args is a hash like one of:
		// member_id 		=> 237
		// person_id 		=> 345
		// constituency 	=> 'Braintree'
		// postcode			=> 'e9 6dw'

		// If just a constituency we currently just get the current member for
		// that constituency.
		
		global $PAGE, $this_page;
		
		$this->db = new ParlDB;
		$person_id = '';	
		if (isset($args['member_id']) && is_numeric($args['member_id'])) {
			$person_id = $this->member_id_to_person_id($args['member_id']);
		} elseif (isset($args['name'])) {
			$con = isset($args['constituency']) ? $args['constituency'] : '';
			$person_id = $this->name_to_person_id($args['name'], $con);
		} elseif (isset($args['constituency'])) {
			$person_id = $this->constituency_to_person_id($args['constituency']);
		} elseif (isset($args['postcode'])) {
			$person_id = $this->postcode_to_person_id($args['postcode']);
		} elseif (isset($args['person_id']) && is_numeric($args['person_id'])) {
			$person_id = $args['person_id'];	
		}
		
		if (!$person_id) {
			$this->valid = false;
			return;
		}

		if (is_array($person_id)) {
			if ($this_page == 'peer') {
				# Hohoho, how long will I get away with this for?
				#   Not very long, it made Lord Patel go wrong
				$person_id = $person_id[0];
			} else {
				$this->valid = false;
				$this->person_id = $person_id;
				return;
			}
		}
		$this->valid = true;
		
		// Get the data.
		$q = $this->db->query("SELECT member_id, house, title,
			first_name, last_name, constituency, party,
			entered_house, left_house, entered_reason, left_reason, person_id
			FROM member
			WHERE person_id = '" . mysql_escape_string($person_id) . "'
                        ORDER BY left_house DESC, house");

		if (!$q->rows() > 0) {
			$this->valid = false;
			return;
		}

		$this->house_disp = 0;
		for ($row=0; $row<$q->rows(); $row++) {
			$house          = $q->field($row, 'house');
			if (!in_array($house, $this->houses)) $this->houses[] = $house;
			$const          = $q->field($row, 'constituency');
			$party		= $q->field($row, 'party');
			$entered_house	= $q->field($row, 'entered_house');
			$left_house	= $q->field($row, 'left_house');
			$entered_reason	= $q->field($row, 'entered_reason');
			$left_reason	= $q->field($row, 'left_reason');

			$entered_time = strtotime($entered_house);
			$left_time = strtotime($left_house); if ($left_time === -1) $left_time = false;

			if (!isset($this->entered_house[$house]) || $entered_time < $this->entered_house[$house]['time']) {
				$this->entered_house[$house] = array(
					'time' => $entered_time,
					'date' => $entered_house,
					'date_pretty' => $this->entered_house_text($entered_house),
					'reason' => $this->entered_reason_text($entered_reason),
				);
			} 

			if (!isset($this->left_house[$house])) {
				$this->left_house[$house] = array(
					'time' => $left_time,
					'date' => $left_house,
					'date_pretty' => $this->left_house_text($left_house),
					'reason' => $this->left_reason_text($left_reason),
					'constituency' => $const,
					'party' => $this->party_text($party)
				);
			}

			if ( $house==0 || (!$this->house_disp && $house==3) || ($this->house_disp!=2 && $house==2)
			    || ((!$this->house_disp || $this->house_disp==3) && $house==1) ) {
				$this->house_disp = $house;
				$this->constituency = $const;
				$this->party = $party;

				$this->member_id	= $q->field($row, 'member_id');
				$this->title		= $q->field($row, 'title');
				$this->first_name	= $q->field($row, 'first_name');
				$this->last_name	= $q->field($row, 'last_name');
				$this->person_id	= $q->field($row, 'person_id');
			}

			if ($left_reason == 'changed_party') {
				$this->other_parties[] = array(
					'from' => $this->party_text($q->field($row, 'party')),
					'date' => $q->field($row, 'left_house')
				);
			}
		}

		// Loads extra info from DB - you now have to call this from outside
	        // when you need it, as some uses of MEMBER are lightweight (e.g.
	        // in searchengine.php)
		// $this->load_extra_info();
		
		$this->set_users_mp();
	}
	
	function member_id_to_person_id ($member_id) {
		global $PAGE;
		$q = $this->db->query("SELECT person_id FROM member 
					WHERE member_id = '" . mysql_escape_string($member_id) . "'");
		if ($q->rows > 0) {
			return $q->field(0, 'person_id');
		} else {
			$PAGE->error_message("Sorry, there is no member with a member ID of '" . htmlentities($member_id) . "'.");
			return false;
		}	
	}
	
	function postcode_to_person_id ($postcode) {
		twfy_debug ('MP', "postcode_to_person_id converting postcode to person");
		$constituency = strtolower(postcode_to_constituency($postcode));
		return $this->constituency_to_person_id($constituency);
	}
	
	function constituency_to_person_id ($constituency) {
		global $PAGE;
		if ($constituency == '') {
			$PAGE->error_message("Sorry, no constituency was found.");
			return false;
		}

		if ($constituency == 'Orkney ') {
			$constituency = 'Orkney &amp; Shetland';
		}

		$normalised = normalise_constituency_name($constituency);
		if ($normalised) $constituency = $normalised;

	        $q = $this->db->query("SELECT person_id FROM member 
					WHERE constituency = '" . mysql_escape_string($constituency) . "' 
					AND left_reason = 'still_in_office'");

		if ($q->rows > 0) {
			return $q->field(0, 'person_id');
		} else {
			$q = $this->db->query("SELECT person_id FROM member WHERE constituency = '".mysql_escape_string($constituency)."' ORDER BY left_house DESC LIMIT 1");
			if ($q->rows > 0) {
				return $q->field(0, 'person_id');
			} else {
				$PAGE->error_message("Sorry, there is no current member for the '" . htmlentities(html_entity_decode($constituency)) . "' constituency.");
				return false;
			}
		}
	}

	function name_to_person_id ($name, $const='') {
		global $PAGE, $this_page;
		if ($name == '') {
			$PAGE->error_message('Sorry, no name was found.');
			return false;
		}
		# Matthew made this change, but I don't know why.  It broke
		# Iain Duncan Smith, so I've put it back.  FAI 2005-03-14
		#		$success = preg_match('#^(.*? .*?) (.*?)$#', $name, $m);
		$q = "SELECT DISTINCT person_id,constituency FROM member WHERE ";
		if ($this_page=='peer') {
			$success = preg_match('#^(.*?) (.*?) of (.*?)$#', $name, $m);
			if (!$success)
				$success = preg_match('#^(.*?)() of (.*?)$#', $name, $m);
			if (!$success)
				$success = preg_match('#^(.*?) (.*?)()$#', $name, $m);
			if (!$success) {
				$PAGE->error_message('Sorry, that name was not recognised.');
				return false;
			}
			$title = mysql_escape_string($m[1]);
			$last_name = mysql_escape_string($m[2]);
			$const = $m[3];
			$q .= "house = 2 AND title = '$title' AND last_name='$last_name'";
		} elseif ($this_page=='mla') {
			$success = preg_match('#^(.*?) (.*?) (.*?)$#', $name, $m);
			if (!$success)
				$success = preg_match('#^(.*?)() (.*)$#', $name, $m);
			if (!$success) {
				$PAGE->error_message('Sorry, that name was not recognised.');
				return false;
			}
			$first_name = mysql_escape_string($m[1]);
			$middle_name = mysql_escape_string($m[2]);
			$last_name = mysql_escape_string($m[3]);
			$q .= "house = 3 AND (";
			$q .= "(first_name='$first_name $middle_name' AND last_name='$last_name')";
			$q .= " or (first_name='$first_name' AND last_name='$middle_name $last_name') )";
		} elseif (strstr($this_page, 'mp')) {
			$success = preg_match('#^(.*?) (.*?) (.*?)$#', $name, $m);
			if (!$success)
				$success = preg_match('#^(.*?)() (.*)$#', $name, $m);
			if (!$success) {
				$PAGE->error_message('Sorry, that name was not recognised.');
				return false;
			}
			$first_name = $m[1];
			$middle_name = $m[2];
			$last_name = $m[3];
			# if ($title) $q .= 'title = \'' . mysql_escape_string($title) . '\' AND ';
			$q .= "house =1 AND ((first_name='".mysql_escape_string($first_name." ".$middle_name)."' AND last_name='".mysql_escape_string($last_name)."') OR ".
			"(first_name='".mysql_escape_string($first_name)."' AND last_name='".mysql_escape_string($middle_name." ".$last_name)."'))";
			if ($const) {
				$normalised = normalise_constituency_name($const);
				if ($normalised && strtolower($normalised) != strtolower($const)) {
					$this->canonical = false;
					$const = $normalised;
				}
			}
		} elseif ($this_page == 'royal') {
			$q .= ' house = 0';
		}

		if ($const || $this_page=='peer') {
			$q .= ' AND constituency=\''.mysql_escape_string($const)."'";
		}
		$q .= ' ORDER BY left_house DESC';
		$q = $this->db->query($q);
		if ($q->rows > 1) {
			# Hacky as a very hacky thing that's graduated in hacking from the University of Hacksville
			# Anyone who wants to do it properly, feel free

			$person_ids = array(); $consts = array();
			for ($i=0; $i<$q->rows(); ++$i) {
				$pid = $q->field($i, 'person_id');
				if (!in_array($pid, $person_ids)) {
					$person_ids[] = $pid;
					$consts[] = $q->field($i, 'constituency');
				}
			}
			if (sizeof($person_ids) == 1) return $person_ids[0];
			$this->constituency = $consts;
			return $person_ids;
		} elseif ($q->rows > 0) {
			return $q->field(0, 'person_id');
		} elseif ($const) {
			$this->canonical = false;
			return $this->name_to_person_id($name);
		} else {
			$PAGE->error_message("Sorry, there is no current member with that name.");
			return false;
		}
	}

	function set_users_mp () {
		// Is this MP THEUSER's MP?
		global $THEUSER;
		if (is_object($THEUSER) && $THEUSER->postcode_is_set() && $this->current_member(1)) {
			$pc = $THEUSER->postcode();
			twfy_debug ('MP', "set_users_mp converting postcode to person");
			$constituency = strtolower(postcode_to_constituency($pc));
			if ($constituency == strtolower($this->constituency())) {
				$this->the_users_mp = true;
			}
		}
	}
	

    // Grabs extra information (e.g. external links) from the database
    function load_extra_info()
    {

	$q = $this->db->query('SELECT * FROM moffice WHERE person=' .
		mysql_escape_string($this->person_id) . ' ORDER BY from_date DESC');
	for ($row=0; $row<$q->rows(); $row++) {
		$this->extra_info['office'][] = $q->row($row);
	}
	
        // Info specific to member id (e.g. attendance during that period of office)
	#$q = $this->db->query("SELECT data_key, data_value,
	#			(SELECT count(member_id) FROM memberinfo AS m2
	#				WHERE m2.data_key=memberinfo.data_key AND m2.data_value=memberinfo.data_value) AS joint
	#               FROM 	memberinfo
	#               WHERE	member_id = '" . mysql_escape_string($this->member_id) . "'
	#               ");
        $q = $this->db->query("SELECT data_key, data_value
                        FROM 	memberinfo
                        WHERE	member_id = '" . mysql_escape_string($this->member_id) . "'
                        ");
        for ($row = 0; $row < $q->rows(); $row++) {
		$this->extra_info[$q->field($row, 'data_key')] = $q->field($row, 'data_value');
		#		if ($q->field($row, 'joint') > 1)
		#			$this->extra_info[$q->field($row, 'data_key').'_joint'] = true;
        }

        // Info specific to person id (e.g. their permanent page on the Guardian website)
	#$q = $this->db->query("SELECT data_key, data_value, (SELECT person_id FROM personinfo AS p2
	#		WHERE p2.person_id <> personinfo.person_id AND p2.data_key=personinfo.data_key AND p2.data_value=personinfo.data_value LIMIT 1) AS count
	#               FROM 	personinfo
	#               WHERE	person_id = '" . mysql_escape_string($this->person_id) . "'
	#               ");
        $q = $this->db->query("SELECT data_key, data_value
                        FROM 	personinfo
                        WHERE	person_id = '" . mysql_escape_string($this->person_id) . "'
                        ");
        for ($row = 0; $row < $q->rows(); $row++) {
            $this->extra_info[$q->field($row, 'data_key')] = $q->field($row, 'data_value');
	    #	    if ($q->field($row, 'count') > 1)
	    #	    	$this->extra_info[$q->field($row, 'data_key').'_joint'] = true;
        }

        // Info specific to constituency (e.g. election results page on Guardian website)
	if ($this->house(1)) {

        $q = $this->db->query("SELECT	data_key,
                                data_value
                        FROM 	consinfo
                        WHERE	constituency = '" . mysql_escape_string($this->constituency) . "'
                        ");
        for ($row = 0; $row < $q->rows(); $row++)
        {
            $this->extra_info[$q->field($row, 'data_key')] = $q->field($row, 'data_value');
        }

        if (array_key_exists('guardian_mp_summary', $this->extra_info))
        {
            $guardian_url = $this->extra_info['guardian_mp_summary'];
            $this->extra_info['guardian_register_member_interests'] = 
                    str_replace("/person/", "/person/parliamentrmi/", $guardian_url);
            $this->extra_info['guardian_parliament_history'] = 
                    str_replace("/person/", "/person/parliament/", $guardian_url);
            $this->extra_info['guardian_biography'] = 
                    $guardian_url;
#                    str_replace("/person/", "/person/biography/", $guardian_url);
            $this->extra_info['guardian_candidacies'] = 
                    str_replace("/person/", "/person/candidacies/", $guardian_url);
            $this->extra_info['guardian_howtheyvoted'] = 
                    str_replace("/person/", "/person/howtheyvoted/", $guardian_url);
            $this->extra_info['guardian_contactdetails'] = 
                    str_replace("/person/", "/person/contactdetails/", $guardian_url);
        }

	}

        if (array_key_exists('public_whip_rebellions', $this->extra_info))
        {
            $rebellions = $this->extra_info['public_whip_rebellions'];
            $rebel_desc = "<unknown>";
            if ($rebellions == 0)
                $rebel_desc = "never";
            else if ($rebellions == 1)
                $rebel_desc = "hardly ever";
            else if ($rebellions == 2 or $rebellions == 3)
                $rebel_desc = "occasionally";
            else if ($rebellions == 4 or $rebellions == 5)
                $rebel_desc = "sometimes";
            else if ($rebellions > 5)
                $rebel_desc = "quite often";
            $this->extra_info['public_whip_rebel_description'] = $rebel_desc; 
        }
        
	if (isset($this->extra_info['public_whip_attendrank'])) {
		$prefix = ($this->house(2) ? 'L' : '');
		$this->extra_info[$prefix.'public_whip_division_attendance_rank'] = $this->extra_info['public_whip_attendrank'];
		$this->extra_info[$prefix.'public_whip_division_attendance_rank_outof'] = $this->extra_info['public_whip_attendrank_outof'];
		$this->extra_info[$prefix.'public_whip_division_attendance_quintile'] = floor($this->extra_info['public_whip_attendrank'] / ($this->extra_info['public_whip_attendrank_outof']+1) * 5);
	}
	if ($this->house(2) && isset($this->extra_info['public_whip_division_attendance'])) {
		$this->extra_info['Lpublic_whip_division_attendance'] = $this->extra_info['public_whip_division_attendance'];
		unset($this->extra_info['public_whip_division_attendance']);
	}
	

        if (array_key_exists('register_member_interests_html', $this->extra_info) && ($this->extra_info['register_member_interests_html'] != ''))
        {
        	$args = array (
        		"sort" => "regexp_replace"
        	);
        	$GLOSSARY = new GLOSSARY($args);
        	$this->extra_info['register_member_interests_html'] = 
		$GLOSSARY->glossarise($this->extra_info['register_member_interests_html']);
        }

	$q = $this->db->query('select count(*) as c from alerts where criteria like "%speaker:'.$this->person_id.'%" and confirmed and not deleted');
	$this->extra_info['number_of_alerts'] = $q->field(0, 'c');

	if (isset($this->extra_info['reading_ease'])) {
		$this->extra_info['reading_ease'] = round($this->extra_info['reading_ease'], 2);
		$this->extra_info['reading_year'] = round($this->extra_info['reading_year'], 0);
		$this->extra_info['reading_age'] = $this->extra_info['reading_year'] + 4;
		$this->extra_info['reading_age'] .= '&ndash;' . ($this->extra_info['reading_year'] + 5);
	}

	# Public Bill Committees
	$q = $this->db->query('select bill_id,session,title,sum(attending) as a,sum(chairman) as c
		from pbc_members, bills
		where bill_id = bills.id and member_id = ' . $this->member_id()
		 . ' group by bill_id');
	$this->extra_info['pbc'] = array();
	for ($i=0; $i<$q->rows(); $i++) {
		$bill_id = $q->field($i, 'bill_id');
		$c = $this->db->query('select count(*) as c from hansard where major=6 and minor='.$bill_id.' and htype=10');
		$c = $c->field(0, 'c');
		$title = $q->field($i, 'title');
		$attending = $q->field($i, 'a');
		$chairman = $q->field($i, 'c');
		$this->extra_info['pbc'][$bill_id] = array(
			'title' => $title, 'session' => $q->field($i, 'session'),
			'attending'=>$attending, 'chairman'=>($chairman>0), 'outof' => $c
		);
	}

    }
	
	// Functions for accessing things about this Member.
	
	function member_id() 		{ return $this->member_id; }
	function person_id() 		{ return $this->person_id; }
	function first_name() 		{ return $this->first_name; }
	function last_name() 		{ return $this->last_name; }
	function full_name($no_mp_title = false) {
		$title = $this->title;
		if ($no_mp_title && $this->house_disp==1)
			$title = '';
		return member_full_name($this->house_disp, $title, $this->first_name, $this->last_name, $this->constituency);
	}
	function houses() {
		return $this->houses;
	}
	function house($house) {
		return in_array($house, $this->houses) ? true : false;
	}
	function house_text($house) {
		return $this->houses_pretty[$house];
	}
	function constituency() 	{ return $this->constituency; }
	function party() 			{ return $this->party; }
	function party_text($party = null) {
		global $parties;
		if (!$party)
			$party = $this->party;
		if (isset($parties[$party])) {
			return $parties[$party];
		} else {
			return $party;
		}
	}

	function entered_house($house = 0) {
		if ($house) return array_key_exists($house, $this->entered_house) ? $this->entered_house[$house] : null;
		return $this->entered_house;
	}
	function entered_house_text($entered_house) {
		if (!$entered_house) return '';
		list($year, $month, $day) = explode('-', $entered_house);
		if ($month==1 && $day==1 && $this->house(2)) {
			return $year;
		} elseif (checkdate($month, $day, $year) && $year != '9999') {
			return format_date($entered_house, LONGDATEFORMAT); 
		} else {
			return "n/a";
		}
	}
	
	function left_house($house = null) {
		if (!is_null($house))
			return array_key_exists($house, $this->left_house) ? $this->left_house[$house] : null;
		return $this->left_house;
	}
	function left_house_text($left_house) {
		if (!$left_house) return '';
		list($year, $month, $day) = explode('-', $left_house);
		if (checkdate($month, $day, $year) && $year != '9999') {
			return format_date($left_house, LONGDATEFORMAT); 
		} else {
			return "n/a";
		}
	}
	
	function entered_reason() 	{ return $this->entered_reason; }
	function entered_reason_text($entered_reason) { 
		if (isset($this->reasons[$entered_reason])) {
			return $this->reasons[$entered_reason];
		} else {
			return $entered_reason;
		}
	}
	
	function left_reason() 		{ return $this->left_reason; }
	function left_reason_text($left_reason, $mponly=0) { 
		if (isset($this->reasons[$left_reason])) {
			$left_reason = $this->reasons[$left_reason];
			if (is_array($left_reason)) {
				$q = $this->db->query("SELECT MAX(left_house) AS max FROM member");
				$max = $q->field(0, 'max');
				if ((!$mponly && $max == $this->left_house) || ($mponly && $max == $this->mp_left_house)) {
					return $left_reason[0];
				} else {
					return $left_reason[1];
				}
			} else {
				return $left_reason;
			}
		} else {
			return $left_reason;
		}
	}
	
	function extra_info()		{ return $this->extra_info; }
	
	function current_member($house = 0) {
		$current = array();
		foreach (array_keys($this->houses_pretty) as $h) {
			$lh = $this->left_house($h);
			$current[$h] = ($lh['date'] == '9999-12-31');
		}
		if ($house) return $current[$house];
		return $current;
	}

	function the_users_mp()		{ return $this->the_users_mp; }

	function url($absolute = true) {
		$house = $this->house_disp;
		if ($house==1) {
			$URL = new URL('mp');
		} elseif ($house==2) {
			$URL = new URL('peer');
		} elseif ($house==3) {
			$URL = new URL('mla');
		} elseif ($house==0) {
			$URL = new URL('royal');
		}
		$member_url = make_member_url($this->full_name(true), $this->constituency(), $house);
		if ($absolute)
			return 'http://' . DOMAIN . $URL->generate('none') . $member_url;
		else
			return $URL->generate('none') . $member_url;
	}
	
	function display () {
		global $PAGE;
		
		$member = array (
			'member_id' 		=> $this->member_id(),
			'person_id'		=> $this->person_id(),
			'constituency' 		=> $this->constituency(),
			'party'			=> $this->party_text(),
			'other_parties'		=> $this->other_parties,
			'houses'		=> $this->houses(),
			'entered_house'		=> $this->entered_house(),
			'left_house'		=> $this->left_house(),
			'current_member'	=> $this->current_member(),
			'full_name'		=> $this->full_name(),
			'the_users_mp'		=> $this->the_users_mp(),
			'house_disp'		=> $this->house_disp,
		);
		
		$PAGE->display_member($member, $this->extra_info);
	}

	function previous_mps() {
		$previous_people = '';
		$entered_house = $this->entered_house(1);
		if (is_null($entered_house)) return '';
		$q = $this->db->query('SELECT DISTINCT(person_id), first_name, last_name FROM member WHERE house=1 AND constituency = "'.$this->constituency() . '" AND person_id != ' . $this->person_id() . ' AND entered_house < "' . $entered_house['date'] . '"');
		for ($r = 0; $r < $q->rows(); $r++) {
			$pid = $q->field($r, 'person_id');
			$name = $q->field($r, 'first_name') . ' ' . $q->field($r, 'last_name');
			$previous_people .= '<li><a href="' . WEBPATH . 'mp/?pid='.$pid.'">'.$name.'</a></li>';
		}
		# XXX: This is because George's enter date is before Oona's enter date...
		# Can't think of an easy fix without another pointless DB lookup
		# Guess the starting setup of this class should store more information
		if ($this->person_id() == 10218)
			$previous_people = '<li><a href="' . WEBPATH . 'mp/?pid=10341">Oona King</a></li>';
		return $previous_people;
	}

	function future_mps() {
		$future_people = '';
		$entered_house = $this->entered_house(1);
		if (is_null($entered_house)) return '';
		$q = $this->db->query('SELECT DISTINCT(person_id), first_name, last_name FROM member WHERE house=1 AND constituency = "'.$this->constituency() . '" AND person_id != ' . $this->person_id() . ' AND entered_house > "' . $entered_house['date'] . '"');
		if ($this->person_id() == 10218) return;
		for ($r = 0; $r < $q->rows(); $r++) {
			$pid = $q->field($r, 'person_id');
			$name = $q->field($r, 'first_name') . ' ' . $q->field($r, 'last_name');
			$future_people .= '<li><a href="' . WEBPATH . 'mp/?pid='.$pid.'">'.$name.'</a></li>';
		}
		return $future_people;
	}

}

# from http://news.bbc.co.uk/nol/shared/bsp/hi/vote2004/css/styles.css
global $party_colours;
$party_colours = array(
    "Con" => "#333399",
    "DU" => "#cc6666",
    "Ind" => "#eeeeee",
    "Ind Con" => "#ddddee",
    "Ind Lab" => "#eedddd",
    "Ind UU" => "#ccddee",
    "Lab" => "#cc0000",
    "Lab/Co-op" => "#cc0000",
    "LDem" => "#f1cc0a", #"#ff9900", 
    "PC" => "#33CC33",
    "SDLP" => "#8D9033",
    "SF" => "#2B7255",
    "SNP" => "#FFCC00",
    "UKU" => "#99CCFF",
    "UU" => "#003677",

    "Speaker" => "#999999",
    "Deputy Speaker" => "#999999",
    "CWM" => "#999999",
    "DCWM" => "#999999",
    "SPK" => "#999999",
);

function party_to_colour($party) {
    global $party_colours;
    if (isset($party_colours[$party])) {
        return $party_colours[$party];
    } else {
        return "#eeeeee";
    }
}

?>
