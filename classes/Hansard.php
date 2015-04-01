<?php

namespace MySociety\TheyWorkForYou;

/**
 * Hansard
 */

class Hansard extends \HANSARDLIST {

    /**
     * Search
     *
     * Performs a search of Hansard.
     *
     * @param string $searchstring The string to initialise SEARCHENGINE with.
     * @param array  $args         An array of arguments to restrict search results.
     *
     * @return array An array of search results.
     */

    public function search($searchstring, $args) {

        if (!defined('FRONT_END_SEARCH') || !FRONT_END_SEARCH) {
            throw new \Exception('FRONT_END_SEARCH is not defined or is false.');
        }

        // $args is an associative array with 's'=>'my search term' and
        // (optionally) 'p'=>1  (the page number of results to show) annd
        // (optionall) 'pop'=>1 (if "popular" search link, so don't log)
        global $PAGE, $hansardmajors;

        if (isset($args['s'])) {
            // $args['s'] should have been tidied up by the time we get here.
            // eg, by doing filter_user_input($s, 'strict');
            $searchstring = $args['s'];
        } else {
            throw new \Exception('No search string provided.');
        }

        // What we'll return.
        $data = array ();

        $data['info']['s'] = $args['s'];

        // Allows us to specify how many results we want
        // Mainly for glossary term adding
        if (isset($args['num']) && $args['num']) {
            $results_per_page = $args['num']+0;
        }
        else {
            $results_per_page = 20;
        }
        if ($results_per_page > 1000)
            $results_per_page = 1000;

        $data['info']['results_per_page'] = $results_per_page;

        // What page are we on?
        if (isset($args['p']) && is_numeric($args['p'])) {
            $page = $args['p'];
        } else {
            $page = 1;
        }
        $data['info']['page'] = $page;

        if (isset($args['e'])) {
            $encode = 'url';
        } else {
            $encode = 'html';
        }

        // Gloablise the search engine
        global $SEARCHENGINE;

        // For Xapian's equivalent of an SQL LIMIT clause.
        $first_result = ($page-1) * $results_per_page;
        $data['info']['first_result'] = $first_result + 1; // Take account of LIMIT's 0 base.

        // Get the gids from Xapian
        $sort_order = 'date';
        if (isset($args['o'])) {
            if ($args['o']=='d') $sort_order = 'newest';
            if ($args['o']=='o') $sort_order = 'oldest';
            elseif ($args['o']=='c') $sort_order = 'created';
            elseif ($args['o']=='r') $sort_order = 'relevance';
        }

        $data['searchdescription'] = $SEARCHENGINE->query_description_long();
        $count = $SEARCHENGINE->run_count($first_result, $results_per_page, $sort_order);
        $data['info']['total_results'] = $count;
        $data['info']['spelling_correction'] = $SEARCHENGINE->get_spelling_correction();

        // Log this query so we can improve them - if it wasn't a "popular
        // query" link
        if (! isset($args['pop']) or $args['pop'] != 1) {
            global $SEARCHLOG;
            $SEARCHLOG->add(
            array('query' => $searchstring,
                'page' => $page,
                'hits' => $count));
        }
        // No results.
        if ($count <= 0) {
            $data['rows'] = array();
            return $data;
        }

        $SEARCHENGINE->run_search($first_result, $results_per_page, $sort_order);
        $gids = $SEARCHENGINE->get_gids();
        if ($sort_order=='created') {
            $createds = $SEARCHENGINE->get_createds();
        }
        $relevances = $SEARCHENGINE->get_relevances();
        if (count($gids) <= 0) {
            // No results.
            $data['rows'] = array();
            return $data;
        }
        #if ($sort_order=='created') { print_r($gids); }

        // We'll put all the data in here before giving it to a template.
        $rows = array();

        // We'll cache the ids=>first_names/last_names of speakers here.
        $speakers = array();

        // We'll cache (sub)section_ids here:
        $hansard_to_gid = array();

        // Cycle through each result, munge the data, get more, and put it all in $data.
        $gids_count = count($gids);
        for ($n = 0; $n < $gids_count; $n++) {
            $gid = $gids[$n];
            $relevancy = $relevances[$n];
            $collapsed = $SEARCHENGINE->collapsed[$n];
            if ($sort_order=='created') {
                #$created = substr($createds[$n], 0, strpos($createds[$n], ':'));
                if ($createds[$n]<$args['threshold']) {
                    $data['info']['total_results'] = $n;
                    break;
                }
            }

            if (strstr($gid, 'calendar')) {
                $id = fix_gid_from_db($gid);

                $q = $this->db->query("SELECT *, event_date as hdate, pos as hpos
                    FROM future
                    LEFT JOIN future_people ON id=calendar_id AND witness=0
                    WHERE id = $id AND deleted=0");
                if ($q->rows() == 0) continue;

                $itemdata = $q->row(0);

                # Ignore past events in places that we cover (we'll have the data from Hansard)
                if ($itemdata['event_date'] < date('Y-m-d') &&
                    in_array($itemdata['chamber'], array(
                        'Commons: Main Chamber', 'Lords: Main Chamber',
                        'Commons: Westminster Hall',
                    )))
                        continue;

                list($cal_item, $cal_meta) = calendar_meta($itemdata);
                $body = $this->prepare_search_result_for_display($cal_item) . '.';
                if ($cal_meta) {
                    $body .= ' <span class="future_meta">' . join('; ', $cal_meta) . '</span>';
                }
                if ($itemdata['witnesses']) {
                    $body .= '<br><small>Witnesses: '
                        . $this->prepare_search_result_for_display($itemdata['witnesses'])
                        . '</small>';
                }

                if ($itemdata['event_date'] >= date('Y-m-d')) {
                    $title = 'Upcoming Business';
                } else {
                    $title = 'Previous Business';
                }
                $itemdata['gid']            = $id;
                $itemdata['relevance']      = $relevances[$n];
                $itemdata['parent']['body'] = $title . ' &#8211; ' . $itemdata['chamber'];
                $itemdata['extract']        = $body;
                $itemdata['listurl']        = '/calendar/?d=' . $itemdata['event_date'] . '#cal' . $itemdata['id'];
                $itemdata['major']          = 'F';

            } else {

                // Get the data for the gid from the database
                $q = $this->db->query("SELECT hansard.gid, hansard.hdate,
                    hansard.htime, hansard.section_id, hansard.subsection_id,
                    hansard.htype, hansard.major, hansard.minor,
                    hansard.person_id, hansard.hpos, hansard.video_status,
                    epobject.epobject_id, epobject.body
                FROM hansard, epobject
                WHERE hansard.gid = '$gid'
                    AND hansard.epobject_id = epobject.epobject_id"
                );

                if ($q->rows() > 1)
                    throw new \Exception('Got more than one row getting data for $gid.');
                if ($q->rows() == 0) {
                    # This error message is totally spurious, so don't show it
                    # $PAGE->error_message("Unexpected missing gid $gid while searching");
                    continue;
                }

                $itemdata = $q->row(0);
                $itemdata['collapsed']  = $collapsed;
                $itemdata['gid']        = fix_gid_from_db( $q->field(0, 'gid') );
                $itemdata['relevance']  = $relevances[$n];
                $itemdata['extract']    = $this->prepare_search_result_for_display($q->field(0, 'body'));

                //////////////////////////
                // 2. Create the URL to link to this bit of text.

                $id_data = array (
                    'major'            => $itemdata['major'],
                    'minor'            => $itemdata['minor'],
                    'htype'         => $itemdata['htype'],
                    'gid'             => $itemdata['gid'],
                    'section_id'    => $itemdata['section_id'],
                    'subsection_id'    => $itemdata['subsection_id']
                );

                // We append the query onto the end of the URL as variable 's'
                // so we can highlight them on the debate/wrans list page.
                $url_args = array ('s' => $searchstring);

                $itemdata['listurl'] = $this->_get_listurl($id_data, $url_args, $encode);

                //////////////////////////
                // 3. Get the speaker for this item, if applicable.
                if ($itemdata['person_id'] != 0) {
                    $itemdata['speaker'] = $this->_get_speaker($itemdata['person_id'], $itemdata['hdate'], $itemdata['htime'], $itemdata['major']);
                }

                //////////////////////////
                // 4. Get data about the parent (sub)section.
                if ($itemdata['major'] && $hansardmajors[$itemdata['major']]['type'] == 'debate') {
                    // Debate
                    if ($itemdata['htype'] != 10) {
                        $section = $this->_get_section($itemdata);
                        $itemdata['parent']['body'] = $section['body'];
#                        $itemdata['parent']['listurl'] = $section['listurl'];
                        if ($itemdata['section_id'] != $itemdata['subsection_id']) {
                            $subsection = $this->_get_subsection($itemdata);
                            $itemdata['parent']['body'] .= ': ' . $subsection['body'];
#                            $itemdata['parent']['listurl'] = $subsection['listurl'];
                        }
                        if ($itemdata['major'] == 5) {
                            $itemdata['parent']['body'] = 'Northern Ireland Assembly: ' . $itemdata['parent']['body'];
                        } elseif ($itemdata['major'] == 6) {
                            $itemdata['parent']['body'] = 'Public Bill Committee: ' . $itemdata['parent']['body'];
                        } elseif ($itemdata['major'] == 7) {
                            $itemdata['parent']['body'] = 'Scottish Parliament: ' . $itemdata['parent']['body'];
                        }

                    } else {
                        // It's a section, so it will be its own title.
                        $itemdata['parent']['body'] = $itemdata['body'];
                        $itemdata['body'] = '';
                    }

                } else {
                    // Wrans or WMS
                    $section = $this->_get_section($itemdata);
                    $subsection = $this->_get_subsection($itemdata);
                    $body = $hansardmajors[$itemdata['major']]['title'] . ' &#8212; ';
                    if (isset($section['body'])) $body .= $section['body'];
                    if (isset($subsection['body'])) $body .= ': ' . $subsection['body'];
                    if (isset($subsection['listurl'])) $listurl = $subsection['listurl'];
                    else $listurl = '';
                    $itemdata['parent'] = array (
                        'body' => $body,
                        'listurl' => $listurl
                    );
                    if ($itemdata['htype'] == 11) {
                        # Search result was a subsection heading; fetch the first entry
                        # from the wrans/wms to show under the heading
                        $input = array (
                            'amount' => array(
                                'body' => true,
                                'speaker' => true
                            ),
                            'where' => array(
                                'hansard.subsection_id=' => $itemdata['epobject_id']
                            ),
                            'order' => 'hpos ASC',
                            'limit' => 1
                        );
                        $ddata = $this->_get_hansard_data($input);
                        if (count($ddata)) {
                            $itemdata['body'] = $ddata[0]['body'];
                            $itemdata['extract'] = $this->prepare_search_result_for_display($ddata[0]['body']);
                            $itemdata['person_id'] = $ddata[0]['person_id'];
                            if ($itemdata['person_id']) {
                                $itemdata['speaker'] = $this->_get_speaker($itemdata['person_id'], $itemdata['hdate'], $itemdata['htime'], $itemdata['major']);
                            }
                        }
                    } elseif ($itemdata['htype'] == 10) {
                        $itemdata['body'] = '';
                        $itemdata['extract'] = '';
                    }
                }

            } // End of handling non-calendar search result

            $rows[] = $itemdata;
        }

        $data['rows'] = $rows;
        return $data;
    }

}
