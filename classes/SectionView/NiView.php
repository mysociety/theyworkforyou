<?php

namespace MySociety\TheyWorkForYou\SectionView;

class NiView extends SectionView {
    public $major = 5;
    protected $class = 'NILIST';
    protected $index_template = 'section/ni_index';

    protected function display_front() {
        if (get_http_var('more')) {
            return parent::display_front();
        } else {
            return $this->display_front_ni();
        }
    }

    protected function front_content() {
        return $this->list->display('biggest_debates', array('days'=>30, 'num'=>20), 'none');
    }

    protected function getURLs($data) {
        $urls = array();

        $day = new \MySociety\TheyWorkForYou\Url('nidebates');
        $urls['niday'] = $day;

        $urls['day'] = $day;

        return $urls;
    }

    protected function getSearchSections() {
        return array(
            array( 'section' => 'ni' )
        );
    }

    protected function display_front_ni() {
        global $this_page;
        $this_page = "nioverview";

        $data = array();

        $data['popular_searches'] = null;


        $data['urls'] = $this->getURLs($data);

        $DEBATELIST = new \NILIST;

        $debates = $DEBATELIST->display('recent_debates', array('days' => 30, 'num' => 6), 'none');
        $MOREURL = new \MySociety\TheyWorkForYou\Url('nidebatesfront');
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
                $debate['desc'] = "Northern Ireland Assembly debates";
                $debate['more_url'] = $MOREURL->generate();
                $recent[] = $debate;
            }
        }

        $featured = array();
        if ( $random_debate ) {
            $featured = $random_debate;
            $featured['more_url'] = $MOREURL->generate();
            $featured['desc'] = 'Northern Ireland Assembly debate';
            $featured['related'] = array();
            $featured['featured'] = false;
        }

        $data['featured'] = $featured;
        $data['debates'] = array( 'recent' => $recent);

        $data['regional'] = $this->getMLAList();
        $data['template'] = 'ni/index';

        return $data;
    }

    protected function getMLAList() {
        global $THEUSER;

        $mreg = array();
        if ($THEUSER->isloggedin() && $THEUSER->postcode() != '' || $THEUSER->postcode_is_set()) {
            return \MySociety\TheyWorkForYou\Member::getRegionalList($THEUSER->postcode, 3, 'NIE');
        }

        return $mreg;
    }
}
