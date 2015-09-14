<?php
# vim:sw=4:ts=4:et:nowrap

namespace MySociety\TheyWorkForYou\Search;

class Normal extends \MySociety\TheyWorkForYou\Search {

    private $searchstring;

    private function get_sort_args() {
        $pagenum = get_http_var('p');
        if (!is_numeric($pagenum)) {
            $pagenum = 1;
        }

        $o = get_http_var('o');
        $args = array (
            's' => $this->searchstring,
            'p' => $pagenum,
            'num' => get_http_var('num'),
            'pop' => get_http_var('pop'),
            'o' => ($o=='d' || $o=='r' || $o=='o') ? $o : 'd',
        );

        return $args;
    }

    private function get_first_page_data($args) {
        $members = null;
        $cons = null;
        $glossary = null;

        $mp_types = array();
        if ($args['p'] == 1 && $args['s'] && !preg_match('#[a-z]+:[a-z0-9]+#', $args['s'])) {
            $members = $this->find_members();
            list($cons, $mp_types) = $this->find_constituency($args);
            $glossary = $this->find_glossary_items($args);
        }

        return array($members, $cons, $mp_types, $glossary);
    }

    public function search($searchstring) {
        global $DATA, $this_page, $SEARCHENGINE;

        $this->searchstring = $searchstring;

        $SEARCHENGINE = new \SEARCHENGINE($this->searchstring);

        $args = $this->get_sort_args();

        $pagenum = $args['p'];

        $DATA->set_page_metadata($this_page, 'rss', '/search/rss/?s=' . urlencode($this->searchstring));
        if ($pagenum == 1) {
            # Allow indexing of first page of search results
            $DATA->set_page_metadata($this_page, 'robots', '');
        }

        $sort_order = 'newest';
        if ( $args['o'] == 'o' ) {
            $sort_order = 'oldest';
        } else if ( $args['o'] == 'r' ) {
            $sort_order = 'relevance';
        }

        list($members, $cons, $mp_types, $glossary) = $this->get_first_page_data($args);

        if (!defined('FRONT_END_SEARCH') || !FRONT_END_SEARCH) {
            return array(
                'error' =>'Apologies, search has been turned off currently for performance reasons.'
            );
        }

        if (!$SEARCHENGINE->valid) {
            return array('error' => $SEARCHENGINE->error);
        } else {
            $LIST = new \HANSARDLIST();
            $data = $LIST->display('search', $args , 'none');
            $data['search_type'] = 'normal';
            $data['sort_order'] = $sort_order;
            $data['members'] = $members;
            $data['cons'] = $cons;
            $data['mp_types'] = $mp_types;
            $data['glossary'] = $glossary;
            $data['pagination_links'] = $this->generate_pagination($data['info']);
            $data['search_sidebar'] = $this->get_sidebar_links($this->searchstring);
            return $data;
        }
    }

    private function find_constituency($args) {
        if ($args['s'] != '') {
            $searchterm = $args['s'];
        } else {
            return false;
        }

        list ($constituencies, ) = \MySociety\TheyWorkForYou\Utility\Search::searchConstituenciesByQuery($searchterm);

        $constituency = "";
        if (count($constituencies)==1) {
            $constituency = $constituencies[0];
        }

        $cons = array();
        $mp_types = array(
            'mp' => 0,
            'former' => 0
        );

        if ($constituency != '') {
            try {
            // Got a match, display....

                $MEMBER = new \MySociety\TheyWorkForYou\Member(array('constituency'=>$constituency, 'house' => 1));
                $cons[] = $MEMBER;
                if ( $MEMBER->current_member(1) ) {
                    $mp_types['mp']++;
                } else {
                    $mp_types['former']++;
                }
            } catch ( \MySociety\TheyWorkForYou\MemberException $e ) {
                $cons = array();
            }
        } elseif (count($constituencies)) {
            foreach ($constituencies as $constituency) {
                try {
                    $MEMBER = new \MySociety\TheyWorkForYou\Member(array('constituency'=>$constituency, 'house' => 1));
                    $cons[] = $MEMBER;
                    if ( $MEMBER->current_member(1) ) {
                        $mp_types['mp']++;
                    } else {
                        $mp_types['former']++;
                    }
                } catch ( \MySociety\TheyWorkForYou\MemberException $e ) {
                    continue;
                }
            }
        }

        return array($cons, $mp_types);
    }

    private function find_members() {
        $searchstring = trim(preg_replace('#-?[a-z]+:[a-z0-9]+#', '', $this->searchstring));
        $q = \MySociety\TheyWorkForYou\Utility\Search::searchMemberDbLookup($searchstring);
        if (!$q) return array();

        $members = array();
        if ($q->rows() > 0) {
            $row_count = $q->rows();
            for ($n=0; $n<$row_count; $n++) {
                $member = new \MySociety\TheyWorkForYou\Member(array('person_id' => $q->field($n, 'person_id')));
                // searchMemberDbLookup returns dups so we
                // key by person_id to work round this
                $members[$member->person_id] = $member;
            }
        }

        return $members;
    }

    private function find_glossary_items($args) {
        $GLOSSARY = new \GLOSSARY($args);
        $items = array();

        if (isset($GLOSSARY->num_search_matches) && $GLOSSARY->num_search_matches >= 1) {
            $URL = new \URL('glossary');
            $URL->insert(array('gl' => ""));
            foreach ($GLOSSARY->search_matches as $glossary_id => $term) {
                $URL->update(array("gl" => $glossary_id));
                $items[] = array(
                    'url' => $URL->generate(),
                    'term' => $term['title'],
                    'body' => $term['body']
                );
            }
        }
        return $items;
    }

    private function generate_pagination_links($data, $url, $first, $last) {
        $links = array();

        for ($n = $first; $n <= $last; $n++) {

            if ($n > 1) {
                $url->insert(array('p'=>$n));
            } else {
                // No page number for the first page.
                $url->remove(array('p'));
            }

            $link = array(
                'url' => $url->generate(),
                'page' => $n,
                'current' => ( $n == $data['page'] )
            );

            $links[] = $link;
        }

        return $links;
    }

    private function generate_pagination($data) {
        $total_results      = $data['total_results'];
        $results_per_page   = $data['results_per_page'];
        $page               = $data['page'];
        $pagelinks          = array();

        $URL = $this->get_search_url($data);

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

            $numlinks = $this->generate_pagination_links($data, $URL, $firstpage, $lastpage);

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

    private function get_sidebar_links() {
        global $DATA, $SEARCHENGINE, $this_page;

        $links = array();
        $links['rss'] = $DATA->page_metadata($this_page, 'rss');

        if ($SEARCHENGINE) {
            $links['email'] = '/alert/?' . ($this->searchstring ? 'alertsearch='.urlencode($this->searchstring) : '');
            $links['email_desc'] = $SEARCHENGINE->query_description_long();
        }

        $filter_ss = $this->searchstring;
        $section = get_http_var('section');
        if (preg_match('#\s*section:([a-z]*)#', $filter_ss, $m)) {
            $section = $m[1];
            $filter_ss = preg_replace("#\s*section:$section#", '', $filter_ss);
        }
        if ($section && $filter_ss) {
            $search_engine = new \SEARCHENGINE($filter_ss);
            $links['email_section'] = $links['email'];
            $links['email_desc_section'] = $links['email_desc'];
            $links['email'] = '/alert/?' . ($filter_ss ? 'alertsearch='.urlencode($filter_ss) : '');
            $links['email_desc'] = $search_engine->query_description_long();
        }

        return $links;
    }
}
