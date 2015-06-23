<?php
# vim:sw=4:ts=4:et:nowrap

namespace MySociety\TheyWorkForYou\Search;

class Normal {

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

        if ($args['p'] == 1 && $args['s'] && !preg_match('#[a-z]+:[a-z0-9]+#', $args['s'])) {
            $members = $this->find_members();
            $cons = $this->find_constituency($args);
            $glossary = $this->find_glossary_items($args);
        }

        return array($members, $cons, $glossary);
    }

    public function search($searchstring) {
        global $DATA, $this_page, $SEARCHENGINE;

        $this->searchstring = $searchstring;

        $SEARCHENGINE = new \SEARCHENGINE($this->searchstring);

        $args = $this->get_sort_args();

        $pagenum = $args['p'];

        $DATA->set_page_metadata($this_page, 'rss', 'search/rss/?s=' . urlencode($this->searchstring));
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

        list($members, $cons, $glossary) = $this->get_first_page_data($args);

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
            $data['glossary'] = $glossary;
            return $data;
        }
    }

    private function find_constituency($args) {
        if ($args['s'] != '') {
            $searchterm = $args['s'];
        } else {
            return false;
        }

        list ($constituencies, ) = search_constituencies_by_query($searchterm);

        $constituency = "";
        if (count($constituencies)==1) {
            $constituency = $constituencies[0];
        }

        $cons = array();
        if ($constituency != '') {
            try {
            // Got a match, display....

                $MEMBER = new \MySociety\TheyWorkForYou\Member(array('constituency'=>$constituency, 'house' => 1));
                $cons[] = $MEMBER;
            } catch ( \MySociety\TheyWorkForYou\MemberException $e ) {
                $cons = array();
            }
        } elseif (count($constituencies)) {
            foreach ($constituencies as $constituency) {
                try {
                    $MEMBER = new \MySociety\TheyWorkForYou\Member(array('constituency'=>$constituency, 'house' => 1));
                    $cons[] = $MEMBER;
                } catch ( \MySociety\TheyWorkForYou\MemberException $e ) {
                    continue;
                }
            }
        }

        return $cons;
    }

    private function find_members() {
        $searchstring = trim(preg_replace('#-?[a-z]+:[a-z0-9]+#', '', $this->searchstring));
        $q = search_member_db_lookup($searchstring);
        if (!$q) return array();

        $members = array();
        if ($q->rows() > 0) {
            $row_count = $q->rows();
            for ($n=0; $n<$row_count; $n++) {
                $member = new \MySociety\TheyWorkForYou\Member(array('person_id' => $q->field($n, 'person_id')));
                // search_member_db_lookup returns dups so we
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

}
