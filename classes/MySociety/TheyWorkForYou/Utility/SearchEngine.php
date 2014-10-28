<?php

namespace MySociety\TheyWorkForYou\Utility;

/**
 * Search Engine Utilities
 *
 * Utility functions related to searching
 */

class SearchEngine
{

    public static function searchByUsage($search, $house = 0) {
        $data = array();
        $SEARCHENGINE = new \MySociety\TheyWorkForYou\SearchEngine($search);
        $data['pagetitle'] = $SEARCHENGINE->query_description_short();
        $SEARCHENGINE = new \MySociety\TheyWorkForYou\SearchEngine($search . ' groupby:speech');
        $count = $SEARCHENGINE->run_count(0, 5000, 'date');
        if ($count <= 0) {
            $data['error'] = 'No results';

            return $data;
        }
        $SEARCHENGINE->run_search(0, 5000, 'date');
        $gids = $SEARCHENGINE->get_gids();
        if (count($gids) <= 0) {
            $data['error'] = 'No results';

            return $data;
        }
        if (count($gids) == 5000)
            $data['limit_reached'] = true;

        # Fetch all the speakers of the results, count them up and get min/max date usage
        $speaker_count = array();
        $gids = join('","', $gids);
        $db = new \MySociety\TheyWorkForYou\ParlDb;
        $q = $db->query('SELECT gid,speaker_id,hdate FROM hansard WHERE gid IN ("' . $gids . '")');
        for ($n=0; $n<$q->rows(); $n++) {
            $gid = $q->field($n, 'gid');
            $speaker_id = $q->field($n, 'speaker_id'); # This is member ID
            $hdate = $q->field($n, 'hdate');
            if (!isset($speaker_count[$speaker_id])) {
                $speaker_count[$speaker_id] = 0;
                $maxdate[$speaker_id] = '1001-01-01';
                $mindate[$speaker_id] = '9999-12-31';
            }
            $speaker_count[$speaker_id]++;
            if ($hdate < $mindate[$speaker_id]) $mindate[$speaker_id] = $hdate;
            if ($hdate > $maxdate[$speaker_id]) $maxdate[$speaker_id] = $hdate;
        }

        # Fetch details of all the speakers
        if (count($speaker_count)) {
            $speaker_ids = join(',', array_keys($speaker_count));
            $q = $db->query('SELECT member_id, person_id, title,first_name,last_name,constituency,house,party,
                                moffice_id, dept, position, from_date, to_date, left_house
                            FROM member LEFT JOIN moffice ON member.person_id = moffice.person
                            WHERE member_id IN (' . $speaker_ids . ')
                            ' . ($house ? " AND house=$house" : '') . '
                            ORDER BY left_house DESC');
            for ($n=0; $n<$q->rows(); $n++) {
                $mid = $q->field($n, 'member_id');
                if (!isset($pids[$mid])) {
                    $title = $q->field($n, 'title');
                    $first = $q->field($n, 'first_name');
                    $last = $q->field($n, 'last_name');
                    $cons = $q->field($n, 'constituency');
                    $house = $q->field($n, 'house');
                    $party = $q->field($n, 'party');
                    $full_name = ucfirst(member_full_name($house, $title, $first, $last, $cons));
                    $pid = $q->field($n, 'person_id');
                    $pids[$mid] = $pid;
                    $speakers[$pid]['house'] = $house;
                    $speakers[$pid]['left'] = $q->field($n, 'left_house');
                }
                $dept = $q->field($n, 'dept');
                $posn = $q->field($n, 'position');
                $moffice_id = $q->field($n, 'moffice_id');
                if ($dept && $q->field($n, 'to_date') == '9999-12-31')
                    $speakers[$pid]['office'][$moffice_id] = prettify_office($posn, $dept);
                if (!isset($speakers[$pid]['name'])) {
                    $speakers[$pid]['name'] = $full_name . ($house==1?' MP':'');
                    $speakers[$pid]['party'] = $party;
                }
            }
        }
        $pids[0] = 0;
        $speakers[0] = array('party'=>'', 'name'=>'Headings, procedural text, etc.', 'house'=>0, 'count'=>0);
        $party_count = array();
        $ok = 0;
        foreach ($speaker_count as $speaker_id => $count) {
            if (!isset($pids[$speaker_id])) continue;
            $pid = $pids[$speaker_id];
            if (!isset($speakers[$pid]['pmindate'])) {
                $speakers[$pid]['count'] = 0;
                $speakers[$pid]['pmaxdate'] = '1001-01-01';
                $speakers[$pid]['pmindate'] = '9999-12-31';
                $ok = 1;
            }
            if (!isset($party_count[$speakers[$pid]['party']]))
                $party_count[$speakers[$pid]['party']] = 0;
            $speakers[$pid]['count'] += $count;
            $party_count[$speakers[$pid]['party']] += $count;
            if ($mindate[$speaker_id] < $speakers[$pid]['pmindate']) $speakers[$pid]['pmindate'] = $mindate[$speaker_id];
            if ($maxdate[$speaker_id] > $speakers[$pid]['pmaxdate']) $speakers[$pid]['pmaxdate'] = $maxdate[$speaker_id];
        }
        function sort_by_count($a, $b) {
            if ($a['count'] > $b['count']) return -1;
            if ($a['count'] < $b['count']) return 1;
            return 0;
        }
        if ($speakers[0]['count']==0) unset($speakers[0]);
        uasort($speakers, 'sort_by_count');
        arsort($party_count);
        if (!$ok) {
            $data['error'] = 'No results';

            return $data;
        }

        $data['party_count'] = $party_count;
        $data['speakers'] = $speakers;

        return $data;
    }

    /**
     * Return query result from looking for MPs.
     */
    public static function searchMemberDbLookup($searchstring, $current_only=false) {
        if (!$searchstring) return false;
        $searchwords = explode(' ', $searchstring, 3);
        $params = array();
        if (count($searchwords) == 1) {
            $params[':like_0'] = '%' . $searchwords[0] . '%';
            $where = "first_name LIKE :like_0 OR last_name LIKE :like_0";
        } elseif (count($searchwords) == 2) {
            // We don't do anything special if there are more than two search words.
            // And here we're assuming the user's put the names in the right order.
            $params[':like_0'] = '%' . $searchwords[0] . '%';
            $params[':like_1'] = '%' . $searchwords[1] . '%';
            $where = "(first_name LIKE :like_0 AND last_name LIKE :like_1)";
            $where .= " OR (first_name LIKE :like_1 AND last_name LIKE :like_0)";
            $where .= " OR (title LIKE :like_0 AND last_name LIKE :like_1)";
            if (strtolower($searchwords[0]) == 'nick') {
                $where .= " OR (first_name LIKE '%nicholas%' AND last_name LIKE :like_1)";
            }
        } else {
            $searchwords[2] = str_replace('of ', '', $searchwords[2]);
            $params[':like_0'] = '%' . $searchwords[0] . '%';
            $params[':like_1'] = '%' . $searchwords[1] . '%';
            $params[':like_2'] = '%' . $searchwords[2] . '%';
            $params[':like_0_and_1'] = '%' . $searchwords[0] . ' '. $searchwords[1] . '%';
            $params[':like_1_and_2'] = '%' . $searchwords[1] . ' '. $searchwords[2] . '%';
            $where = "(first_name LIKE :like_0_and_1 AND last_name LIKE :like_2)";
            $where .= " OR (first_name LIKE :like_0 AND last_name LIKE :like_1_and_2)";
            $where .= " OR (title LIKE :like_0 AND first_name LIKE :like_1 AND last_name LIKE :like_2)";
            $where .= " OR (title LIKE :like_0 AND last_name LIKE :like_1 AND constituency LIKE :like_2)";
        }
        $where = "($where)";

        if ($current_only) {
            $where .= " AND left_house='9999-12-31'";
        }

        $db = new \MySociety\TheyWorkForYou\ParlDb;
        $q = $db->query("SELECT person_id,
                                title, first_name, last_name,
                                constituency, party,
                                entered_house, left_house, house
                        FROM    member
                        WHERE   $where
                        ORDER BY last_name, first_name, person_id, entered_house desc
                        ", $params);

        return $q;
    }

    /**
     * Given a search term, find constituencies by name or postcode.
     *
     * @return array List of the array of constituencies, then a boolean saying whether it was a postcode used.
     */
    public static function searchConstituenciesByQuery($searchterm) {
        if (validate_postcode($searchterm)) {
            // Looks like a postcode - can we find the constituency?
            $constituency = \MySociety\TheyWorkForYou\Utility\Postcode::postcodeToConstituency($searchterm);
            if ($constituency) {
                return array( array($constituency), true );
            }
        }

        // No luck so far - let's see if they're searching for a constituency.
        $try = strtolower($searchterm);
        $query = "select distinct
                (select name from constituency where cons_id = o.cons_id and main_name) as name
            from constituency AS o where name like :try
            and from_date <= date(now()) and date(now()) <= to_date";
        $db = new \MySociety\TheyWorkForYou\ParlDb;
        $q = $db->query($query, array(':try' => '%' . $try . '%'));

        $constituencies = array();
        for ($n=0; $n<$q->rows(); $n++) {
            $constituencies[] = $q->field($n, 'name');
        }

        return array( $constituencies, false );
    }

}
