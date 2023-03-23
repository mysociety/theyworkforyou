<?php

namespace MySociety\TheyWorkForYou\SectionView;

class SeneddView extends SectionView {
    protected $index_template = 'section/senedd_index';

    public function __construct() {
        if (LANGUAGE == 'cy') {
            $this->major = 11;
            $this->class = 'SENEDDCYLIST';
        } else {
            $this->major = 10;
            $this->class = 'SENEDDENLIST';
        }
        parent::__construct();
    }

    protected function display_front() {
        if (get_http_var('more')) {
            return parent::display_front();
        } else {
            return $this->display_front_senedd();
        }
    }

    protected function front_content() {
        return $this->list->display('biggest_debates', array('days'=>30, 'num'=>20), 'none');
    }

    protected function getURLs($data) {
        $urls = array();

        $day = new \MySociety\TheyWorkForYou\Url('senedddebates');
        $urls['seneddday'] = $day;
        $urls['day'] = $day;

        return $urls;
    }

    protected function getSearchSections() {
        return array(
            array( 'section' => 'wales' )
        );
    }

    protected function display_front_senedd() {
        global $this_page;
        $this_page = "seneddoverview";

        $data = array();

        $data['popular_searches'] = null;


        $data['urls'] = $this->getURLs($data);

        $DEBATELIST = new $this->class();

        $debates = $DEBATELIST->display('recent_debates', array('days' => 30, 'num' => 6), 'none');
        $MOREURL = new \MySociety\TheyWorkForYou\Url('senedddebatesfront');
        $MOREURL->insert( array( 'more' => 1 ) );

        // this makes sure that we don't repeat this debate in the list below
        $random_debate = null;
        if ( isset($debates['data']) && count($debates['data']) ) {
            $random_debate = $debates['data'][0];
        }

        $recent = array();
        if ( isset($debates['data']) && count($debates['data']) ) {
            // at the start of a session there may be less than 6
            // debates
            $max = 6;
            if ( count($debates['data']) < 6 ) {
                $max = count($debates['data']);
            }
            for ( $i = 1; $i < $max; $i++ ) {
                $debate = $debates['data'][$i];
                $debate['desc'] = "Senedd";
                $debate['more_url'] = $MOREURL->generate();
                $recent[] = $debate;
            }
        }

        $featured = array();
        if ( $random_debate ) {
            $featured = $random_debate;
            $featured['more_url'] = $MOREURL->generate();
            $featured['desc'] = 'Senedd';
            $featured['related'] = array();
            $featured['featured'] = false;
        }

        $data['featured'] = $featured;
        $data['debates'] = array( 'recent' => $recent);

        $data['regional'] = $this->getMSList();
        $data['template'] = 'senedd/index';

        return $data;
    }

    protected function getMSList() {
        global $THEUSER;

        $mreg = array();
        if ($THEUSER->isloggedin() && $THEUSER->postcode() != '' || $THEUSER->postcode_is_set()) {
            return array_merge(
                \MySociety\TheyWorkForYou\Member::getRegionalList($THEUSER->postcode, 5, 'WAC'),
                \MySociety\TheyWorkForYou\Member::getRegionalList($THEUSER->postcode, 5, 'WAE')
            );
        }

        return $mreg;
    }
}
