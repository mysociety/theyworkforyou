<?php
# vim:sw=4:ts=4:et:nowrap

namespace MySociety\TheyWorkForYou;

class Search {

    private $search_string;

    function __construct() {
        global $this_page;
        $this_page = 'search';
    }

    public function display() {
        $data = array();
        $this->search_string = $this->construct_search_string();

        if ( !$this->search_string ) {
            $data['searchstring'] = '';
            $data['template'] = 'search/results';
            return $data;
        }

        $this->searchstring = filter_user_input($this->search_string, 'strict');
        $warnings = $this->validate_search_string();
        if ( $warnings ) {
            $data['warnings'] = $warnings;
        } else {
            if (get_http_var('o')=='p') {
                $data = $this->search_order_p($this->search_string);
                $data['template'] = 'search/by-person';
            } else {
                $data = $this->search_normal($this->search_string);
                $data['template'] = 'search/results';
            }
        }

        $data['searchstring'] = $this->search_string;
        $data['pagination_links'] = $this->generate_pagination($data['info']);

        return $data;
    }

    private function validate_search_string() {
        $warning = '';
        if (preg_match('#^\s*[^\s]+\.\.[^\s]+\s*$#', $this->searchstring)) {
            $warning = 'You cannot search for just a date range, please select some other criteria as well.';
        }
        if (preg_match('#\.\..*?\.\.#', $this->searchstring)) {
            $warning = 'You cannot search for more than one date range.';
        }

        return $warning;
    }

    private function construct_search_string() {

        // If q has a value (other than the default empty string) use that over s.

        if (get_http_var('q') != '') {
            $search_main = trim(get_http_var('q'));
        } else {
            $search_main = trim(get_http_var('s'));
        }

        $searchstring = '';

        # Stuff from advanced search form
        if ($advphrase = get_http_var('phrase')) {
            $searchstring .= ' "' . $advphrase . '"';
        }

        if ($advexclude = get_http_var('exclude')) {
            $searchstring .= ' -' . join(' -', preg_split('/\s+/', $advexclude));
        }

        if (get_http_var('from') || get_http_var('to')) {
            $from = parse_date(get_http_var('from'));
            if ($from) $from = $from['iso'];
            else $from = '1935-10-01';
            $to = parse_date(get_http_var('to'));
            if ($to) $to = $to['iso'];
            else $to = date('Y-m-d');
            $searchstring .= " $from..$to";
        }

        if ($advdept = get_http_var('department')) {
            $searchstring .= ' department:' . preg_replace('#[^a-z]#i', '', $advdept);
        }

        if ($advparty = get_http_var('party')) {
            $searchstring .= ' party:' . join(' party:', explode(',', $advparty));
        }

        if ($column = trim(get_http_var('column'))) {
            if (preg_match('#^(\d+)W$#', $column, $m)) {
                $searchstring .= " column:$m[1] section:wrans";
            } elseif (preg_match('#^(\d+)WH$#', $column, $m)) {
                $searchstring .= " column:$m[1] section:whall";
            } elseif (preg_match('#^(\d+)WS$#', $column, $m)) {
                $searchstring .= " column:$m[1] section:wms";
            } elseif (preg_match('#^\d+$#', $column)) {
                $searchstring .= " column:$column";
            }
        }

        $advsection = get_http_var('section');
        if (!$advsection)
            $advsection = get_http_var('maj'); # Old URLs had this
        if (is_array($advsection)) {
            $searchstring .= ' section:' . join(' section:', $advsection);
        } elseif ($advsection) {
            $searchstring .= " section:$advsection";
        }

        if ($searchgroupby = trim(get_http_var('groupby'))) {
            $searchstring .= " groupby:$searchgroupby";
        }

        # Searching from MP pages
        if ($searchspeaker = trim(get_http_var('pid'))) {
            $searchstring .= " speaker:$searchspeaker";
        }

        # Searching from MP pages
        if ($searchspeaker = trim(get_http_var('person'))) {
            $q = search_member_db_lookup($searchspeaker);
            $pids = array();
            for ($i=0; $i<$q->rows(); $i++) {
                $pids[$q->field($i, 'person_id')] = true;
            }
            $pids = array_keys($pids);
            if ($pids)
                $searchstring .= ' speaker:' . join(' speaker:', $pids);
        }

        $searchstring = trim($searchstring);
        if ($search_main && $searchstring) {
            if (strpos($search_main, 'OR') !== false) {
                $search_main = "($search_main)";
            }
        } elseif ($search_main) {
            $searchstring = $search_main;
        }

        twfy_debug('SEARCH', _htmlspecialchars($searchstring));
        return $searchstring;
    }

