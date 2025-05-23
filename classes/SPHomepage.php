<?php

namespace MySociety\TheyWorkForYou;

class SPHomepage extends Homepage {
    protected $mp_house = 4;
    protected $cons_type = 'SPC';
    protected $mp_url = 'yourmsp';
    protected $page = 'spoverview';
    protected $houses = [7];

    protected $recent_types = [
        'SPLIST' => ['recent_debates', 'spdebatesfront', 'Scottish parliament debates'],
        'SPWRANSLIST' => ['recent_wrans', 'spwransfront', 'Written answers'],
    ];

    protected function getCurrentAssembly(): string {
        return "scotland";
    }

    protected function getSearchBox(array $data): Search\SearchBox {
        $search_box = new Search\SearchBox();
        $search_box->homepage_panel_class = "panel--homepage--scotland";
        $search_box->homepage_subhead = "Scottish Parliament";
        $search_box->homepage_desc = "";
        $search_box->search_section = "scotland";
        $search_box->quick_links = [];
        if (count($data["mp_data"])) {
            $regional_con = $data["regional"][0]["constituency"];
            $search_box->add_quick_link('Find out more about your MSPs for ' . $data["mp_data"]["constituency"] . ' (' . $regional_con . ')', '/postcode/?pc=' . $data["mp_data"]['postcode'], 'torso');
        }
        $search_box->add_quick_link('Create and manage email alerts', '/alert/', 'megaphone');
        $search_box->add_quick_link(gettext('Subscribe to our newsletter'), '/about/#about-mysociety', 'mail');
        $search_box->add_quick_link('Donate to support our work', '/support-us/', 'heart');
        $search_box->add_quick_link('Learn more about TheyWorkForYou', '/about/', 'magnifying-glass');
        return $search_box;
    }

    protected function getEditorialContent() {
        $debatelist = new \SPLIST();
        $item = $debatelist->display('recent_debates', ['days' => 7, 'num' => 1], 'none');

        $item = $item['data'][0];
        $more_url = new Url('spdebatesfront');
        $item['more_url'] = $more_url->generate();
        $item['desc'] = 'Scottish Parliament debate';
        $item['related'] = [];
        $item['featured'] = false;

        return $item;
    }

    protected function getURLs() {
        $urls = [];

        $regional = new Url('msp');
        $urls['regional'] = $regional->generate();

        return $urls;
    }

    protected function getCalendarData() {
        return null;
    }


    protected function getRegionalList() {
        global $THEUSER;

        $mreg = [];

        if ($THEUSER->isloggedin() && $THEUSER->postcode() != '' || $THEUSER->postcode_is_set()) {
            return Member::getRegionalList($THEUSER->postcode, 4, 'SPE');
        }

        return $mreg;
    }
}
