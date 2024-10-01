<?php

namespace MySociety\TheyWorkForYou\Utility;

/**
 * Search Utilities
 *
* Utility functions related to search and search strings
 */

class Search {
    public static function searchByUsage($search, $house = 0) {
        $data = [];
        $SEARCHENGINE = new \SEARCHENGINE($search);
        $data['pagetitle'] = $SEARCHENGINE->query_description_short();
        $SEARCHENGINE = new \SEARCHENGINE($search . ' groupby:speech');
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
        if (count($gids) == 5000) {
            $data['limit_reached'] = true;
        }

        # Fetch all the speakers of the results, count them up and get min/max date usage
        $speaker_count = [];
        $gids = join('","', $gids);
        $db = new \ParlDB();
        $q = $db->query('SELECT person_id,hdate FROM hansard WHERE gid IN ("' . $gids . '")');
        foreach ($q as $row) {
            $person_id = $row['person_id'];
            $hdate = $row['hdate'];
            if (!isset($speaker_count[$person_id])) {
                $speaker_count[$person_id] = 0;
                $maxdate[$person_id] = '1001-01-01';
                $mindate[$person_id] = '9999-12-31';
            }
            $speaker_count[$person_id]++;
            if ($hdate < $mindate[$person_id]) {
                $mindate[$person_id] = $hdate;
            }
            if ($hdate > $maxdate[$person_id]) {
                $maxdate[$person_id] = $hdate;
            }
        }

        # Fetch details of all the speakers
        $speakers = [];
        $pids = [];
        if (count($speaker_count)) {
            $person_ids = join(',', array_keys($speaker_count));
            $q = $db->query('SELECT member_id, member.person_id, title, given_name, family_name, lordofname,
                                constituency, house, party,
                                moffice_id, dept, position, from_date, to_date, left_house
                            FROM member LEFT JOIN moffice ON member.person_id = moffice.person
                                JOIN person_names pn ON member.person_id = pn.person_id AND pn.type="name" AND pn.start_date <= left_house AND left_house <= pn.end_date
                            WHERE member.person_id IN (' . $person_ids . ')
                            ' . ($house ? " AND house=$house" : '') . '
                            ORDER BY left_house DESC');
            foreach ($q as $row) {
                $mid = $row['member_id'];
                if (!isset($pids[$mid])) {
                    $title = $row['title'];
                    $first = $row['given_name'];
                    $last = $row['family_name'];
                    $lordofname = $row['lordofname'];
                    $house = $row['house'];
                    $party = $row['party'];
                    $full_name = ucfirst(member_full_name($house, $title, $first, $last, $lordofname));
                    $pid = $row['person_id'];
                    $pids[$mid] = $pid;
                    $speakers[$pid]['house'] = $house;
                    $speakers[$pid]['left'] = $row['left_house'];
                }
                $dept = $row['dept'];
                $posn = $row['position'];
                $moffice_id = $row['moffice_id'];
                if ($dept && $row['to_date'] == '9999-12-31') {
                    $speakers[$pid]['office'][$moffice_id] = prettify_office($posn, $dept);
                }
                if (!isset($speakers[$pid]['name'])) {
                    $speakers[$pid]['name'] = $full_name . ($house == 1 ? ' MP' : '');
                }
                if (!isset($speakers[$pid]['party']) && $party) {
                    $speakers[$pid]['party'] = $party;
                }
            }
        }
        if (isset($speaker_count[0])) {
            $speakers[0] = ['party' => '', 'name' => 'Headings, procedural text, etc.', 'house' => 0, 'count' => 0];
        }
        $party_count = [];
        $ok = 0;
        foreach ($speakers as $pid => &$speaker) {
            $speaker['count'] = $speaker_count[$pid];
            $speaker['pmaxdate'] = $maxdate[$pid];
            $speaker['pmindate'] = $mindate[$pid];
            $ok = 1;
            if (isset($speaker['party'])) {
                if (!isset($party_count[$speaker['party']])) {
                    $party_count[$speaker['party']] = 0;
                }
                $party_count[$speaker['party']] += $count;
            }
        }

        uasort($speakers, function ($a, $b) {

            if ($a['count'] > $b['count']) {
                return -1;
            }

            if ($a['count'] < $b['count']) {
                return 1;
            }

            return 0;

        });

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
     * Return query result from looking for MPs
     */

    public static function searchMemberDbLookup($searchstring, $current_only = false) {
        if (!$searchstring) {
            return [];
        }
        $searchwords = explode(' ', $searchstring, 3);
        $params = [];
        if (count($searchwords) == 1) {
            $params[':like_0'] = '%' . $searchwords[0] . '%';
            $where = "given_name LIKE :like_0 OR family_name LIKE :like_0 OR lordofname LIKE :like_0";
        } elseif (count($searchwords) == 2) {
            // We don't do anything special if there are more than two search words.
            // And here we're assuming the user's put the names in the right order.
            $params[':like_0'] = '%' . $searchwords[0] . '%';
            $params[':like_1'] = '%' . $searchwords[1] . '%';
            $params[':like_0_and_1'] = '%' . $searchwords[0] . ' ' . $searchwords[1] . '%';
            $params[':like_0_and_1_hyphen'] = '%' . $searchwords[0] . '-' . $searchwords[1] . '%';
            $where = "(given_name LIKE :like_0 AND family_name LIKE :like_1)";
            $where .= " OR (given_name LIKE :like_1 AND family_name LIKE :like_0)";
            $where .= " OR (title LIKE :like_0 AND family_name LIKE :like_1)";
            $where .= " OR given_name LIKE :like_0_and_1 OR given_name LIKE :like_0_and_1_hyphen";
            $where .= " OR family_name LIKE :like_0_and_1 OR family_name LIKE :like_0_and_1_hyphen";
            $where .= " OR lordofname LIKE :like_0_and_1";
            if (strtolower($searchwords[0]) == 'nick') {
                $where .= " OR (given_name LIKE '%nicholas%' AND family_name LIKE :like_1)";
            }
        } else {
            $searchwords[2] = str_replace('of ', '', $searchwords[2]);
            $params[':like_0'] = '%' . $searchwords[0] . '%';
            $params[':like_1'] = '%' . $searchwords[1] . '%';
            $params[':like_2'] = '%' . $searchwords[2] . '%';
            $params[':like_0_and_1'] = '%' . $searchwords[0] . ' ' . $searchwords[1] . '%';
            $params[':like_1_and_2'] = '%' . $searchwords[1] . ' ' . $searchwords[2] . '%';
            $params[':like_1_and_2_hyphen'] = '%' . $searchwords[1] . '-' . $searchwords[2] . '%';
            $where = "(given_name LIKE :like_0_and_1 AND family_name LIKE :like_2)";
            $where .= " OR (given_name LIKE :like_0 AND family_name LIKE :like_1_and_2)";
            $where .= " OR (given_name LIKE :like_0 AND family_name LIKE :like_1_and_2_hyphen)";
            $where .= " OR (title LIKE :like_0 AND family_name LIKE :like_1_and_2)";
            $where .= " OR (title LIKE :like_0 AND family_name LIKE :like_1_and_2_hyphen)";
            $where .= " OR (title LIKE :like_0 AND given_name LIKE :like_1 AND family_name LIKE :like_2)";
            $where .= " OR (title LIKE :like_0 AND family_name LIKE :like_1 AND lordofname LIKE :like_2)";
        }

        $db = new \ParlDB();
        $q = $db->query("SELECT person_id FROM person_names WHERE type='name' AND ($where)", $params);

        # Check for redirects
        $pids = [];
        foreach ($q as $row) {
            $pid = $row['person_id'];
            $redirect = $db->query(
                "SELECT gid_to FROM gidredirect WHERE gid_from = :gid_from",
                [':gid_from' => "uk.org.publicwhip/person/$pid"]
            )->first();
            if ($redirect) {
                $pid = str_replace('uk.org.publicwhip/person/', '', $redirect['gid_to']);
            }
            $pids[] = $pid;
        }

        return array_unique($pids);
    }

    public static function searchMemberDbLookupWithNames($searchstring, $current_only = false) {
        $pids = self::searchMemberDbLookup($searchstring, $current_only);

        if (!count($pids)) {
            return $pids;
        }

        $pids_str = join(',', $pids);

        $where = '';
        if ($current_only) {
            $where = "AND left_house='9999-12-31'";
        }

        # This is not totally accurate (e.g. minimum entered date may be from a
        # different house, or similar), but should be good enough.
        $db = new \ParlDB();
        $q = $db->query("SELECT member.person_id,
                                title, given_name, family_name, lordofname,
                                constituency, party,
                                (SELECT MIN(entered_house) FROM member m WHERE m.person_id=member.person_id) min_entered_house,
                                left_house, house
                        FROM member, person_names pn
                        WHERE member.person_id IN ($pids_str) $where
                            AND member.person_id = pn.person_id AND pn.type = 'name'
                            AND pn.start_date <= member.left_house AND member.left_house <= pn.end_date
                            AND left_house = (SELECT MAX(left_house) FROM member m WHERE m.person_id=member.person_id)
                        GROUP BY person_id
                        ORDER BY family_name, lordofname, given_name, person_id");

        return $q->fetchAll();
    }

    /**
     * Search Constituencies
     *
     * Given a search term, find constituencies by name or postcode.
     *
     * @param string $searchterm The term to search for.
     *
     * @return array A list of the array of constituencies, then a boolean
     *               saying whether it was a postcode used.
     */

    public static function searchConstituenciesByQuery($searchterm) {
        if (validate_postcode($searchterm)) {
            // Looks like a postcode - can we find the constituency?
            $constituency = Postcode::postcodeToConstituency($searchterm);
            if ($constituency) {
                return [ [$constituency], true ];
            }
        }

        // No luck so far - let's see if they're searching for a constituency.
        $try = strtolower($searchterm);
        $query = "select distinct
                (select name from constituency where cons_id = o.cons_id and main_name) as name
            from constituency AS o where name like :try
            and from_date <= date(now()) and date(now()) <= to_date";
        $db = new \ParlDB();
        $q = $db->query($query, [':try' => '%' . $try . '%']);

        $constituencies = [];
        foreach ($q as $row) {
            $constituencies[] = $row['name'];
        }

        return [ $constituencies, false ];
    }

    /**
     * get list of names of speaker IDs from search string
     *
     * @param string      $searchstring       The search string with the speaker:NNN text
     *
     * @return array Array with the speaker id string as key and speaker name as value
     */

    public static function speakerNamesForIDs($searchstring) {
        $criteria = explode(' ', $searchstring);
        $speakers = [];

        foreach ($criteria as $c) {
            if (preg_match('#^speaker:(\d+)#', $c, $m)) {
                $MEMBER = new \MEMBER(['person_id' => $m[1]]);
                $speakers[$m[1]] = $MEMBER->full_name();
            }
        }

        return $speakers;
    }

    /**
     * replace speaker:NNNN with speaker:Name in search string
     *
     * @param string      $searchstring       The search string with the speaker:NNN text
     *
     * @return string The search string with replaced speaker IDs
     */
    public static function speakerIDsToNames($searchstring) {
        $speakers = self::speakerNamesForIDs($searchstring);

        foreach ($speakers as $id => $name) {
            $searchstring = str_replace('speaker:' . $id, "speaker:$name", $searchstring);
        }

        return $searchstring;
    }
}