    private function generate_pagination($data) {
        global $this_page;

        $total_results      = $data['total_results'];
        $results_per_page   = $data['results_per_page'];
        $page               = $data['page'];
        $pagelinks          = array();
        $numlinks           = array();

        if ($total_results > $results_per_page) {

            $numpages = ceil($total_results / $results_per_page);

            // How many links are we going to display on the page - don't want to
            // display all of them if we have 100s...
            if ($page < 10) {
                $firstpage = 1;
                $lastpage = 10;
            } else {
                $firstpage = $page - 4;
                $lastpage = $page + 5;
            }

            if ($firstpage < 1) {
                $firstpage = 1;
            }
            if ($lastpage > $numpages) {
                $lastpage = $numpages;
            }

            $URL = new \URL($this_page);
            $URL->insert(array( 's' => $data['s'] ) );
            for ($n = $firstpage; $n <= $lastpage; $n++) {

                if ($n > 1) {
                    $URL->insert(array('p'=>$n));
                } else {
                    // No page number for the first page.
                    $URL->remove(array('p'));
                }
                if (isset($pagedata['pid'])) {
                    $URL->insert(array('pid'=>$pagedata['pid']));
                }

                $link = array(
                    'url' => $URL->generate(),
                    'page' => $n,
                    'current' => ( $n == $page )
                );

                $numlinks[] = $link;
            }

            $pagelinks['nums'] = $numlinks;
            $pagelinks['first_result'] = $page == 1 ? 1 : ( ( $page - 1 ) * $results_per_page ) + 1;
            $pagelinks['last_result'] = $page == $numpages ? $total_results : $pagelinks['first_result'] + ( $results_per_page - 1 );

            if ( $page != 1 ) {
                $prev_page = $page - 1;
                $URL->insert(array( 'p' => $prev_page ) );
                $pagelinks['prev'] = array(
                    'url' => $URL->generate()
                );
                $URL->insert(array( 'p' => 1 ) );
                $pagelinks['firstpage'] = array(
                    'url' => $URL->generate()
                );
            }
            if ($page != $numpages) {
                $next_page = $page + 1;
                $URL->insert(array( 'p' => $next_page ) );
                $pagelinks['next'] = array(
                    'url' => $URL->generate()
                );
                $URL->insert(array( 'p' => $numpages ) );
                $pagelinks['lastpage'] = array(
                    'url' => $URL->generate()
                );
            }
        }

        return $pagelinks;
    }

    private function search_normal($searchstring) {
        global $PAGE, $DATA, $this_page, $SEARCHENGINE;

        $SEARCHENGINE = new \SEARCHENGINE($searchstring);
        $qd = $SEARCHENGINE->valid ? $SEARCHENGINE->query_description_short() : $searchstring;
        $pagetitle = 'Search for ' . $qd;
        $pagenum = get_http_var('p');
        if (!is_numeric($pagenum)) {
            $pagenum = 1;
        }
        if ($pagenum > 1) {
            $pagetitle .= ", page $pagenum";
        }

        $DATA->set_page_metadata($this_page, 'title', $pagetitle);
        $DATA->set_page_metadata($this_page, 'rss', 'search/rss/?s=' . urlencode($searchstring));
        if ($pagenum == 1) {
            # Allow indexing of first page of search results
            $DATA->set_page_metadata($this_page, 'robots', '');
        }
        //$PAGE->page_start();
        //$PAGE->stripe_start();
        //$PAGE->search_form($searchstring);

        $o = get_http_var('o');
        $args = array (
            's' => $searchstring,
            'p' => $pagenum,
            'num' => get_http_var('num'),
            'pop' => get_http_var('pop'),
            'o' => ($o=='d' || $o=='r' || $o=='o') ? $o : 'd',
        );

        if ($pagenum == 1 && $args['s'] && !preg_match('#[a-z]+:[a-z0-9]+#', $args['s'])) {
            $members = $this->find_members($args['s']);
            //$this->find_constituency($args);
            //$this->find_glossary_items($args);
        }

        if (!defined('FRONT_END_SEARCH') || !FRONT_END_SEARCH) {
            print '<p>Apologies, search has been turned off currently for performance reasons.</p>';
        }

        if (!$SEARCHENGINE->valid) {
            $PAGE->error_message($SEARCHENGINE->error);
        } else {
            $LIST = new \HANSARDLIST();
            $data = $LIST->display('search', $args , 'none');
            $data['members'] = $members;
            return $data;
        }
    }

