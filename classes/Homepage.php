<?php

namespace MySociety\TheyWorkForYou;

class Homepage {

    private $db;

    public function __construct() {
        $this->db = new \ParlDB;
    }

    function display() {
        global $this_page;
        $this_page = 'overview';

        $data = array();

        $data['debates'] = $this->getDebatesData();
        $data['mp_data'] = $this->getMP();
        $data['popular_searches'] = $this->getPopularSearches();
        $data['urls'] = $this->getURLs();
        $data['calendar'] = $this->getCalendarData();
        $data['featured'] = $this->getEditorialContent();

        return $data;
    }

    function getMP() {
        $mp_url = new \URL('yourmp');
        $mp_data = array();
        global $THEUSER;

        if ($THEUSER->isloggedin() && $THEUSER->postcode() != '' || $THEUSER->postcode_is_set()) {
            // User is logged in and has a postcode, or not logged in with a cookied postcode.

            // (We don't allow the user to search for a postcode if they
            // already have one set in their prefs.)

            $MEMBER = new MEMBER(array ('postcode'=>$THEUSER->postcode(), 'house'=>1));
            if ($MEMBER->valid) {
                $pc_form = false;
                if ($THEUSER->isloggedin()) {
                    $CHANGEURL = new \URL('useredit');
                } else {
                    $CHANGEURL = new \URL('userchangepc');
                }
                $mp_data['name'] = $MEMBER->first_name() . ' ' . $MEMBER->last_name();
                $former = "";
                $left_house = $MEMBER->left_house();
                $mp_data['former'] = '';
                if ($left_house[1]['date'] != '9999-12-31') {
                    $mp_data['former'] = 'former';
                }
                $mp_data['postcode'] = $THEUSER->postcode();
                $mp_data['mp_url'] = $mp_url->generate();
                $mp_data['change_url'] = $CHANGEURL->generate();
            }
        }

        return $mp_data;
    }

    function getEditorialContent() {
        $debatelist = new \DEBATELIST;
        $featured = new Model\Featured;
        $gid = $featured->get_gid();
        if ( $gid ) {
            $title = $featured->get_title();
            $related = $featured->get_related();
            $item = $this->getFeaturedDebate($gid, $title, $related);
        } else {
            $item = $debatelist->display('recent_debates', array('days' => 7, 'num' => 1), 'none');
            $item = $item['data'][0];
            $more_url = new \URL('debates');
            $item['more_url'] = $more_url->generate();
            $item['desc'] = 'Commons Debates';
            $item['related'] = array();
            $item['featured'] = false;
        }

        return $item;
    }

    public function getFeaturedDebate($gid, $title, $related) {
        $debatelist = new \DEBATELIST;

        $item = $debatelist->display('featured_gid', array('gid' => $gid), 'none');
        $item = $item['data'];
        $item['headline'] = $title;
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

    function getURLs() {
        $urls = array();

        $search = new \URL('search');
        $urls['search'] = $search->generate();

        $alert = new \URL('alert');
        $urls['alert'] = $alert->generate();

        return $urls;
    }

    function getDebatesData() {
        $debates = array(); // holds the most recent data there is data for, indexed by type
        $DEBATELIST = new \DEBATELIST;
        $LORDSDEBATELIST = new \LORDSDEBATELIST;
        $WHALLLIST = new \WHALLLIST;
        $WMSLIST = new \WMSLIST;
        $WRANSLIST = new \WRANSLIST;
        $COMMITTEE = new \StandingCommittee();
        $last_dates[1] = $DEBATELIST->most_recent_day();
        $last_dates[101] = $LORDSDEBATELIST->most_recent_day();
        $last_dates[4] = $WMSLIST->most_recent_day();
        $last_dates[2] = $WHALLLIST->most_recent_day();
        $last_dates[3] = $WRANSLIST->most_recent_day();
        $last_dates[6] = $COMMITTEE->most_recent_day();

        $debates['last_dates'] = $last_dates;

        $recent_wrans = $WRANSLIST->display('recent_wrans', array('days' => 7, 'num' => 1), 'none');
        $debates['recent_wrans'] = $recent_wrans['data'][0];
        $more_wrans = new \URL('wransfront');
        $debates['more_wrans'] = $more_wrans->generate();

        $recent_content = array();
        $classes = array(
            'DEBATELIST' => array('recent_debates', 'debatesfront', 'Commons debates'),
            'LORDSDEBATELIST' => array('recent_debates', 'lordsdebatesfront', 'Lords debates'),
            'WHALLLIST' => array('recent_debates', 'whallfront', 'Westminster Hall debates'),
            'WMSLIST' => array('recent_wms', 'wmsfront', 'Written ministerial statements'),
            'WRANSLIST' => array('recent_wrans', 'wransfront', 'Written answers'),
            'StandingCommittee' => array('recent_pbc_debates', 'pbcfront', 'Public Bill committees')
        );

        foreach ( $classes as $class => $recent ) {
            $class = "\\$class";
            $instance = new $class();
            $content = $instance->display($recent[0], array('days' => 7, 'num' => 1), 'none');
            $more_url = new \URL($recent[1]);
            $content['data'][0]['more_url'] = $more_url->generate();
            $content['data'][0]['desc'] = $recent[2];
            if ( $recent[0] == 'recent_pbc_debates' ) {
                $content = array( 'data' => $instance->display($recent[0], array('num' => 5), 'none') );
                $content['more_url'] = $more_url->generate();
                $content['desc'] = $recent[2];
                $recent_content[] = $content;
            } else {
                $recent_content[] = $content['data'][0];
            }
        }

        $debates['recent'] = $recent_content;

        return $debates;
    }

    function getPopularSearches() {
        global $SEARCHLOG;
        $popular_searches = $SEARCHLOG->popular_recent(10);

        return $popular_searches;
    }

    function getCalendarData() {
        $date = date('Y-m-d');
        $date = '2013-03-01';
        $q = $this->db->query("SELECT * FROM future
            LEFT JOIN future_people ON future.id = future_people.calendar_id AND witness = 0
            WHERE event_date >= :date
            AND deleted = 0
            ORDER BY chamber, pos",
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
