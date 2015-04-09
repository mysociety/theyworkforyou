<?php

namespace MySociety\TheyWorkForYou\SectionView;

class NiView extends SectionView {
    protected $major = 5;
    protected $class = 'NILIST';

    protected function display_front() {
        if (get_http_var('more')) {
            parent::display_front();
        } else {
            $this->display_front_ni();
            return true;
        }
    }

    protected function front_content() {
        echo '<h2>Busiest debates from the most recent month</h2>';
        $this->list->display('biggest_debates', array('days'=>30, 'num'=>20));
    }

    protected function display_front_ni() {
        global $this_page;
        $this_page = "nioverview";

        $data = array();
        $urls = array();

        $data['popular_searches'] = NULL;

        $search = new \URL('search');
        $urls['search'] = $search->generate();

        $alert = new \URL('alert');
        $urls['alert'] = $alert->generate();

        $data['urls'] = $urls;

        $DEBATELIST = new \NILIST;

        $debates = $DEBATELIST->display('recent_debates', array('days' => 30, 'num' => 6), 'none');
        $MOREURL = new \URL('nidebatesfront');
        $MOREURL->insert( array( 'more' => 1 ) );

        // this makes sure that we don't repeat this debate in the list below
        $random_debate = NULL;
        if ( isset($debates['data']) && count($debates['data']) ) {
            $random_debate = $debates['data'][0];
        }

        $recent = array();
        if ( isset($debates['data']) && count($debates['data']) ) {
            for ( $i = 1; $i < 6; $i++ ) {
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

        \MySociety\TheyWorkForYou\Renderer::output('ni/index', $data);
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
