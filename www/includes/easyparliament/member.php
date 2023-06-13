<?php

include_once INCLUDESPATH."easyparliament/glossary.php";

class MEMBER {

    public $valid = false;
    public $member_id;
    public $person_id;
    public $title;
    public $given_name;
    public $family_name;
    public $lordofname;
    public $constituency;
    public $party;
    public $other_parties = array();
    public $other_constituencies;
    public $houses = array();
    public $entered_house = array();
    public $left_house = array();
    public $extra_info = array();
    // Is this MP THEUSERS's MP?
    public $the_users_mp = false;
    public $house_disp = 0; # Which house we should display this person in

    // Mapping member table 'house' numbers to text.
    private function houses_pretty() {
        return array(
            0 => gettext('Royal Family'),
            1 => gettext('House of Commons'),
            2 => gettext('House of Lords'),
            3 => gettext('Northern Ireland Assembly'),
            4 => gettext('Scottish Parliament'),
            5 => gettext('Senedd'),
            6 => gettext('London Assembly'),
        );
    }

    // Mapping member table reasons to text.
    private function reasons() {
        return array(
            'became_peer'		=> gettext('Became peer'),
            'by_election'		=> gettext('Byelection'),
            'changed_party'		=> gettext('Changed party'),
            'changed_name' 		=> gettext('Changed name'),
            'declared_void'		=> gettext('Declared void'),
            'died'			=> gettext('Died'),
            'disqualified'		=> gettext('Disqualified'),
            'general_election' 	=> gettext('General election'),
            'general_election_standing' 	=> array(gettext('General election (standing again)'), gettext('General election (stood again)')),
            'general_election_not_standing' 	=> gettext('did not stand for re-election'),
            'reinstated'		=> gettext('Reinstated'),
            'resigned'		=> gettext('Resigned'),
            'recall_petition'   => gettext('Removed from office by a recall petition'),
            'still_in_office'	=> gettext('Still in office'),
            'dissolution'		=> gettext('Dissolved for election'),
            'regional_election'	=> gettext('Election'), # Scottish Parliament
            'replaced_in_region'	=> gettext('Appointed, regional replacement'),
        );
    }

    private $db;

    /*
     * Is given house higher priority than current?
     *
     * Determine if the given house is a higher priority than the currently displayed one.
     *
     * @param int $house The number of the house to evaluate.
     *
     * @return boolean
     */

    private function isHigherPriorityHouse(int $house)
    {
        # The monarch always takes priority, so if the house is royal always say "yes"
        if ($house == HOUSE_TYPE_ROYAL) {
            return true;
        }

        # If the current house is *not* Lords, and the house to check is, Lords is next
        if ($this->house_disp != HOUSE_TYPE_LORDS && $house == HOUSE_TYPE_LORDS) {
            return true;
        }

        # All the following only happen if the house to display isn't yet set.
        # TODO: This relies on interpreting the default value of 0 as a false, which may be error-prone.
        if (! (bool) $this->house_disp) {
            if ($house == HOUSE_TYPE_LONDON_ASSEMBLY # London Assembly
                || $house == HOUSE_TYPE_SCOTLAND     # MSPs and
                || $house == HOUSE_TYPE_WALES        # MSs and
                || $house == HOUSE_TYPE_NI           # MLAs have lowest priority
                || $house == HOUSE_TYPE_COMMONS      # MPs
            ) {
                return true;
            }
        }

        return false;
    }

    public function __construct($args) {
        // $args is a hash like one of:
        // member_id 		=> 237
        // person_id 		=> 345
        // constituency 	=> 'Braintree'
        // postcode			=> 'e9 6dw'

        // If just a constituency we currently just get the current member for
        // that constituency.

        global $this_page;

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
            $q = $this->db->query("SELECT gid_to FROM gidredirect
                    WHERE gid_from = :gid_from",
                array(':gid_from' => "uk.org.publicwhip/person/$person_id")
            )->first();
            if ($q) {
                $person_id = str_replace('uk.org.publicwhip/person/', '', $q['gid_to']);
            }
        }

