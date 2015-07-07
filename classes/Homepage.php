<?php

namespace MySociety\TheyWorkForYou;

class Homepage {

    private $db;

    protected $mp_house = 1;
    protected $cons_type = 'WMC';
    protected $mp_url = 'yourmp';
    protected $page = 'overview';

    protected $recent_types = array(
        'DEBATELIST' => array('recent_debates', 'debatesfront', 'Commons debates'),
        'LORDSDEBATELIST' => array('recent_debates', 'lordsdebatesfront', 'Lords debates'),
        'WHALLLIST' => array('recent_debates', 'whallfront', 'Westminster Hall debates'),
        'WMSLIST' => array('recent_wms', 'wmsfront', 'Written ministerial statements'),
        'WRANSLIST' => array('recent_wrans', 'wransfront', 'Written answers'),
        'StandingCommittee' => array('recent_pbc_debates', 'pbcfront', 'Public Bill committees')
    );

    public function __construct() {
        $this->db = new \ParlDB;
    }

    public function display() {
        global $this_page;
        $this_page = $this->page;

        $data = array();

        $data['debates'] = $this->getDebatesData();

        $user = new User();
        $data['mp_data'] = $user->getMP($this->mp_url, $this->cons_type, $this->mp_house);

        $data['regional'] = $this->getRegionalList();
        $data['popular_searches'] = $this->getPopularSearches();
        $data['urls'] = $this->getURLs();
        $data['calendar'] = $this->getCalendarData();
        $data['featured'] = $this->getEditorialContent();

        return $data;
    }

    protected function getRegionalList() {
        return NULL;
    }

    protected function getEditorialContent() {
        $debatelist = new \DEBATELIST;
        $featured = new Model\Featured;
        $gid = $featured->get_gid();
        if ( $gid ) {
            $title = $featured->get_title();
            $context = $featured->get_context();
            $related = $featured->get_related();
            $item = $this->getFeaturedDebate($gid, $title, $context, $related);
        } else {
            $item = $debatelist->display('recent_debates', array('days' => 7, 'num' => 1), 'none');
            if ( isset($item['data']) && count($item['data']) ) {
                $item = $item['data'][0];
                $more_url = new \URL('debates');
                $item['more_url'] = $more_url->generate();
                $item['desc'] = 'Commons Debates';
                $item['related'] = array();
                $item['featured'] = false;
            } else {
                $item = array();
            }
        }

        return $item;
    }

    public function getFeaturedDebate($gid, $title, $context, $related) {
        if (strpos($gid, 'lords') !== false) {
            $debatelist = new \LORDSDEBATELIST;
        } else {
            $debatelist = new \DEBATELIST;
        }

        $item = $debatelist->display('featured_gid', array('gid' => $gid), 'none');
        $item = $item['data'];
        $item['headline'] = $title;
        $item['context'] = $context;
        $item['featured'] = true;

        $related_debates = array();
        foreach ( $related as $related_gid ) {
            if ( $related_gid ) {
                $related_item = $debatelist->display('featured_gid', array('gid' => $related_gid), 'none');
                $related_debates[] = $related_item['data'];
            }
        }
        $item['related'] = $related_debates;
        return $item;
    }

    protected function getURLs() {
        $urls = array();

        $search = new \URL('search');
        $urls['search'] = $search->generate();

        $alert = new \URL('alert');
        $urls['alert'] = $alert->generate();

        return $urls;
    }

    protected function getDebatesData() {
        $debates = array(); // holds the most recent data there is data for, indexed by type

        $recent_content = array();

        foreach ( $this->recent_types as $class => $recent ) {
            $class = "\\$class";
            $instance = new $class();
            $more_url = new \URL($recent[1]);
            if ( $recent[0] == 'recent_pbc_debates' ) {
                $content = array( 'data' => $instance->display($recent[0], array('num' => 5), 'none') );
            } else {
                $content = $instance->display($recent[0], array('days' => 7, 'num' => 1), 'none');
                if ( isset($content['data']) && count($content['data']) ) {
                    $content = $content['data'][0];
                } else {
                    $content = array();
                }
            }
            if ( $content ) {
                $content['more_url'] = $more_url->generate();
                $content['desc'] = $recent[2];
                $recent_content[] = $content;
            }
        }

        $debates['recent'] = $recent_content;

        return $debates;
    }

    protected function getPopularSearches() {
        global $SEARCHLOG;
        $popular_searches = $SEARCHLOG->popular_recent(10);

        return $popular_searches;
    }

    private function getCalendarData() {
        $date = date('Y-m-d');
        $q = $this->db->query("SELECT * FROM future
            LEFT JOIN future_people ON future.id = future_people.calendar_id AND witness = 0
            WHERE event_date >= :date
            AND deleted = 0
            ORDER BY event_date, chamber, pos",
            array( ':date' => $date )
        );

        if (!$q->rows()) {
            return array();
        }

        $data = array();
        foreach ($q->data as $row) {
            $data[$row['event_date']][$row['chamber']][] = $row;
        }

        return $data;
    }

}
