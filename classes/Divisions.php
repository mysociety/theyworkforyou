<?php
/**
 * Policy Positions
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou;

class Divisions {
    /**
     * Member
     */

    private $member;

    /**
     * DB handle
     */
    private $db;

    private $positions;
    private $policies;

    /**
     * Constructor
     *
     * @param Member   $member   The member to get positions for.
     */

    public function __construct(Member $member = null, PolicyPositions $positions = null) {
        $this->member = $member;
        $this->positions = $positions;
        $this->policies = new Policies();
        $this->db = new \ParlDB();
    }

    public static function getMostRecentDivisionDate() {
        $db = new \ParlDB();
        $q = $db->query(
            "SELECT policy_id, max(division_date) as recent
            FROM policydivisions
                JOIN divisions USING(division_id)
            GROUP BY policy_id"
        );

        $policy_maxes = [];
        foreach ($q as $row) {
            $policy_maxes[$row['policy_id']] = $row['recent'];
        }
        $policy_maxes['latest'] = $policy_maxes ? max(array_values($policy_maxes)) : '';
        return $policy_maxes;
    }

    /**
     * @param  int              $number  Number of divisions to return. Optional.
     * @param  string|string[]  $houses  House name (eg: "commons") or array of
     *                                   house names. Optional.
     */
    public function getRecentDivisions($number = 20, $houses = null) {
        $select = '';
        $order = 'ORDER BY division_date DESC, division_number DESC';
        $limit = 'LIMIT :count';
        $params = [
            ':count' => $number,
        ];

        $where = [];
        if ($houses) {
            if (is_string($houses)) {
                $houses = [ $houses ];
            }
            $where[] = 'house IN ("' . implode('", "', $houses) . '")';
        }
        if (!$houses || in_array('senedd', $houses)) {
            if (LANGUAGE == 'cy') {
                $where[] = "divisions.division_id NOT LIKE '%-en-%'";
            } else {
                $where[] = "divisions.division_id NOT LIKE '%-cy-%'";
            }
        }
        $where = 'WHERE ' . join(' AND ', $where);

        if ($this->member) {
            $select = "SELECT divisions.*, vote FROM divisions
                LEFT JOIN persondivisionvotes ON divisions.division_id=persondivisionvotes.division_id AND person_id=:person_id";
            $params[':person_id'] = $this->member->person_id;
        } else {
            $select = "SELECT * FROM divisions";
        }

        $q = $this->db->query(
            sprintf("%s %s %s %s", $select, $where, $order, $limit),
            $params
        );

        $divisions = [];
        foreach ($q as $division) {
            $data = $this->getParliamentDivisionDetails($division);

            $mp_vote = '';
            if (array_key_exists('vote', $division)) {
                if ($division['vote'] == 'aye') {
                    $mp_vote = 'voted in favour';
                } elseif ($division['vote'] == 'tellaye') {
                    $mp_vote = 'voted (as a teller) in favour';
                } elseif ($division['vote'] == 'no') {
                    $mp_vote = 'voted against';
                } elseif ($division['vote'] == 'tellno') {
                    $mp_vote = 'voted (as a teller) against';
                } elseif ($division['vote'] == 'absent') {
                    $mp_vote = ' was absent';
                } elseif ($division['vote'] == 'both') {
                    $mp_vote = ' abstained';
                }
            }
            $data['mp_vote'] = $mp_vote;
            $house = Utility\House::division_house_name_to_number($division['house']);
            $data['members'] = \MySociety\TheyWorkForYou\Utility\House::house_to_members($house);
            $divisions[] = $data;
        }

        return ['divisions' => $divisions];
    }

    /**
     * @param  int              $number  Number of divisions to return. Optional.
     * @param  int|int[]        $majors  Major types (e.g. 1) or array of
     *                                   major types. Optional.
     */
    public function getRecentDebatesWithDivisions($number = 20, $majors = null) {
        global $hansardmajors;

        if (!is_array($majors)) {
            $majors = [$majors];
        }

        $where = '';
        if (count($majors) > 0) {
            $where = 'AND h.major IN (' . implode(', ', $majors) . ')';
        }

        # Fetch any division speech, its subsection gid for the link, and
        # section/subsection bodies to construct a debate title
        $q = $this->db->query(
            "SELECT min(eps.body) as section_body, min(epss.body) as subsection_body,
                min(ss.gid) as debate_gid, min(h.gid) AS gid, min(h.hdate) as hdate,
                min(h.major) as major, count(h.gid) AS c
            FROM hansard h, hansard ss, epobject eps, epobject epss
            WHERE h.section_id = eps.epobject_id
                AND h.subsection_id = epss.epobject_id
                AND h.subsection_id = ss.epobject_id
                AND h.htype=14
            $where
            GROUP BY h.subsection_id
            ORDER BY h.hdate DESC, h.hpos DESC
            LIMIT :count",
            [':count' => $number]
        );

        $debates = [];
        foreach ($q as $debate) {
            $debate_gid = fix_gid_from_db($debate['debate_gid']);
            $anchor = '';
            if ($debate['c'] == 1) {
                $anchor = '#g' . gid_to_anchor(fix_gid_from_db($debate['gid']));
            }
            $url = new Url($hansardmajors[$debate['major']]['page']);
            $url->insert(['gid' => $debate_gid]);
            $debates[] = [
                'url' => $url->generate() . $anchor,
                'title' => "$debate[section_body] : $debate[subsection_body]",
                'date' => $debate['hdate'],
                'major' => $debate['major'],
            ];
        }

        return $debates;
    }

    public function getRecentDivisionsForPolicies($policies, $number = 20) {
        $args = [':number' => $number];

        $quoted = [];
        foreach ($policies as $policy) {
            $quoted[] = $this->db->quote($policy);
        }
        $policies_str = implode(',', $quoted);

        $q = $this->db->query(
            "SELECT divisions.*
            FROM policydivisions
                JOIN divisions USING(division_id)
            WHERE policy_id in ($policies_str)
            GROUP BY division_id
            ORDER by division_date DESC LIMIT :number",
            $args
        );

        $divisions = [];
        foreach ($q as $row) {
            $divisions[] = $this->getParliamentDivisionDetails($row);
        }

        return $divisions;
    }

    /**
     *
     * Get a list of division votes related to a policy
     *
     * Returns an array with one key ( the policyID ) containing a hash
     * with a policy_id key and a divisions key which contains an array
     * with details of all the divisions.
     *
     * Each division is a hash with the following fields:
     *    division_id, date, vote, gid, url, text, strong
     *
     * @param int|null $policyId The ID of the policy to get divisions for
     */

    public function getMemberDivisionsForPolicy($policyID = null) {
        $where_extra = '';
        $args = [':person_id' => $this->member->person_id];
        if ($policyID) {
            $where_extra = 'AND policy_id = :policy_id';
            $args[':policy_id'] = $policyID;
        }
        $q = $this->db->query(
            "SELECT policy_id, division_id, division_title, yes_text, no_text, division_date, division_number, vote, gid, direction
            FROM policydivisions JOIN persondivisionvotes USING(division_id)
                JOIN divisions USING(division_id)
            WHERE person_id = :person_id AND direction <> 'abstention' $where_extra
            ORDER by policy_id, division_date DESC",
            $args
        );
        # possibly add another query here to get related policies that use the same votes
        return $this->divisionsByPolicy($q);
    }

    public function getMemberDivisionDetails($strong_only = false) {
        $args = [':person_id' => $this->member->person_id];

        $policy_divisions = [];
        if ($strong_only) {
            $where_extra = "AND (policy_vote = 'no3' OR policy_vote = 'aye3')";
        } else {
            $where_extra = '';
        }
        $q = $this->db->query(
            "SELECT policy_id, policy_vote, vote, count(division_id) as total,
            max(year(division_date)) as latest, min(year(division_date)) as earliest
            FROM policydivisions JOIN persondivisionvotes USING(division_id)
                JOIN divisions USING(division_id)
            WHERE person_id = :person_id AND direction <> 'abstention' $where_extra
            GROUP BY policy_id, policy_vote, vote",
            $args
        );

        foreach ($q as $row) {
            $policy_id = $row['policy_id'];

            if (!array_key_exists($policy_id, $policy_divisions)) {
                $summary = [
                    'max' => $row['latest'],
                    'min' => $row['earliest'],
                    'total' => $row['total'],
                    'for' => 0, 'against' => 0, 'absent' => 0, 'both' => 0, 'tell' => 0,
                ];

                $policy_divisions[$policy_id] = $summary;
            }

            $summary = $policy_divisions[$policy_id];

            $summary['total'] += $row['total'];
            if ($summary['max'] < $row['latest']) {
                $summary['max'] = $row['latest'];
            }
            if ($summary['min'] > $row['latest']) {
                $summary['min'] = $row['latest'];
            }

            $vote = $row['vote'];
            $policy_vote = str_replace('3', '', $row['policy_vote']);
            if ($vote == 'absent') {
                $summary['absent'] += $row['total'];
            } elseif ($vote == 'both') {
                $summary['both'] += $row['total'];
            } elseif (strpos($vote, 'tell') !== false) {
                $summary['tell'] += $row['total'];
            } elseif ($policy_vote == $vote) {
                $summary['for'] += $row['total'];
            } elseif ($policy_vote != $vote) {
                $summary['against'] += $row['total'];
            }

            $policy_divisions[$policy_id] = $summary;
        }

        // for each key in $policy_divisions, we want to add agreement information

        $policies_list = new \MySociety\TheyWorkForYou\Policies();
        foreach ($policy_divisions as $policy_id => &$summary) {
            $agreement_details = $this->member->member_agreements($policy_id, HOUSE_TYPE_COMMONS, $policies_list);
            $summary["agreements_for"] = 0;
            $summary["agreements_against"] = 0;
            foreach ($agreement_details as $agreement) {
                if ($strong_only == true & $agreement["strength"] != "strong") {
                    continue;
                }
                if ($agreement["alignment"] == "agree") {
                    $summary["agreements_for"] += 1;
                } else {
                    $summary["agreements_against"] += 1;
                }
            }
        }
        return $policy_divisions;
    }

    public function getDivisionByGid($gid) {
        $args = [
            ':gid' => $gid,
        ];
        $q = $this->db->query("SELECT * FROM divisions WHERE gid = :gid", $args)->first();

        if (!$q) {
            return false;
        }

        return $this->_division_data($q);
    }

    public function getDivisionResults($division_id) {
        $args = [
            ':division_id' => $division_id,
        ];
        $q = $this->db->query("SELECT * FROM divisions WHERE division_id = :division_id", $args)->first();

        if (!$q) {
            return false;
        }

        return $this->_division_data($q);

    }

    private function _division_data($row) {

        $details = $this->getParliamentDivisionDetails($row);

        $house = $row['house'];
        $args['division_id'] = $row['division_id'];
        $args['division_date'] = $row['division_date'];
        $args['house'] = \MySociety\TheyWorkForYou\Utility\House::division_house_name_to_number($house);

        $q = $this->db->query(
            "SELECT pdv.person_id, vote, proxy, title, given_name, family_name, lordofname, party
            FROM persondivisionvotes AS pdv JOIN person_names AS pn ON (pdv.person_id = pn.person_id)
            JOIN member AS m ON (pdv.person_id = m.person_id)
            WHERE division_id = :division_id
            AND house = :house AND entered_house <= :division_date AND left_house >= :division_date
            AND start_date <= :division_date AND end_date >= :division_date
            ORDER by family_name",
            $args
        );

        $votes = [
            'yes_votes' => [],
            'no_votes' => [],
            'absent_votes' => [],
            'both_votes' => [],
        ];

        $party_breakdown = [
            'yes_votes' => [],
            'no_votes' => [],
            'absent_votes' => [],
            'both_votes' => [],
        ];

        # Sort Lords specially
        $data = $q->fetchAll();
        if ($args['house'] == HOUSE_TYPE_LORDS) {
            uasort($data, 'by_peer_name');
        }

        foreach ($data as $vote) {
            $detail = [
                'person_id' => $vote['person_id'],
                'name' => ucfirst(member_full_name(
                    $args['house'],
                    $vote['title'],
                    $vote['given_name'],
                    $vote['family_name'],
                    $vote['lordofname']
                )),
                'party' => $vote['party'],
                'proxy' => false,
                'teller' => false,
            ];

            if (strpos($vote['vote'], 'tell') !== false) {
                $detail['teller'] = true;
            }

            if ($vote['proxy']) {
                $q = $this->db->query(
                    "SELECT title, given_name, family_name, lordofname
                    FROM person_names AS pn
                    WHERE person_id = :person_id
                    AND start_date <= :division_date AND end_date >= :division_date",
                    [ ':person_id' => $vote['proxy'], ':division_date' => $row['division_date'] ]
                )->first();
                $detail['proxy'] = ucfirst(member_full_name(
                    HOUSE_TYPE_COMMONS,
                    $q['title'],
                    $q['given_name'],
                    $q['family_name'],
                    $q['lordofname']
                ));
            }

            if ($vote['vote'] == 'aye' or $vote['vote'] == 'tellaye') {
                $votes['yes_votes'][] = $detail;
                @$party_breakdown['yes_votes'][$detail['party']]++;
            } elseif ($vote['vote'] == 'no' or $vote['vote'] == 'tellno') {
                $votes['no_votes'][] = $detail;
                @$party_breakdown['no_votes'][$detail['party']]++;
            } elseif ($vote['vote'] == 'absent') {
                $votes['absent_votes'][] = $detail;
                @$party_breakdown['absent_votes'][$detail['party']]++;
            } elseif ($vote['vote'] == 'both') {
                $votes['both_votes'][] = $detail;
                @$party_breakdown['both_votes'][$detail['party']]++;
            }
        }

        foreach ($votes as $vote => $count) { // array('yes_votes', 'no_votes', 'absent_votes', 'both_votes') as $vote) {
            $votes[$vote . '_by_party'] = $votes[$vote];
            usort($votes[$vote . '_by_party'], function ($a, $b) {
                return $a['party'] > $b['party'];
            });
        }

        foreach ($party_breakdown as $vote => $parties) {
            $summary = [];
            foreach ($parties as $party => $count) {
                array_push($summary, "$party: $count");
            }

            sort($summary);
            $party_breakdown[$vote] = implode(', ', $summary);
        }

        $details = array_merge($details, $votes);
        $details['party_breakdown'] = $party_breakdown;
        $details['members'] = \MySociety\TheyWorkForYou\Utility\House::house_to_members($args['house']);
        $details['house'] = $house;
        $details['house_number'] = $args['house'];

        return $details;
    }

    public function getDivisionResultsForMember($division_id, $person_id) {
        $args = [
            ':division_id' => $division_id,
            ':person_id' => $person_id,
        ];
        $q = $this->db->query(
            "SELECT division_id, division_title, yes_text, no_text, division_date, division_number, gid, vote
            FROM divisions JOIN persondivisionvotes USING(division_id)
            WHERE division_id = :division_id AND person_id = :person_id",
            $args
        )->first();

        // if the vote was before or after the MP was in Parliament
        // then there won't be a row
        if (!$q) {
            return false;
        }

        $details = $this->getDivisionDetails($q);
        return $details;
    }

    public function generateSummary($votes) {
        $max = $votes['max'];
        $min = $votes['min'];

        $actions = [
            $votes['for'] . ' ' . make_plural('vote', $votes['for']) . ' for',
            $votes['against'] . ' ' . make_plural('vote', $votes['against']) . ' against',
        ];

        if ($votes['agreements_for']) {
            $actions[] = $votes['agreements_for'] . ' ' . make_plural('agreement', $votes['agreements_for']) . ' for';
        }

        if ($votes['agreements_against']) {
            $actions[] = $votes['agreements_against'] . ' ' . make_plural('agreement', $votes['agreements_against']) . ' against';
        }

        if ($votes['both']) {
            $actions[] = $votes['both'] . ' ' . make_plural('abstention', $votes['both']);
        }
        if ($votes['absent']) {
            $actions[] = $votes['absent'] . ' ' . make_plural('absence', $votes['absent']);
        }
        if ($max == $min) {
            return join(', ', $actions) . ', in ' . $max;
        } else {
            return join(', ', $actions) . ', between ' . $min . '&ndash;' . $max;
        }
    }

    /**
     *
     * Get all the divisions a member has voted in keyed by policy
     *
     * Returns an array with keys for each policyID, each of these contains
     * the same structure as getMemberDivisionsForPolicy
     *
     */

    public function getAllMemberDivisionsByPolicy() {
        $policies = $this->getMemberDivisionsForPolicy();
        return Utility\Shuffle::keyValue($policies);
    }


    /**
     * Get the last n votes for a member
     *
     * @param $number int - How many divisions to return. Defaults to 20
     * @param $context string - The context of the page the results are being presented in.
     *    This affects the summary details and can either be 'Parliament' in which case the
     *    overall vote for all MPs is returned, plus additional information on how the MP passed
     *    in to the constructor voted, or the default of 'MP' which is just the vote of the
     *    MP passed in to the constructor.
     *
     * Returns an array of divisions
     */
    public function getRecentMemberDivisions($number = 20) {
        $args = [':person_id' => $this->member->person_id, ':number' => $number];
        $q = $this->db->query(
            "SELECT *
            FROM persondivisionvotes
                JOIN divisions USING(division_id)
            WHERE person_id = :person_id
            ORDER by division_date DESC, division_number DESC, division_id DESC LIMIT :number",
            $args
        );

        $divisions = [];
        foreach ($q as $row) {
            $divisions[] = $this->getDivisionDetails($row);
        }

        return $divisions;
    }

    private function constructYesNoVoteDescription($direction, $title, $short_text) {
        $text = ' ' ;
        if ($short_text) {
            $text .= sprintf(gettext('voted %s'), $short_text);
        } else {
            $text .= sprintf(gettext('voted %s on <em>%s</em>'), $direction, $title);
        }

        return $text;
    }

    private function constructVoteDescription($vote, $yes_text, $no_text, $division_title) {
        /*
         * for most post 2010 votes we have nice single sentence summaries of
         * what voting for or against means so we use that if it's there, however
         * we don't have anything nice for people being absent or for pre 2010
         * votes so we need to generate some text using the title of the division
         */

        switch (strtolower($vote)) {
            case 'yes':
            case 'aye':
                $description = $this->constructYesNoVoteDescription('yes', $division_title, $yes_text);
                break;
            case 'no':
                $description = $this->constructYesNoVoteDescription('no', $division_title, $no_text);
                break;
            case 'absent':
                $description = ' was absent for a vote on <em>' . $division_title . '</em>';
                break;
            case 'both':
                $description = ' abstained on a vote on <em>' . $division_title . '</em>';
                break;
            case 'tellyes':
            case 'tellno':
            case 'tellaye':
                $description = ' acted as teller for a vote on <em>' . $division_title . '</em>';
                break;
            default:
                $description = $division_title;
        }

        return $description;
    }

    private function getBasicDivisionDetails($row, $vote) {
        $yes_text = $row['yes_text'];
        $no_text = $row['no_text'];

        $division = [
            'division_id' => $row['division_id'],
            'date' => $row['division_date'],
            'gid' => fix_gid_from_db($row['gid']),
            'number' => $row['division_number'],
            'text' => $this->constructVoteDescription($vote, $yes_text, $no_text, $row['division_title']),
            'has_description' => $yes_text && $no_text,
            'vote' => $vote,
        ];

        if ($row['gid']) {
            $division['debate_url'] = $this->divisionUrlFromGid($row['gid']);
        }

        # Policy-related information

        # So one option is just to query for it here
        # we want to add an array of policies aside the current policy
        # and if they have the same or different direction as thie current division
        # in the row

        # fetch related policies from database
        $q = $this->db->query(
            "SELECT policy_id, direction
            FROM policydivisions
            WHERE division_id = :division_id",
            [':division_id' => $row['division_id']]
        );
        $division['related_policies'] = [];

        $policy_lookup = $this->policies->getPolicies();
        foreach ($q as $policy) {
            $division['related_policies'][] = [
                'policy_id' => $policy['policy_id'],
                'policy_title' => preg_replace('#</?a[^>]*>#', '', $policy_lookup[$policy['policy_id']]),
                'direction' => $policy['direction'],
            ];
        }

        if (array_key_exists('direction', $row)) {
            $division['direction'] = $row['direction'];
            if (strpos($row['direction'], 'strong') !== false) {
                $division['strong'] = true;
            } else {
                $division['strong'] = false;
            }
        }

        return $division;
    }

    private function getDivisionDetails($row) {
        return $this->getBasicDivisionDetails($row, $row['vote']);
    }

    private function getParliamentDivisionDetails($row) {
        $division = $this->getBasicDivisionDetails($row, $row['majority_vote']);

        $division['division_title'] = $row['division_title'];
        $division['for'] = $row['yes_total'];
        $division['against'] = $row['no_total'];
        $division['both'] = $row['both_total'];
        $division['absent'] = $row['absent_total'];

        return $division;
    }

    private function divisionsByPolicy($q) {
        $policies = [];

        # iterate through each division, and adds it to an array of policies
        # if there is only one policy being queried, it will be an array of 1
        foreach ($q as $row) {
            $policy_id = $row['policy_id'];

            # if this policy hasn't come up yet, create the key for it
            if (!array_key_exists($policy_id, $policies)) {
                $policies[$policy_id] = [
                    'policy_id' => $policy_id,
                    'divisions' => [],
                ];
                $policies[$policy_id]['desc'] = $this->policies->getPolicies()[$policy_id];
                $policies[$policy_id]['header'] = $this->policies->getPolicyDetails($policy_id);
                if ($this->positions) {
                    $policies[$policy_id]['position'] = $this->positions->positionsById[$policy_id];
                }
            }


            $division = $this->getDivisionDetails($row);

            $policies[$policy_id]['divisions'][] = $division;
        };

        return $policies;
    }

    private function divisionUrlFromGid($gid) {
        global $hansardmajors;

        $gid = get_canonical_gid($gid);

        $q = $this->db->query("SELECT gid, major FROM hansard WHERE epobject_id = ( SELECT subsection_id FROM hansard WHERE gid = :gid )", [ ':gid' => $gid ])->first();
        if (!$q) {
            return '';
        }
        $parent_gid = fix_gid_from_db($q['gid']);
        $url = new Url($hansardmajors[$q['major']]['page']);
        $url->insert(['gid' => $parent_gid]);
        return $url->generate() . '#g' . gid_to_anchor(fix_gid_from_db($gid));
    }
}
