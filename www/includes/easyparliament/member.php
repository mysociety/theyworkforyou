<?php

include_once INCLUDESPATH."postcode.inc";
include_once INCLUDESPATH."easyparliament/glossary.php";

class MEMBER {

    public $member_id;
    public $person_id;
    public $first_name;
    public $title;
    public $last_name;
    public $constituency;
    public $party;
    public $other_parties;
    public $other_constituencies;
    public $houses = array();
    public $entered_house = array();
    public $left_house = array();
    public $extra_info = array();
    // Is this MP THEUSERS's MP?
    public $the_users_mp = false;
    public $house_disp = 0; # Which house we should display this person in

    // Mapping member table 'house' numbers to text.
    public $houses_pretty = array(
        0 => 'Royal Family',
        1 => 'House of Commons',
        2 => 'House of Lords',
        3 => 'Northern Ireland Assembly',
        4 => 'Scottish Parliament',
    );

    // Mapping member table reasons to text.
    public $reasons = array(
        'became_peer'		=> 'Became peer',
        'by_election'		=> 'Byelection',
        'changed_party'		=> 'Changed party',
        'changed_name' 		=> 'Changed name',
        'declared_void'		=> 'Declared void',
        'died'			=> 'Died',
        'disqualified'		=> 'Disqualified',
        'general_election' 	=> 'General election',
        'general_election_standing' 	=> array('General election (standing again)', 'General election (stood again)'),
        'general_election_not_standing' 	=> 'did not stand for re-election',
        'reinstated'		=> 'Reinstated',
        'resigned'		=> 'Resigned',
        'still_in_office'	=> 'Still in office',
        'dissolution'		=> 'Dissolved for election',
        'regional_election'	=> 'Election', # Scottish Parliament
        'replaced_in_region'	=> 'Appointed, regional replacement',

    );

    public function MEMBER($args) {
        // $args is a hash like one of:
        // member_id 		=> 237
        // person_id 		=> 345
        // constituency 	=> 'Braintree'
        // postcode			=> 'e9 6dw'

        // If just a constituency we currently just get the current member for
        // that constituency.

        global $PAGE, $this_page;

        $house = isset($args['house']) ? $args['house'] : null;

        $this->db = new ParlDB;
        $person_id = '';
        if (isset($args['member_id']) && is_numeric($args['member_id'])) {
            $person_id = $this->member_id_to_person_id($args['member_id']);
        } elseif (isset($args['name'])) {
            $con = isset($args['constituency']) ? $args['constituency'] : '';
            $person_id = $this->name_to_person_id($args['name'], $con);
        } elseif (isset($args['constituency'])) {
            $still_in_office = isset($args['still_in_office']) ? $args['still_in_office'] : false;
            $person_id = $this->constituency_to_person_id($args['constituency'], $house, $still_in_office);
        } elseif (isset($args['postcode'])) {
            $person_id = $this->postcode_to_person_id($args['postcode'], $house);
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
            first_name, last_name, constituency, party, lastupdate,
            entered_house, left_house, entered_reason, left_reason, person_id
            FROM member
            WHERE person_id = :person_id
            ORDER BY left_house DESC, house", array(
                ':person_id' => $person_id
            ));

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

            if (!isset($this->entered_house[$house]) || $entered_house < $this->entered_house[$house]['date']) {
                $this->entered_house[$house] = array(
                    'date' => $entered_house,
                    'date_pretty' => $this->entered_house_text($entered_house),
                    'reason' => $this->entered_reason_text($entered_reason),
                );
            }

            if (!isset($this->left_house[$house])) {
                $this->left_house[$house] = array(
                    'date' => $left_house,
                    'date_pretty' => $this->left_house_text($left_house),
                    'reason' => $this->left_reason_text($left_reason, $left_house, $house),
                    'constituency' => $const,
                    'party' => $this->party_text($party)
                );
            }