        if (!$person_id) {
            return;
        }

        // Find the memberships of this person, in reverse chronological order (latest first)
        $q = $this->db->query("SELECT member_id, house, title,
            given_name, family_name, lordofname, constituency, party, lastupdate,
            entered_house, left_house, entered_reason, left_reason, member.person_id
            FROM member, person_names pn
            WHERE member.person_id = :person_id
                AND member.person_id = pn.person_id AND pn.type = 'name' AND pn.start_date <= left_house AND left_house <= pn.end_date
            ORDER BY left_house DESC, house", array(
                ':person_id' => $person_id
            ));

        if (!$q->rows() > 0) {
            return;
        }

        $this->valid = true;

        $this->house_disp = 0;
        $last_party = null;
        foreach ($q as $row) {
            $house = $row['house'];
            if (!in_array($house, $this->houses)) {
                $this->houses[] = $house;
            }
            $const = gettext($row['constituency']);
            $party = $row['party'];
            $entered_house = $row['entered_house'];
            $left_house = $row['left_house'];
            $entered_reason = $row['entered_reason'];
            $left_reason = $row['left_reason'];

            if (!isset($this->entered_house[$house]) || $entered_house < $this->entered_house[$house]['date']) {
                $this->entered_house[$house] = array(
                    'date' => $entered_house,
                    'date_pretty' => $this->entered_house_text($entered_house),
                    'reason' => $this->entered_reason_text($entered_reason),
                    'house' => $house
                );
            }

            if (!isset($this->left_house[$house])) {
                $this->left_house[$house] = array(
                    'date' => $left_house,
                    'date_pretty' => $this->left_house_text($left_house),
                    'reason' => $this->left_reason_text($left_reason, $left_house, $house),
                    'constituency' => $const,
                    'party' => $this->party_text($party),
                    'house' => $house
                );
            }

            if ($this->isHigherPriorityHouse($house)) {
                $this->house_disp = $house;
                $this->constituency = $const;
                $this->party = $party;

                $this->member_id = $row['member_id'];
                $this->title = $row['title'];
                $this->given_name = $row['given_name'];
                $this->family_name = $row['family_name'];
                $this->lordofname = $row['lordofname'];
                $this->person_id = $row['person_id'];
            }

            if (($last_party && $party && $party != $last_party) || $left_reason == 'changed_party') {
                $this->other_parties[] = array(
                    'from' => $this->party_text($party),
                    'date' => $left_house,
                );
            }
            $last_party = $party;

            if ($const != $this->constituency) {
                $this->other_constituencies[$const] = true;
            }
        }
        $this->other_parties = array_reverse($this->other_parties);

        // Loads extra info from DB - you now have to call this from outside
            // when you need it, as some uses of MEMBER are lightweight (e.g.
            // in searchengine.php)
        // $this->load_extra_info();

        $this->set_users_mp();
    }

    public function member_id_to_person_id($member_id) {
        $q = $this->db->query("SELECT person_id FROM member
                    WHERE member_id = :member_id",
            array(':member_id' => $member_id)
        )->first();
        if (!$q) {
            $q = $this->db->query("SELECT person_id FROM gidredirect, member
                    WHERE gid_from = :gid_from AND
                        CONCAT('uk.org.publicwhip/member/', member_id) = gid_to",
                array(':gid_from' => "uk.org.publicwhip/member/$member_id")
            )->first();
        }
        if ($q) {
            return $q['person_id'];
        } else {
            throw new MySociety\TheyWorkForYou\MemberException('Sorry, there is no member with a member ID of "' . _htmlentities($member_id) . '".');
        }
    }

    public function postcode_to_person_id($postcode, $house=null) {
        twfy_debug ('MP', "postcode_to_person_id converting postcode to person");
        $constituency = strtolower(MySociety\TheyWorkForYou\Utility\Postcode::postcodeToConstituency($postcode));
        return $this->constituency_to_person_id($constituency, $house);
    }

    public function constituency_to_person_id($constituency, $house=null) {
        if ($constituency == '') {
            throw new MySociety\TheyWorkForYou\MemberException('Sorry, no constituency was found.');
        }

        if ($constituency == 'Orkney ') {
            $constituency = 'Orkney & Shetland';
        }

        $normalised = MySociety\TheyWorkForYou\Utility\Constituencies::normaliseConstituencyName($constituency);
        if ($normalised) {
            $constituency = $normalised;
        }

        $params = array();

        $left = "left_reason = 'still_in_office'";
        if ($dissolution = MySociety\TheyWorkForYou\Dissolution::db()) {
            $left = "($left OR $dissolution[query])";
            $params = $dissolution['params'];
        }
        $query = "SELECT person_id FROM member
                WHERE constituency = :constituency
                AND $left";

        $params[':constituency'] = $constituency;

        if ($house) {
            $query .= ' AND house = :house';
            $params[':house'] = $house;
        }

        $q = $this->db->query($query, $params)->first();

        if ($q) {
            return $q['person_id'];
        } else {
            throw new MySociety\TheyWorkForYou\MemberException('Sorry, there is no current member for the "' . _htmlentities(ucwords($constituency)) . '" constituency.');
        }
    }

    public function name_to_person_id($name, $const='') {
        global $this_page;
        if ($name == '') {
            throw new MySociety\TheyWorkForYou\MemberException('Sorry, no name was found.');
        }

        $params = array();
        $q = "SELECT person_id FROM person_names WHERE type = 'name' ";
        if ($this_page == 'peer') {
            $success = preg_match('#^(.*?) (.*?) of (.*?)$#', $name, $m);
            if (!$success) {
                $success = preg_match('#^(.*?)() of (.*?)$#', $name, $m);
            }
            if (!$success) {
                $success = preg_match('#^(.*?) (.*?)()$#', $name, $m);
            }
            if (!$success) {
                throw new MySociety\TheyWorkForYou\MemberException('Sorry, that name was not recognised.');
            }
            $params[':title'] = $m[1];
            $params[':family_name'] = $m[2];
            $params[':lordofname'] = $m[3];
            $q .= "AND title = :title AND family_name = :family_name AND lordofname = :lordofname";
        } elseif ($this_page == 'msp' || $this_page == 'mla' || strstr($this_page, 'mp')) {
            $success = preg_match('#^(.*?) (.*?) (.*?)$#', $name, $m);
            if (!$success) {
                $success = preg_match('#^(.*?)() (.*)$#', $name, $m);
            }
            if (!$success) {
                throw new MySociety\TheyWorkForYou\MemberException('Sorry, that name was not recognised.');
            }
            $params[':given_name'] = $m[1];
            $params[':middle_name'] = $m[2];
            $params[':family_name'] = $m[3];
            $params[':first_and_middle_names'] = $m[1] . ' ' . $m[2];
            $params[':middle_and_last_names'] = $m[2] . ' ' . $m[3];
            # Note this works only because MySQL ignores trailing whitespace
            $q .= "AND (
                (given_name=:first_and_middle_names AND family_name=:family_name)
                OR (given_name=:given_name AND family_name=:middle_and_last_names)
                OR (title=:given_name AND given_name=:middle_name AND family_name=:family_name)
            )";
        } elseif ($this_page == 'royal') {
            twfy_debug ('MP', $name);
            if (stripos($name, 'elizabeth') !== false) {
                $q .= "AND person_id=13935";
            } elseif (stripos($name, 'charles') !== false) {
                $q .= "AND person_id=26065";
            }
        }

        $q = $this->db->query($q, $params);
        if (!$q->rows()) {
            throw new MySociety\TheyWorkForYou\MemberException('Sorry, we could not find anyone with that name.');
        } elseif ($q->rows() == 1) {
            return $q->first()['person_id'];
        }

        # More than one person ID matching the given name
        $person_ids = array();
        foreach ($q as $row) {
            $pid = $row['person_id'];
            $person_ids[$pid] = 1;
        }
        $pids = array_keys($person_ids);

        $params = array();
        if ($this_page == 'peer') {
            $params[':house'] = HOUSE_TYPE_LORDS;
        } elseif ($this_page == 'msp') {
            $params[':house'] = HOUSE_TYPE_SCOTLAND;
        } elseif ($this_page == 'ms') {
            $params[':house'] = HOUSE_TYPE_WALES;
        } elseif ($this_page == 'mla') {
            $params[':house'] = HOUSE_TYPE_NI;
        } elseif ($this_page == 'royal') {
            $params[':house'] = HOUSE_TYPE_ROYAL;
        } elseif ($this_page == 'london-assembly-member') {
            $params[':house'] = HOUSE_TYPE_LONDON_ASSEMBLY;
        } else {
            $params[':house'] = HOUSE_TYPE_COMMONS;
        }

        $pids_str = join(',', $pids);
        $q = "SELECT person_id, min(constituency) AS constituency
            FROM member WHERE person_id IN ($pids_str) AND house = :house";
        if ($const) {
            $params[':constituency'] = $const;
            $q .= ' AND constituency=:constituency';
        }
        $q .= ' GROUP BY person_id';

        $q = $this->db->query($q, $params);
        if ($q->rows() > 1) {
            $person_ids = array();
            foreach ($q as $row) {
                $person_ids[$row['person_id']] = $row['constituency'];
            }
            throw new MySociety\TheyWorkForYou\MemberMultipleException($person_ids);
        } elseif ($q->rows() > 0) {
            return $q->first()['person_id'];
        } elseif ($const) {
            return $this->name_to_person_id($name);
        } else {
            throw new MySociety\TheyWorkForYou\MemberException('Sorry, there is no current member with that name.');
        }
    }

    public function set_users_mp() {
        // Is this MP THEUSER's MP?
        global $THEUSER;
        if (is_object($THEUSER) && $THEUSER->postcode_is_set() && $this->current_member(1)) {
            $pc = $THEUSER->postcode();
            twfy_debug ('MP', "set_users_mp converting postcode to person");
            $constituency = strtolower(MySociety\TheyWorkForYou\Utility\Postcode::postcodeToConstituency($pc));
            if ($constituency == strtolower($this->constituency())) {
                $this->the_users_mp = true;
            }
        }
    }

    // Grabs extra information (e.g. external links) from the database
    # DISPLAY is whether it's to be displayed on MP page.
    public function load_extra_info($display = false, $force = false) {
        $memcache = new MySociety\TheyWorkForYou\Memcache;
        $memcache_key = 'extra_info:' . $this->person_id . ($display ? '' : ':plain');
        $this->extra_info = $memcache->get($memcache_key);
        if (!DEVSITE && !$force && $this->extra_info) {
            return;
        }
        $this->extra_info = array();

        $q = $this->db->query('SELECT * FROM moffice WHERE person=:person_id ORDER BY from_date DESC, moffice_id',
                              array(':person_id' => $this->person_id));
        $this->extra_info['office'] = $q->fetchAll();

        // Info specific to member id (e.g. attendance during that period of office)
        $q = $this->db->query("SELECT data_key, data_value
                        FROM 	memberinfo
                        WHERE	member_id = :member_id",
            array(':member_id' => $this->member_id));
        foreach ($q as $row) {
            $this->extra_info[$row['data_key']] = $row['data_value'];
            #		if ($row['joint'] > 1)
            #			$this->extra_info[$row['data_key'].'_joint'] = true;
        }

        // Info specific to person id (e.g. their permanent page on the Guardian website)
        $q = $this->db->query("SELECT data_key, data_value
                        FROM 	personinfo
                        WHERE	person_id = :person_id",
            array(':person_id' => $this->person_id));
        foreach ($q as $row) {
            $this->extra_info[$row['data_key']] = $row['data_value'];
        #	    if ($row['count'] > 1)
        #	    	$this->extra_info[$row['data_key'].'_joint'] = true;
        }

        // Info specific to constituency (e.g. election results page on Guardian website)
        if ($this->house(HOUSE_TYPE_COMMONS)) {

            $q = $this->db->query("SELECT data_key, data_value FROM consinfo
            WHERE constituency = :constituency",
                array(':constituency' => $this->constituency));
            foreach ($q as $row) {
                $this->extra_info[$row['data_key']] = $row['data_value'];
            }
        }

        if (array_key_exists('public_whip_rebellions', $this->extra_info)) {
            $rebellions = $this->extra_info['public_whip_rebellions'];
            $rebel_desc = "<unknown>";
            if ($rebellions == 0) {
                $rebel_desc = "never";
            } elseif ($rebellions <= 1) {
                $rebel_desc = "hardly ever";
            } elseif ($rebellions <= 3) {
                $rebel_desc = "occasionally";
            } elseif ($rebellions <= 5) {
                $rebel_desc = "sometimes";
            } elseif ($rebellions > 5) {
                $rebel_desc = "quite often";
            }
            $this->extra_info['public_whip_rebel_description'] = $rebel_desc;
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

        $q = $this->db->query('select count(*) as c from alerts where criteria like "%speaker:'.$this->person_id.'%" and confirmed and not deleted')->first();
        $this->extra_info['number_of_alerts'] = $q['c'];

        # Public Bill Committees
        $q = $this->db->query('select bill_id,
            min(session) AS session,
            min(title) AS title,
            sum(attending) as a, sum(chairman) as c
            from pbc_members, bills
            where bill_id = bills.id and person_id = :person_id
            group by bill_id order by session desc',
            array(':person_id' => $this->person_id()));
        $this->extra_info['pbc'] = array();
        foreach ($q as $row) {
            $bill_id = $row['bill_id'];
            $c = $this->db->query('select count(*) as c from hansard where major=6 and minor=:bill_id and htype=10', array(':bill_id' => $bill_id))->first();
            $c = $c['c'];
            $title = $row['title'];
            $attending = $row['a'];
            $chairman = $row['c'];
            $this->extra_info['pbc'][$bill_id] = array(
                'title' => $title, 'session' => $row['session'],
                'attending'=>$attending, 'chairman'=>($chairman>0), 'outof' => $c
            );
        }

        $memcache->set($memcache_key, $this->extra_info);
    }

    // Functions for accessing things about this Member.

    public function member_id() { return $this->member_id; }
    public function person_id() { return $this->person_id; }
    public function given_name() { return $this->given_name; }
    public function family_name() { return $this->family_name; }
    public function full_name($no_mp_title = false) {
        $title = $this->title;
        if ($no_mp_title && ($this->house_disp==HOUSE_TYPE_COMMONS || $this->house_disp==HOUSE_TYPE_NI || $this->house_disp==HOUSE_TYPE_SCOTLAND || $this->house_disp==HOUSE_TYPE_WALES)) {
            $title = '';
        }
        return member_full_name($this->house_disp, $title, $this->given_name, $this->family_name, $this->lordofname);
    }
    public function houses() {
        return $this->houses;
    }
    public function house($house) {
        return in_array($house, $this->houses) ? true : false;
    }
    public function house_text($house) {
        return $this->houses_pretty()[$house];
    }
    public function constituency() { return $this->constituency; }
    public function party() { return $this->party; }
    public function party_text($party = null) {
        global $parties;
        if (!$party) {
            $party = $this->party;
        }
        if (isset($parties[$party])) {
            return $parties[$party];
        } else {
            return $party;
        }
    }

    public function entered_house($house = null) {
        if ( isset($house) ) {
            if ( array_key_exists($house, $this->entered_house) ) {
                return $this->entered_house[$house];
            } else {
                return null;
            }
        }
        return $this->entered_house;
    }

    public function entered_house_text($entered_house) {
        if (!$entered_house) {
            return '';
        }
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
        if ( isset($house) ) {
            if ( array_key_exists($house, $this->left_house) ) {
                return $this->left_house[$house];
            } else {
                return null;
            }
        }
        return $this->left_house;
    }

    public function left_house_text($left_house) {
        if (!$left_house) {
            return '';
        }
        list($year, $month, $day) = explode('-', $left_house);
        if (checkdate($month, $day, $year) && $year != '9999') {
            return format_date($left_house, LONGDATEFORMAT);
        } elseif ($month==0 && $day==0) {
            # Left house date is stored as 1942-00-00 to mean "at some point in 1941"
            return $year - 1;
        } else {
            return "n/a";
        }
    }

    public function entered_reason() { return $this->entered_reason; }
    public function entered_reason_text($entered_reason) {
        if (isset($this->reasons()[$entered_reason])) {
            return $this->reasons()[$entered_reason];
        } else {
            return $entered_reason;
        }
    }

    public function left_reason() { return $this->left_reason; }
    public function left_reason_text($left_reason, $left_house, $house) {
        if (isset($this->reasons()[$left_reason])) {
            $left_reason = $this->reasons()[$left_reason];
            if (is_array($left_reason)) {
                $q = $this->db->query("SELECT MAX(left_house) AS max FROM member WHERE house=$house")->first();
                $max = $q['max'];
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
        foreach (array_keys($this->houses_pretty()) as $h) {
            $lh = $this->left_house($h);
            $current[$h] = ($lh && $lh['date'] == '9999-12-31');
        }
        if ($house) {
            return $current[$house];
        }
        return $current;
    }

    public function the_users_mp() { return $this->the_users_mp; }

    public function url($absolute = false) {
        $house = $this->house_disp;

        switch ($house) {
            case HOUSE_TYPE_LORDS:
                $URL = new \MySociety\TheyWorkForYou\Url('peer');
                break;

            case HOUSE_TYPE_NI:
                $URL = new \MySociety\TheyWorkForYou\Url('mla');
                break;

            case HOUSE_TYPE_SCOTLAND:
                $URL = new \MySociety\TheyWorkForYou\Url('msp');
                break;

            case HOUSE_TYPE_WALES:
                $URL = new \MySociety\TheyWorkForYou\Url('ms');
                break;

            case HOUSE_TYPE_LONDON_ASSEMBLY:
                $URL = new \MySociety\TheyWorkForYou\Url('london-assembly-member');
                break;

            case HOUSE_TYPE_ROYAL:
                $URL = new \MySociety\TheyWorkForYou\Url('royal');
                break;

            default:
                $URL = new \MySociety\TheyWorkForYou\Url('mp');
                break;
        }

        $member_url = make_member_url($this->full_name(true), $this->constituency(), $house, $this->person_id());
        if ($absolute) {
            $protocol = 'https://';
            if (DEVSITE) {
                $protocol = 'http://';
            }
            return $protocol . DOMAIN . $URL->generate('none') . $member_url;
        } else {
            return $URL->generate('none') . $member_url;
        }
    }

    private function _previous_future_mps_query($direction) {
        $entered_house = $this->entered_house(HOUSE_TYPE_COMMONS);
        if (is_null($entered_house)) {
            return '';
        }
        if ($direction == '>') {
            $order = '';
        } else {
            $order = 'DESC';
        }
        $q = $this->db->query('SELECT *
            FROM member, person_names pn
            WHERE member.person_id = pn.person_id AND pn.type = "name"
                AND pn.start_date <= member.left_house AND member.left_house <= pn.end_date
                AND house = :house AND constituency = :cons
                AND member.person_id != :pid AND entered_house ' . $direction . ' :date ORDER BY entered_house ' . $order,
            array(
                ':house' => HOUSE_TYPE_COMMONS,
                ':cons' => $this->constituency(),
                ':pid' => $this->person_id(),
                ':date' => $entered_house['date'],
            ));
        $mships = array(); $last_pid = null;
        foreach ($q as $row) {
            $pid = $row['person_id'];
            $name = $row['given_name'] . ' ' . $row['family_name'];
            if ($last_pid != $pid) {
                $mships[] = array(
                    'href' => WEBPATH . 'mp/?pid='.$pid,
                    'text' => $name
                );
                $last_pid = $pid;
            }
        }
        return $mships;
    }

    public function previous_mps() {
        return $this->_previous_future_mps_query('<');
    }

    public function future_mps() {
        return $this->_previous_future_mps_query('>');
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