    private function search_order_p($searchstring) {
        global $DATA, $PAGE, $this_page;

        $q_house = '';
        if (ctype_digit(get_http_var('house')))
            $q_house = get_http_var('house');

        # Fetch the results
        $data = search_by_usage($searchstring, $q_house);

        $wtt = get_http_var('wtt');
        if ($wtt) {
            $pagetitle = 'League table of Lords who say ' . $data['pagetitle'];
        } else {
            $pagetitle = 'Who says ' . $data['pagetitle'] . ' the most?';
        }
        $DATA->set_page_metadata($this_page, 'title', $pagetitle);
        $PAGE->page_start();
        $PAGE->stripe_start();
        $PAGE->search_form($searchstring);
        if (isset($data['error'])) {
            print '<p>' . $data['error'] . '</p>';
            return;
        }

        if (isset($data['limit_reached'])) {
            print '<p><em>This service runs on a maximum number of 5,000 results, to conserve memory</em></p>';
        }
        print "\n\n<!-- ";
        foreach ($data['party_count'] as $party => $count) {
            print "$party:$count<br>";
        }
        print " -->\n\n";
        if ($wtt) { ?>
    <p><strong><big>Now, try reading what a couple of these Lords are saying,
    to help you find someone appropriate. When you've found someone,
    hit the "I want to write to this Lord" button on their results page
    to go back to WriteToThem.
    </big></strong></p>
    <?php
        }
    ?>
    <p>Please note that this search is only for the exact word/phrase entered.
    For example, putting in 'autism' won't return results for 'autistic spectrum disorder',
    you will have to search for it separately.</p>
    <table><tr><th>Number of occurences</th><th><?php

        if ($wtt) print 'Speaker';
        else {
    ?>Table includes - <?php

            $URL = new URL($this_page);
            $url_l = $URL->generate('html', array('house'=>2));
            $url_c = $URL->generate('html', array('house'=>1));
            $URL->remove(array('house'));
            $url_b = $URL->generate();
            if ($q_house==1) {
                print 'MPs | <a href="' . $url_l . '">Lords</a> | <a href="' . $url_b . '">Both</a>';
            } elseif ($q_house==2) {
                print '<a href="' . $url_c . '">MPs</a> | Lords | <a href="' . $url_b . '">Both</a>';
            } else {
                print '<a href="' . $url_c . '">MPs</a> | <a href="' . $url_l . '">Lords</a> | Both';
            }

    } ?></th><th>Date range</th></tr>
    <?php
        foreach ($data['speakers'] as $pid => $speaker) {
            print '<tr><td align="center">';
            print $speaker['count'] . '</td><td>';
            if ($pid) {
                $house = $speaker['house'];
                $left = $speaker['left'];
                if ($house==1) {
                    print '<span style="color:#009900">&bull;</span> ';
                } elseif ($house==2) {
                    print '<span style="color:#990000">&bull;</span> ';
                }
                if (!$wtt || $left == '9999-12-31')
                    print '<a href="' . WEBPATH . 'search/?s='.urlencode($searchstring).'&amp;pid=' . $pid;
                if ($wtt && $left == '9999-12-31')
                    print '&amp;wtt=2';
                if (!$wtt || $left == '9999-12-31')
                    print '">';
            }
            print $speaker['name'];
            if ($pid) print '</a>';
            if ($speaker['party']) print ' (' . $speaker['party'] . ')';
            if (isset($speaker['office']))
                print ' - ' . join('; ', $speaker['office']);
            print '</td> <td>';
            $pmindate = $speaker['pmindate'];
            $pmaxdate = $speaker['pmaxdate'];
            if (format_date($pmindate, 'M Y') == format_date($pmaxdate, 'M Y')) {
                print format_date($pmindate, 'M Y');
            } else {
                print str_replace(' ', '&nbsp;', format_date($pmindate, 'M Y') . ' &ndash; ' . format_date($pmaxdate, 'M Y'));
            }
            print '</td></tr>';
        }
        print '</table>';
    }

private function find_constituency($args) {
    // We see if the user is searching for a postcode or constituency.
    global $PAGE;

    if ($args['s'] != '') {
        $searchterm = $args['s'];
    } else {
        $PAGE->error_message('No search string');

        return false;
    }

    list ($constituencies, $validpostcode) = search_constituencies_by_query($searchterm);

    $constituency = "";
    if (count($constituencies)==1) {
        $constituency = $constituencies[0];
    }

    if ($constituency != '') {
        // Got a match, display....

        $MEMBER = new Member(array('constituency'=>$constituency, 'house' => 1));
        $URL = new \URL('mp');
        /*
        if ($MEMBER->valid) {
            $URL->insert(array('p'=>$MEMBER->person_id()));
            print '<h2>';
            if (!$MEMBER->current_member(1)) {
                print 'Former ';
            }
            print 'MP for ' . preg_replace('#' . preg_quote($searchterm, '#') . '#i', '<span class="hi">$0</span>', $constituency);
            if ($validpostcode) {
                // Display the postcode the user searched for.
                print ' (' . _htmlentities(strtoupper($args['s'])) . ')';
            }
            ?></h2>

            <p><a href="<?php echo $URL->generate(); ?>"><strong><?php echo $MEMBER->full_name(); ?></strong></a> (<?php echo $MEMBER->party_text(); ?>)</p>
    <?php
        }
         */

    } elseif (count($constituencies)) {
        $out = '';
        $heading = array();
        foreach ($constituencies as $constituency) {
            $MEMBER = new Member(array('constituency'=>$constituency, 'house' => 1));
            if ($MEMBER->valid) {
                if ($MEMBER->current_member(1)) {
                    $heading[] = 'MPs';
                } else {
                    $heading[] = 'Former MPs';
                }
                $URL = new URL('mp');
                $URL->insert(array('p' => $MEMBER->person_id()));
                $out .= '<li><a href="'.$URL->generate().'"><strong>' . $MEMBER->full_name() .
                    '</strong></a> (' . preg_replace('#' . preg_quote($searchterm, '#') . '#i', '<span class="hi">$0</span>', $constituency) .
                    ', '.$MEMBER->party().')</li>';
            }
        }
        /*
        print '<h2>';
        print join(" and ", array_unique($heading));
        print " in constituencies matching &lsquo;" . _htmlentities($searchterm) . "&rsquo;</h2>";
        print "<ul>$out</ul>";
         */
    }
}

