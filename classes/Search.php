<?php
# vim:sw=4:ts=4:et:nowrap

namespace MySociety\TheyWorkForYou;

class Search {

    private $searchstring;
    private $searchkeyword;

    public function __construct() {
        global $this_page;
        $this_page = 'search';
    }

    public function display() {
        $data = array();
        $argparser = new Search\ParseArgs();
        $this->searchstring = $argparser->construct_search_string();
        $this->searchkeyword = $argparser->searchkeyword;

        if ( !$this->searchstring ) {
            $data = $this->get_form_params($data);
            $data['searchstring'] = '';
            $data['template'] = 'search/results';
            return $data;
        }

        $this->searchstring = filter_user_input($this->searchstring, 'strict');
        $warnings = $this->validate_search_string();
        if ( $warnings ) {
            $data['warnings'] = $warnings;
            $data['template'] = 'search/results';
            $data['searchstring'] = $this->searchstring;
            $data = $this->get_form_params($data);
            return $data;
        } else {
            if (get_http_var('o')=='p') {
                $search = new Search\ByUsage();
                $data = $search->search($this->searchstring);
                $data['template'] = 'search/by-person';
            } else {
                $search = new Search\Normal();
                $data = $search->search($this->searchstring);
                $data['pagination_links'] = $this->generate_pagination($data['info']);
                $data['template'] = 'search/results';
                $data['search_sidebar'] = $this->get_sidebar_links($this->searchstring);
            }
        }

        $data['searchstring'] = $this->searchstring;
        $data['urls'] = $this->get_urls();
        $data['this_url'] = $this->get_search_url();
        $data['ungrouped_url'] = $this->get_search_url(false);
        $data = $this->get_form_params($data);
        $data = $this->set_wtt_options($data);
        $this->set_page_title($data);

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

    private function prettify_search_section($section) {
        $name = '';
        switch ($section) {
        case 'wrans':
            $name = 'Written Answers';
            break;
        case 'uk':
            $name = 'All UK';
            break;
        case 'debates':
            $name = 'House of Commons debates';
            break;
        case 'whall':
            $name = 'Westminster Hall debates';
            break;
        case 'lords':
            $name = 'House of Lords debates';
            break;
        case 'wms':
            $name = 'Written ministerial statements';
            break;
        case 'standing':
            $name = 'Bill Committees';
            break;
        case 'future':
            $name = 'Future Business';
            break;
        case 'ni':
            $name = 'Northern Ireland Assembly Debates';
            break;
        case 'scotland':
            $name = 'All Scotland';
            break;
        case 'sp':
            $name = 'Scottish Parliament Debates';
            break;
        case 'spwrans':
            $name = 'Scottish Parliament Written answers';
            break;
        }

        return $name;
    }

    private function get_urls() {
        global $this_page;
        $urls = array();

        $url = new \URL($this_page);
        $url->insert(array('s' => $this->searchstring));
        $url->insert(array('o' => 'r'));
        $urls['relevance'] = $url->generate();
        $url->insert(array('o' => 'o'));
        $urls['oldest'] = $url->generate();
        $url->insert(array('o' => 'd'));
        $urls['newest'] = $url->generate();
        $url->insert(array('o' => 'p'));
        $urls['by-person'] = $url->generate();

        return $urls;
    }

    private function get_form_params($data) {
        $data['search_keyword'] = $this->searchkeyword;

        $is_adv = false;
        foreach ( array('to', 'from', 'person', 'section', 'column' ) as $var ) {
            $key = "search_$var";
            $data[$key] = get_http_var( $var );
            if ( $data[$key] ) {
                $is_adv = true;
            }
        }

        if ( isset($data['search_section']) ) {
            $data['search_section_pretty'] = $this->prettify_search_section($data['search_section']);
        }

        $data['is_adv'] = $is_adv;
        return $data;
    }

    private function set_wtt_options($data) {
        $data['wtt'] = '';
        if ( $wtt = get_http_var('wtt') ) {
            $data['wtt'] = $wtt;
            if ( $wtt == 2 && $pid = get_http_var('pid') ) {
                $data['pid'] = null;
                try {
                    $lord = new Member(array('person_id' => $pid, 'house' => 2));
                } catch ( MemberException $e ) {
                    return $data;
                }
                if ( $lord->valid ) {
                    $data['pid'] = $pid;
                    $data['wtt_lord_name'] = $lord->full_name();
                }
            }
        }

        return $data;
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

    private function get_search_url($params = true) {
        global $this_page;

        $url = new \URL($this_page);

        if (isset($this->searchstring)) {
            $value = $this->searchstring;
            if (preg_match_all('#speaker:(\d+)#', $value, $m) == 1) {
                $person_id = $m[1][0];
                $value = str_replace('speaker:' . $person_id, '', $value);
                $url->insert(array('pid' => $person_id));
                }
            $url->insert(array('s' => $value));
        }

        if ( $params ) {
            if ( get_http_var('house') ) {
                $url->insert(array('house' => get_http_var('house')));
            }
            if ( get_http_var('wtt') ) {
                $url->insert(array('wtt' => get_http_var('wtt')));
            }
        } else {
            $url->remove(array('o', 'house'));
        }

        return $url;
    }

    private function set_page_title($data) {
        global $DATA, $this_page;

        $pagetitle = '';
        if ( isset($data['search_type']) && $data['search_type'] == 'person' ) {
            if (isset($data['wtt']) && $data['wtt'] > 0) {
                $pagetitle = 'League table of Lords who say ' . $data['pagetitle'];
            } else {
                $pagetitle = 'Who says ' . $data['pagetitle'] . ' the most?';
            }
        } else {
            $pagetitle = 'Search for ' . $data['searchdescription'];
            if (isset($data['info']['page']) && $data['info']['page'] > 1) {
                $pagetitle .= ", page " . $data['info']['page'];
            }
        }
        $DATA->set_page_metadata($this_page, 'title', $pagetitle);
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

}
