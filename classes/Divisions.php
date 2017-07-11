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

    public function __construct(Member $member = NULL, PolicyPositions $positions = NULL, Policies $policies = NULL)
    {
        $this->member = $member;
        $this->positions = $positions;
        $this->policies = $policies;
        $this->db = new \ParlDB;
    }

    public static function getMostRecentDivisionDate() {
      $db = new \ParlDB;
      $q = $db->query(
        "SELECT policy_id, max(division_date) as recent from policydivisions GROUP BY policy_id"
      );

      $policy_maxes = array();
      $row_count = $q->rows();
      for ($n = 0; $n < $row_count; $n++) {
        $policy_maxes[$q->field($n, 'policy_id')] = $q->field( $n, 'recent' );
      }
      $policy_maxes['latest'] = $policy_maxes ? max(array_values($policy_maxes)) : '';
      return $policy_maxes;
    }

    public function getRecentDivisions($number = 20) {
        // Grab distinct divisions as sometimes we have the same division for more than one policy
        // and we don't want to display it twice
        $q = $this->db->query(
          "SELECT distinct division_id, division_title, yes_text, no_text, division_date, division_number, gid, direction, majority_vote,
          yes_total, no_total, absent_total, both_total
          FROM policydivisions ORDER BY division_date DESC, division_number DESC LIMIT :count",
            array(
                ':count' => $number
            )
        );

        $divisions = array();
        foreach ($q->data as $division) {
            $divisions[] = $this->getParliamentDivisionDetails($division);
        }

        return array('divisions' => $divisions);
    }

    public function getRecentDivisionsForPolicies($policies, $number = 20) {
        $args = array(':number' => $number);

        $quoted = array();
        foreach ($policies as $policy) {
            $quoted[] = $this->db->quote($policy);
        }
        $policies_str = implode(',', $quoted);

        $q = $this->db->query(
            "SELECT division_id, division_title, yes_text, no_text, division_date, division_number, gid, direction, majority_vote,
            yes_total, no_total, absent_total, both_total
            FROM policydivisions
            WHERE policy_id in ($policies_str)
            GROUP BY division_id
            ORDER by division_date DESC LIMIT :number",
            $args
        );

        $divisions = array();
        $row_count = $q->rows();
        for ($n = 0; $n < $row_count; $n++) {
          $divisions[] = $this->getParliamentDivisionDetails($q->row($n));
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
     * @param $policyId The ID of the policy to get divisions for
     */

    public function getMemberDivisionsForPolicy($policyID = null) {
        $where_extra = '';
        $args = array(':person_id' => $this->member->person_id);
        if ( $policyID ) {
            $where_extra = 'AND policy_id = :policy_id';
            $args[':policy_id'] = $policyID;
        }
        $q = $this->db->query(
            "SELECT policy_id, division_id, division_title, yes_text, no_text, division_date, division_number, vote, gid, direction
            FROM policydivisions JOIN persondivisionvotes USING(division_id)
            WHERE person_id = :person_id AND direction <> 'abstention' $where_extra
            ORDER by policy_id, division_date",
            $args
        );

        return $this->divisionsByPolicy($q);
    }

    public function getMemberDivisionDetails() {
        $args = array(':person_id' => $this->member->person_id);

        $policy_divisions = array();

        $q = $this->db->query(
            "SELECT policy_id, policy_vote, vote, count(division_id) as total,
            max(year(division_date)) as latest, min(year(division_date)) as earliest
            FROM policydivisions JOIN persondivisionvotes USING(division_id)
            WHERE person_id = :person_id AND direction <> 'abstention'
            GROUP BY policy_id, policy_vote, vote",
            $args
        );

        $row_count = $q->rows();
        for ($n = 0; $n < $row_count; $n++) {
          $policy_id = $q->field($n, 'policy_id');

          if (!array_key_exists($policy_id, $policy_divisions)) {
            $summary = array(
              'max' => $q->field($n, 'latest'),
              'min' => $q->field($n, 'earliest'),
              'total' => $q->field($n, 'total'),
              'for' => 0, 'against' => 0, 'absent' => 0, 'both' => 0, 'tell' => 0
            );

            $policy_divisions[$policy_id] = $summary;
          }

          $summary = $policy_divisions[$policy_id];

          $summary['total'] += $q->field($n, 'total');
          if ($summary['max'] < $q->field($n, 'latest')) {
              $summary['max'] = $q->field($n, 'latest');
          }
          if ($summary['min'] > $q->field($n, 'latest')) {
              $summary['min'] = $q->field($n, 'latest');
          }

          $vote = $q->field($n, 'vote');
          $policy_vote = str_replace('3', '', $q->field($n, 'policy_vote'));
          if ( $vote == 'absent' ) {
              $summary['absent'] += $q->field($n, 'total');
          } else if ( $vote == 'both' ) {
              $summary['both'] += $q->field($n, 'total');
          } else if ( strpos($vote, 'tell') !== FALSE ) {
              $summary['tell'] += $q->field($n, 'total');
          } else if ( $policy_vote == $vote ) {
              $summary['for'] += $q->field($n, 'total');
          } else if ( $policy_vote != $vote ) {
              $summary['against'] += $q->field($n, 'total');
          }

          $policy_divisions[$policy_id] = $summary;
        }

        return $policy_divisions;
    }

    public function getDivisionResults($division_id) {
        $args = array(
            ':division_id' => $division_id
        );
        $q = $this->db->query(
            "SELECT division_id, division_title, yes_text, no_text, division_date, division_number, gid, direction,
            yes_total, no_total, absent_total, both_total, majority_vote
            FROM policydivisions
            WHERE division_id = :division_id",
            $args
        );

        if ($q->rows == 0) {
            return false;
        }

        $details = $this->getParliamentDivisionDetails($q->row(0));
        $details['division_title'] = $q->row(0)['division_title'];

        $q = $this->db->query(
            "SELECT person_id, vote, given_name, family_name
            FROM persondivisionvotes JOIN person_names USING(person_id)
            WHERE division_id = :division_id
            ORDER by family_name",
            $args
        );

        $votes = array(
          'yes_votes' => array(),
          'no_votes' => array(),
          'absent_votes' => array(),
          'both_votes' => array()
        );

        foreach ($q->data as $vote) {
            $detail = array(
              'person_id' => $vote['person_id'],
              'name' => $vote['given_name'] . ' ' . $vote['family_name'],
              'teller' => false
            );

            if (strpos($vote['vote'], 'tell') !== FALSE) {
                $detail['teller'] = true;
            }

            if ($vote['vote'] == 'aye' or $vote['vote'] == 'tellaye') {
              $votes['yes_votes'][] = $detail;
            } else if ($vote['vote'] == 'no' or $vote['vote'] == 'tellno') {
              $votes['no_votes'][] = $detail;
            } else if ($vote['vote'] == 'absent') {
              $votes['absent_votes'][] = $detail;
            } else if ($vote['vote'] == 'both') {
              $votes['both_votes'][] = $detail;
            }

        }

        $details = array_merge($details, $votes);

        return $details;
    }

    public function getDivisionResultsForMember($division_id, $person_id) {
        $args = array(
            ':division_id' => $division_id,
            ':person_id' => $person_id
        );
        $q = $this->db->query(
            "SELECT division_id, division_title, yes_text, no_text, division_date, division_number, gid, direction, vote
            FROM policydivisions JOIN persondivisionvotes USING(division_id)
            WHERE division_id = :division_id AND person_id = :person_id",
            $args
        );

        // if the vote was before or after the MP was in Parliament
        // then there won't be a row
        if ($q->rows == 0) {
            return false;
        }

        $details = $this->getDivisionDetails($q->row(0));
        return $details;
    }

    public function generateSummary($votes) {
        $max = $votes['max'];
        $min = $votes['min'];

        $actions = array(
            $votes['for'] . ' ' . make_plural('vote', $votes['for']) . ' for',
            $votes['against'] . ' ' . make_plural('vote', $votes['against']) . ' against'
        );

        if ( $votes['both'] ) {
            $actions[] = $votes['both'] . ' ' . make_plural('abstention', $votes['both']);
        }
        if ( $votes['absent'] ) {
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
        return $this->getMemberDivisionsForPolicy();
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
    public function getRecentMemberDivisions($number = 20, $context = 'MP') {
        $args = array(':person_id' => $this->member->person_id, ':number' => $number);
        $q = $this->db->query(
            "SELECT division_id, division_title, yes_text, no_text, division_date, division_number, vote, gid, direction,
            yes_total, no_total, absent_total, both_total, majority_vote
            FROM policydivisions JOIN persondivisionvotes USING(division_id)
            WHERE person_id = :person_id
            GROUP BY division_id
            ORDER by division_date DESC, division_id DESC LIMIT :number",
            $args
        );

        $divisions = array();
        $row_count = $q->rows();
        for ($n = 0; $n < $row_count; $n++) {
          if ($context == 'Parliament') {
              $divisions[] = $this->getParliamentDivisionDetails($q->row($n));
          } else {
              $divisions[] = $this->getDivisionDetails($q->row($n));
          }
        }

        return $divisions;
    }


    private function constructYesNoVoteDescription($direction, $title, $short_text) {
        $text = ' voted ';
        if ( $short_text ) {
            $text .= $short_text;
        } else {
            $text .= "$direction on <em>$title</em>";
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

        switch ( strtolower($vote) ) {
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
        $division = array();

        $direction = $row['direction'];
        if ( strpos( $direction, 'strong') !== FALSE ) {
            $division['strong'] = TRUE;
        } else {
            $division['strong'] = FALSE;
        }

        $division['division_id'] = $row['division_id'];
        $division['date'] = $row['division_date'];
        $division['gid'] = fix_gid_from_db($row['gid']);
        $division['url'] = $this->divisionUrlFromGid($row['gid']);
        $division['direction'] = $direction;
        $division['number'] = $row['division_number'];

        $yes_text = $row['yes_text'];
        $no_text = $row['no_text'];
        $division_title = $row['division_title'];
        $division['text'] = $this->constructVoteDescription($vote, $yes_text, $no_text, $division_title);

        $division['has_description'] = false;
        if ($yes_text && $no_text) {
            $division['has_description'] = true;
        }

        if ($row['gid']) {
            $division['debate_url'] = $this->divisionUrlFromGid($row['gid']);
        }

        return $division;
    }

    private function getDivisionDetails($row) {
        $vote = $row['vote'];
        $division = $this->getBasicDivisionDetails($row, $vote);

        $division['vote'] = $vote;

        return $division;
    }

    private function getParliamentDivisionDetails($row) {
        $vote = $row['majority_vote'];
        $division = $this->getBasicDivisionDetails($row, $vote);

        $division['mp_vote'] = '';
        if (array_key_exists('vote', $row)) {
          $mp_vote = ' was absent';
          if ($row['vote'] == 'aye') {
              $mp_vote = 'voted in favour';
          } else if ($row['vote'] == 'no') {
              $mp_vote = 'voted against';
          }
          $division['mp_vote'] = $mp_vote;
        }
        $division['division_title'] = $row['division_title'];
        $division['vote'] = $vote;

        $division['summary'] = $row['yes_total'] . ' for, ' . $row['no_total'] . ' against, ' . $row['absent_total'] . ' absent';
        $division['for'] = $row['yes_total'];
        $division['against'] = $row['no_total'];
        $division['both'] = $row['both_total'];
        $division['absent'] = $row['absent_total'];

        return $division;
    }

    private function divisionsByPolicy($q) {
        $policies = array();

        $row_count = $q->rows();
        for ($n = 0; $n < $row_count; $n++) {
            $policy_id = $q->field($n, 'policy_id');

            if ( !array_key_exists($policy_id, $policies) ) {
                $policies[$policy_id] = array(
                    'policy_id' => $policy_id,
                    'weak_count' => 0,
                    'divisions' => array()
                );
                if ( $this->policies ) {
                    $policies[$policy_id]['desc'] = $this->policies->getPolicies()[$policy_id];
                    $policies[$policy_id]['header'] = $this->policies->getPolicyDetails($policy_id);
                }
                if ( $this->positions ) {
                    $policies[$policy_id]['position'] = $this->positions->positionsById[$policy_id];
                }
            }

            $division = $this->getDivisionDetails($q->row($n));

            if ( !$division['strong'] ) {
                $policies[$policy_id]['weak_count']++;
            }

            $policies[$policy_id]['divisions'][] = $division;
        };

        return $policies;
    }

    private function divisionUrlFromGid($gid) {
        global $hansardmajors;

        $gid = get_canonical_gid($gid);

        $q = $this->db->query("SELECT gid, major FROM hansard WHERE epobject_id = ( SELECT subsection_id FROM hansard WHERE gid = :gid )", array( ':gid' => $gid ));
        $parent_gid = $q->field(0, 'gid');
        if ( !$parent_gid ) {
            return '';
        }
        $parent_gid = fix_gid_from_db($parent_gid);
        $url = new \URL($hansardmajors[$q->field(0, 'major')]['page']);
        $url->insert(array('gid' => $parent_gid));
        return $url->generate();
    }
}