    private function find_members($searchstring) {
        $searchstring = trim(preg_replace('#-?[a-z]+:[a-z0-9]+#', '', $searchstring));
        $q = search_member_db_lookup($searchstring);
        if (!$q) return array();

        $members = array();
        if ($q->rows() > 0) {
            $URL1 = new \URL('mp');
            $URL2 = new \URL('peer');

            $last_pid = null;
            $entered_house = '';
            for ($n=0; $n<$q->rows(); $n++) {
                $member = new Member(array('person_id' => $q->field($n, 'person_id')));
                $members[] = $member;
            }
        }

        return $members;
    }

// Checks to see if the search term provided has any similar matching entries in the glossary.
// If it does, show links off to them.
private function find_glossary_items($args) {
    $searchterm = $args['s'];
    $GLOSSARY = new \GLOSSARY($args);

    if (isset($GLOSSARY->num_search_matches) && $GLOSSARY->num_search_matches >= 1) {

        // Got a match(es), display....
        $URL = new URL('glossary');
        $URL->insert(array('gl' => ""));
?>
                <h2>Matching glossary terms:</h2>
                <p><?php
        $n = 1;
        foreach ($GLOSSARY->search_matches as $glossary_id => $term) {
            $URL->update(array("gl" => $glossary_id));
            ?><a href="<?php echo $URL->generate(); ?>"><strong><?php echo _htmlentities($term['title']); ?></strong></a><?php
            if ($n < $GLOSSARY->num_search_matches) {
                print ", ";
            }
            $n++;
        }
        ?></p>
<?php
    }
}

# ---

}
