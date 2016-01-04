<?php
# vim:sw=4:ts=4:et:nowrap

namespace MySociety\TheyWorkForYou;

class Search {

    protected $searchstring;
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
                $data['template'] = 'search/results';
            }
        }

        if ( isset($data['info']['spelling_correction']) ) {
            $data['info']['spelling_correction_display'] = $this->prettifySearchString($data['info']['spelling_correction']);
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
        $url->insert(array('q' => $this->searchstring));
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
        foreach ( array('to', 'from', 'person', 'section', 'column', 'phrase', 'exclude' ) as $var ) {
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

        $data['show_advanced_options'] = false;

        if (get_http_var('show_advanced_options')) {
            $data['show_advanced_options'] = true;
        }

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

    protected function get_search_url($params = true) {
        global $this_page;

        $url = new \URL($this_page);

        if (isset($this->searchstring)) {
            $value = $this->searchstring;
            if (preg_match_all('#speaker:(\d+)#', $value, $m) == 1) {
                $person_id = $m[1][0];
                $value = str_replace('speaker:' . $person_id, '', $value);
                $url->insert(array('pid' => $person_id));
                }
            $url->insert(array('q' => $value));
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

    private function prettifySearchString($string) {
        $string = Utility\Search::speakerIDsToNames($string);

        return $string;
    }
}