            if ( $house==HOUSE_TYPE_ROYAL 					# The Monarch
                || (!$this->house_disp && $house==HOUSE_TYPE_SCOTLAND)	# MSPs and
                || (!$this->house_disp && $house==HOUSE_TYPE_NI)	# MLAs have lowest priority
                || ($this->house_disp!=HOUSE_TYPE_LORDS && $house==HOUSE_TYPE_LORDS)	# Lords have highest priority
                || (!$this->house_disp && $house==HOUSE_TYPE_COMMONS) # MPs
            ) {
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

            if ($const != $this->constituency) {
                $this->other_constituencies[$const] = true;
            }
        }

        // Loads extra info from DB - you now have to call this from outside
            // when you need it, as some uses of MEMBER are lightweight (e.g.
            // in searchengine.php)
        // $this->load_extra_info();

        $this->set_users_mp();
    }

    public function member_id_to_person_id($member_id) {
        global $PAGE;
        $q = $this->db->query("SELECT person_id FROM member
                    WHERE member_id = :member_id",
            array(':member_id' => $member_id)
        );
        if ($q->rows > 0) {
            return $q->field(0, 'person_id');
        } else {
            throw new MySociety\TheyWorkForYou\MemberException('Sorry, there is no member with a member ID of "' . _htmlentities($member_id) . '".');
        }
    }

    public function postcode_to_person_id($postcode, $house=null) {
        twfy_debug ('MP', "postcode_to_person_id converting postcode to person");
        $constituency = strtolower(postcode_to_constituency($postcode));
        return $this->constituency_to_person_id($constituency, $house);
    }

    public function constituency_to_person_id($constituency, $house=null, $still_in_office=false) {
        global $PAGE;
        if ($constituency == '') {
            throw new MySociety\TheyWorkForYou\MemberException('Sorry, no constituency was found.');
        }

        if ($constituency == 'Orkney ') {
            $constituency = 'Orkney & Shetland';
        }

        $normalised = normalise_constituency_name($constituency);
        if ($normalised) $constituency = $normalised;

            $params = array();

            $left = "left_reason = 'still_in_office'";
            if (DISSOLUTION_DATE && !$still_in_office) {
                $left = "($left OR left_house = '" . DISSOLUTION_DATE . "')";
            }
            $query = "SELECT person_id FROM member
                    WHERE constituency = :constituency
                    AND $left";

            $params[':constituency'] = $constituency;

            if ($house) {
                $query .= ' AND house = :house';
                $params[':house'] = $house;
            }

            $q = $this->db->query($query, $params);

        if ($q->rows > 0) {
            return $q->field(0, 'person_id');
        } else {
                throw new MySociety\TheyWorkForYou\MemberException('Sorry, there is no current member for the "' . _htmlentities(ucwords($constituency)) . '" constituency.');
        }
    }

    public function name_to_person_id($name, $const='') {
        global $PAGE, $this_page;
        if ($name == '') {
            throw new MySociety\TheyWorkForYou\MemberException('Sorry, no name was found.');
        }
        # Matthew made this change, but I don't know why.  It broke
        # Iain Duncan Smith, so I've put it back.  FAI 2005-03-14
        #		$success = preg_match('#^(.*? .*?) (.*?)$#', $name, $m);
        $params = array();
        $q = "SELECT person_id,constituency,max(left_house) AS left_house FROM member WHERE ";
        if ($this_page=='peer') {
            $success = preg_match('#^(.*?) (.*?) of (.*?)$#', $name, $m);
            if (!$success)
                $success = preg_match('#^(.*?)() of (.*?)$#', $name, $m);
            if (!$success)
                $success = preg_match('#^(.*?) (.*?)()$#', $name, $m);
            if (!$success) {
                throw new MySociety\TheyWorkForYou\MemberException('Sorry, that name was not recognised.');
            }
            $params[':title'] = $m[1];
            $params[':last_name'] = $m[2];
            $params[':house_type_lords'] = HOUSE_TYPE_LORDS;
            $const = $m[3];
            $q .= "house = :house_type_lords AND title = :title AND last_name = :last_name";
        } elseif ($this_page=='msp') {
            $success = preg_match('#^(.*?) (.*?) (.*?)$#', $name, $m);
            if (!$success)
                $success = preg_match('#^(.*?)() (.*)$#', $name, $m);
            if (!$success) {
                throw new MySociety\TheyWorkForYou\MemberException('Sorry, that name was not recognised.');
                return false;
            }
            $params[':first_name'] = $m[1];
            $params[':last_name'] = $m[3];
            $params[':first_and_middle_names'] = $m[1] . ' ' . $m[2];
            $params[':middle_and_last_names'] = $m[2] . ' ' . $m[3];
            $params[':house_type_scotland'] = HOUSE_TYPE_SCOTLAND;
            $q .= "house = :house_type_scotland AND (";
            $q .= "(first_name=:first_and_middle_names AND last_name=:last_name)";
            $q .= " or (first_name=:first_name AND last_name=:middle_and_last_names) )";
        } elseif ($this_page=='mla') {
            $success = preg_match('#^(.*?) (.*?) (.*?)$#', $name, $m);
            if (!$success)
                $success = preg_match('#^(.*?)() (.*)$#', $name, $m);
            if (!$success) {
                throw new MySociety\TheyWorkForYou\MemberException('Sorry, that name was not recognised.');
                return false;
            }
            $params[':first_name'] = $m[1];
            $params[':middle_name'] = $m[2];
            $params[':last_name'] = $m[3];
            $params[':first_and_middle_names'] = $m[1] . ' ' . $m[2];
            $params[':middle_and_last_names'] = $m[2] . ' ' . $m[3];
            $params[':house_type_ni'] = HOUSE_TYPE_NI;
            $q .= "house = :house_type_ni AND (
    (first_name=:first_and_middle_names AND last_name=:last_name)
    or (first_name=:first_name AND last_name=:middle_and_last_names)
    or (title=:first_name AND first_name=:middle_name AND last_name=:last_name)
)";
        } elseif (strstr($this_page, 'mp')) {
            $success = preg_match('#^(.*?) (.*?) (.*?)$#', $name, $m);
            if (!$success)
                $success = preg_match('#^(.*?)() (.*)$#', $name, $m);
            if (!$success) {
                throw new MySociety\TheyWorkForYou\MemberException('Sorry, that name was not recognised.');
                return false;
            }

            $params[':first_name'] = $m[1];
            $params[':last_name'] = $m[3];
            $params[':first_and_middle_names'] = $m[1] . ' ' . $m[2];
            $params[':middle_and_last_names'] = $m[2] . ' ' . $m[3];
            $params[':house_type_commons'] = HOUSE_TYPE_COMMONS;

            $q .= "house = :house_type_commons AND ((first_name=:first_and_middle_names AND last_name=:last_name) OR ".
            "(first_name=:first_name AND last_name=:middle_and_last_names))";
        } elseif ($this_page == 'royal') {
            $params[':house_type_royal'] = HOUSE_TYPE_ROYAL;
            $q .= ' house = :house_type_royal';
        }

        if ($const || $this_page=='peer') {
            $params[':constituency'] = $const;
            $q .= ' AND constituency=:constituency';
        }
        $q .= ' GROUP BY person_id, constituency ORDER BY left_house DESC';
        $q = $this->db->query($q, $params);
        if ($q->rows > 1) {
            # Hacky as a very hacky thing that's graduated in hacking from the University of Hacksville
            # Anyone who wants to do it properly, feel free

            $person_ids = array(); $consts = array();
            for ($i=0; $i<$q->rows(); ++$i) {
                $pid = $q->field($i, 'person_id');

                # XXX A hack within a hack
                # There are two Mark Durkan MLAs - the current one is the
                # nephew of the old one. To stop this page constantly asking
                # you to pick between them, shortcircuit the current one
                if ($pid == 25143) return $pid;

                if (!in_array($pid, $person_ids)) {
                    $person_ids[] = $pid;
                    $consts[] = $q->field($i, 'constituency');
                }
            }
            if (sizeof($person_ids) == 1) return $person_ids[0];
            $this->constituency = $consts;
            return $person_ids;
        } elseif ($q->rows > 0) {
            if ($q->field(0, 'left_house') != '9999-12-31') {
                $qq = $this->db->query('SELECT MAX(left_house) AS left_house FROM member
                    WHERE person_id=' . $q->field(0, 'person_id')
                );
            }
            return $q->field(0, 'person_id');
        } elseif ($const && $this_page!='peer') {
            return $this->name_to_person_id($name);
        } else {
            throw new MySociety\TheyWorkForYou\MemberException('Sorry, there is no current member with that name.');
            return false;
        }
    }

    public function set_users_mp() {
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
    # DISPLAY is whether it's to be displayed on MP page.
    public function load_extra_info($display = false) {
        $memcache = new MySociety\TheyWorkForYou\Memcache;
        $memcache_key = 'extra_info:' . $this->person_id . ($display ? '' : ':plain');
        $this->extra_info = $memcache->get($memcache_key);
        if (!DEVSITE && $this->extra_info) {
            return;
        }
        $this->extra_info = array();

        $q = $this->db->query('SELECT * FROM moffice WHERE person=:person_id ORDER BY from_date DESC',
                              array(':person_id' => $this->person_id));
        for ($row=0; $row<$q->rows(); $row++) {
            $this->extra_info['office'][] = $q->row($row);
        }

        // Info specific to member id (e.g. attendance during that period of office)
        $q = $this->db->query("SELECT data_key, data_value
                        FROM 	memberinfo
                        WHERE	member_id = :member_id",
            array(':member_id' => $this->member_id));
        for ($row = 0; $row < $q->rows(); $row++) {
            $this->extra_info[$q->field($row, 'data_key')] = $q->field($row, 'data_value');
            #		if ($q->field($row, 'joint') > 1)
            #			$this->extra_info[$q->field($row, 'data_key').'_joint'] = true;
        }

        // Info specific to person id (e.g. their permanent page on the Guardian website)
        $q = $this->db->query("SELECT data_key, data_value
                        FROM 	personinfo
                        WHERE	person_id = :person_id",
            array(':person_id' => $this->person_id));
        for ($row = 0; $row < $q->rows(); $row++) {
            $this->extra_info[$q->field($row, 'data_key')] = $q->field($row, 'data_value');
        #	    if ($q->field($row, 'count') > 1)
        #	    	$this->extra_info[$q->field($row, 'data_key').'_joint'] = true;
        }

        // Info specific to constituency (e.g. election results page on Guardian website)
        if ($this->house(HOUSE_TYPE_COMMONS)) {

            $q = $this->db->query("SELECT data_key, data_value FROM consinfo
            WHERE constituency = :constituency",
                array(':constituency' => $this->constituency));
            for ($row = 0; $row < $q->rows(); $row++) {
                $this->extra_info[$q->field($row, 'data_key')] = $q->field($row, 'data_value');
            }
        }

        if (array_key_exists('public_whip_rebellions', $this->extra_info)) {
            $rebellions = $this->extra_info['public_whip_rebellions'];
            $rebel_desc = "<unknown>";
            if ($rebellions == 0)
                $rebel_desc = "never";
            elseif ($rebellions <= 1)
                $rebel_desc = "hardly ever";
            elseif ($rebellions <= 3)
                $rebel_desc = "occasionally";
            elseif ($rebellions <= 5)
                $rebel_desc = "sometimes";
            elseif ($rebellions > 5)
                $rebel_desc = "quite often";
            $this->extra_info['public_whip_rebel_description'] = $rebel_desc;
        }

        if (isset($this->extra_info['public_whip_attendrank'])) {
            $prefix = ($this->house(HOUSE_TYPE_LORDS) ? 'L' : '');
            $this->extra_info[$prefix.'public_whip_division_attendance_rank'] = $this->extra_info['public_whip_attendrank'];
            $this->extra_info[$prefix.'public_whip_division_attendance_rank_outof'] = $this->extra_info['public_whip_attendrank_outof'];
            $this->extra_info[$prefix.'public_whip_division_attendance_quintile'] = floor($this->extra_info['public_whip_attendrank'] / ($this->extra_info['public_whip_attendrank_outof']+1) * 5);
        }
        if ($this->house(HOUSE_TYPE_LORDS) && isset($this->extra_info['public_whip_division_attendance'])) {
            $this->extra_info['Lpublic_whip_division_attendance'] = $this->extra_info['public_whip_division_attendance'];
            unset($this->extra_info['public_whip_division_attendance']);
        }

        if ($display && array_key_exists('register_member_interests_html', $this->extra_info) && ($this->extra_info['register_member_interests_html'] != '')) {
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
            where bill_id = bills.id and person_id = ' . $this->person_id()
             . ' group by bill_id order by session desc');
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

        $memcache->set($memcache_key, $this->extra_info);
    }

    // Functions for accessing things about this Member.

    public function member_id() { return $this->member_id; }
    public function person_id() { return $this->person_id; }
    public function first_name() { return $this->first_name; }
    public function last_name() { return $this->last_name; }
    public function full_name($no_mp_title = false) {
        $title = $this->title;
        if ($no_mp_title && ($this->house_disp==HOUSE_TYPE_COMMONS || $this->house_disp==HOUSE_TYPE_NI || $this->house_disp==HOUSE_TYPE_SCOTLAND))
            $title = '';
        return member_full_name($this->house_disp, $title, $this->first_name, $this->last_name, $this->constituency);
    }
    public function houses() {
        return $this->houses;
    }
    public function house($house) {
        return in_array($house, $this->houses) ? true : false;
    }
    public function house_text($house) {
        return $this->houses_pretty[$house];
    }
    public function constituency() { return $this->constituency; }
    public function party() { return $this->party; }
    public function party_text($party = null) {
        global $parties;
        if (!$party)
            $party = $this->party;
        if (isset($parties[$party])) {
            return $parties[$party];
        } else {
            return $party;
        }
    }

    public function entered_house($house = 0) {
        if ($house) return array_key_exists($house, $this->entered_house) ? $this->entered_house[$house] : null;
        return $this->entered_house;
    }
    public function entered_house_text($entered_house) {
        if (!$entered_house) return '';
        list($year, $month, $day) = explode('-', $entered_house);
        if ($month==1 && $day==1 && $this->house(HOUSE_TYPE_LORDS)) {
            return $year;
        } elseif ($month==0 && $day==0) {
            return $year;
        } elseif (checkdate($month, $day, $year) && $year != '9999') {
            return format_date($entered_house, LONGDATEFORMAT);
        } else {
            return "n/a";
        }
    }

    public function left_house($house = null) {
        if (!is_null($house))
            return array_key_exists($house, $this->left_house) ? $this->left_house[$house] : null;
        return $this->left_house;
    }

    public function left_house_text($left_house) {
        if (!$left_house) return '';
        list($year, $month, $day) = explode('-', $left_house);
        if (checkdate($month, $day, $year) && $year != '9999') {
            return format_date($left_house, LONGDATEFORMAT);
        } elseif ($month==0 && $day==0) {
            return $year;
        } else {
            return "n/a";
        }
    }

    public function entered_reason() { return $this->entered_reason; }
    public function entered_reason_text($entered_reason) {
        if (isset($this->reasons[$entered_reason])) {
            return $this->reasons[$entered_reason];
        } else {
            return $entered_reason;
        }
    }

    public function left_reason() { return $this->left_reason; }
    public function left_reason_text($left_reason, $left_house, $house) {
        if (isset($this->reasons[$left_reason])) {
            $left_reason = $this->reasons[$left_reason];
            if (is_array($left_reason)) {
                $q = $this->db->query("SELECT MAX(left_house) AS max FROM member WHERE house=$house");
                $max = $q->field(0, 'max');
                if ($max == $left_house) {
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

    public function extra_info() { return $this->extra_info; }

    public function current_member($house = 0) {
        $current = array();
        foreach (array_keys($this->houses_pretty) as $h) {
            $lh = $this->left_house($h);
            $current[$h] = ($lh['date'] == '9999-12-31');
        }
        if ($house) return $current[$house];
        return $current;
    }

    public function the_users_mp() { return $this->the_users_mp; }

    public function url($absolute = true) {
        $house = $this->house_disp;
        if ($house == HOUSE_TYPE_COMMONS) {
            $URL = new URL('mp');
        } elseif ($house == HOUSE_TYPE_LORDS) {
            $URL = new URL('peer');
        } elseif ($house == HOUSE_TYPE_NI) {
            $URL = new URL('mla');
        } elseif ($house == HOUSE_TYPE_SCOTLAND) {
            $URL = new URL('msp');
        } elseif ($house == HOUSE_TYPE_ROYAL) {
            $URL = new URL('royal');
        }
        $member_url = make_member_url($this->full_name(true), $this->constituency(), $house, $this->person_id());
        if ($absolute)
            return 'http://' . DOMAIN . $URL->generate('none') . $member_url;
        else
            return $URL->generate('none') . $member_url;
    }

    public function previous_mps() {
        $previous_people = '';
        $entered_house = $this->entered_house(HOUSE_TYPE_COMMONS);
        if (is_null($entered_house)) return '';
        $q = $this->db->query('SELECT DISTINCT(person_id), first_name, last_name FROM member WHERE house=' . HOUSE_TYPE_COMMONS . ' AND constituency = "'.$this->constituency() . '" AND person_id != ' . $this->person_id() . ' AND entered_house < "' . $entered_house['date'] . '" ORDER BY entered_house DESC');
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

    public function previous_mps_array() {
        $previous_people = array();
        $entered_house = $this->entered_house(HOUSE_TYPE_COMMONS);
        if (is_null($entered_house)) return '';
        $q = $this->db->query('SELECT DISTINCT(person_id), first_name, last_name FROM member WHERE house=' . HOUSE_TYPE_COMMONS . ' AND constituency = "'.$this->constituency() . '" AND person_id != ' . $this->person_id() . ' AND entered_house < "' . $entered_house['date'] . '" ORDER BY entered_house DESC');
        for ($r = 0; $r < $q->rows(); $r++) {
            $pid = $q->field($r, 'person_id');
            $name = $q->field($r, 'first_name') . ' ' . $q->field($r, 'last_name');
            $previous_people[] = array(
                'href' => WEBPATH . 'mp/?pid='.$pid,
                'text' => $name
            );
        }
        # XXX: This is because George's enter date is before Oona's enter date...
        # Can't think of an easy fix without another pointless DB lookup
        # Guess the starting setup of this class should store more information
        if ($this->person_id() == 10218)
            $previous_people[] = array(
                'href' => WEBPATH . 'mp/?pid='.$pid,
                'text' => $name
            );
        return $previous_people;
    }

    public function future_mps() {
        $future_people = '';
        $entered_house = $this->entered_house(HOUSE_TYPE_COMMONS);
        if (is_null($entered_house)) return '';
        $q = $this->db->query('SELECT DISTINCT(person_id), first_name, last_name FROM member WHERE house=' . HOUSE_TYPE_COMMONS . ' AND constituency = "'.$this->constituency() . '" AND person_id != ' . $this->person_id() . ' AND entered_house > "' . $entered_house['date'] . '" ORDER BY entered_house');
        if ($this->person_id() == 10218) return;
        for ($r = 0; $r < $q->rows(); $r++) {
            $pid = $q->field($r, 'person_id');
            $name = $q->field($r, 'first_name') . ' ' . $q->field($r, 'last_name');
            $future_people .= '<li><a href="' . WEBPATH . 'mp/?pid='.$pid.'">'.$name.'</a></li>';
        }
        return $future_people;
    }

    public function future_mps_array() {
        $future_people = array();
        $entered_house = $this->entered_house(HOUSE_TYPE_COMMONS);
        if (is_null($entered_house)) return '';
        $q = $this->db->query('SELECT DISTINCT(person_id), first_name, last_name FROM member WHERE house=' . HOUSE_TYPE_COMMONS . ' AND constituency = "'.$this->constituency() . '" AND person_id != ' . $this->person_id() . ' AND entered_house > "' . $entered_house['date'] . '" ORDER BY entered_house');
        if ($this->person_id() == 10218) return;
        for ($r = 0; $r < $q->rows(); $r++) {
            $pid = $q->field($r, 'person_id');
            $name = $q->field($r, 'first_name') . ' ' . $q->field($r, 'last_name');
            $future_people[] = array(
                'href' => WEBPATH . 'mp/?pid='.$pid,
                'text' => $name
            );
        }
        return $future_people;
    }

    public function current_member_anywhere() {
        $is_current = false;
        $current_memberships = $this->current_member();
        foreach ($current_memberships as $current_memberships) {
            if ($current_memberships === true) {
                $is_current = true;
            }
        }

        return $is_current;
    }
}
