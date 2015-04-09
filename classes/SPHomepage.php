<?php

namespace MySociety\TheyWorkForYou;

class SPHomepage extends Homepage {

    protected $mp_house = 4;
    protected $cons_type = 'SPC';
    protected $mp_url = 'yourmsp';
    protected $page = 'spoverview';

    protected $recent_types = array(
        'SPLIST' => array('recent_debates', 'spdebatesfront', 'Scottish parliament debates'),
        'SPWRANSLIST' => array('recent_wrans', 'spwransfront', 'Written answers'),
    );

    protected function getEditorialContent() {
        $debatelist = new \SPLIST;
        $item = $debatelist->display('recent_debates', array('days' => 7, 'num' => 1), 'none');

        $item = $item['data'][0];
        $more_url = new \URL('spdebatesfront');
        $item['more_url'] = $more_url->generate();
        $item['desc'] = 'Scottish Parliament debate';
        $item['related'] = array();
        $item['featured'] = false;

        return $item;
    }

    protected function getURLs() {
        $urls = array();

        $search = new \URL('search');
        $urls['search'] = $search->generate();

        $alert = new \URL('alert');
        $urls['alert'] = $alert->generate();

        $regional = new \URL('msp');
        $urls['regional'] = $regional->generate();

        return $urls;
    }

    protected function getCalendarData() {
        return NULL;
    }


    protected function getRegionalList() {
        global $THEUSER;

        $mreg = array();

        if ($THEUSER->isloggedin() && $THEUSER->postcode() != '' || $THEUSER->postcode_is_set()) {
            return Member::getRegionalList($THEUSER->postcode, 4, 'SPE');
        }

        return $mreg;
    }
}
