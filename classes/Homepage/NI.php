<?php

namespace MySociety\TheyWorkForYou\Homepage;

class NI extends Base {
    protected $mp_house = 3;
    protected $cons_type = 'NIE';
    protected $page = 'nioverview';
    protected $houses = [5];
    protected $recent_types = [
        'NILIST' => ['recent_debates', 'nidebatesfront', 'Northern Ireland Assembly debates'],
    ];

    public function display() {
        $data = parent::display();
        $data['popular_searches'] = null;
        $data['template'] = 'ni/index';
        return $data;
    }

    protected function getEditorialContent(&$data) {
        $featured = [];
        if (count($data['debates']['recent'])) {
            $MOREURL = new \MySociety\TheyWorkForYou\Url('nidebatesfront');
            $MOREURL->insert([ 'more' => 1 ]);
            $featured = array_shift($data['debates']['recent']);
            $featured['more_url'] = $MOREURL->generate();
            $featured['desc'] = 'Northern Ireland Assembly debate';
            $featured['related'] = [];
            $featured['featured'] = false;
        }
        return $featured;
    }

    protected function getSearchBox(array $data): \MySociety\TheyWorkForYou\Search\SearchBox {

        global $THEUSER;

        if ($THEUSER->isloggedin() && $THEUSER->postcode() != '' || $THEUSER->postcode_is_set()) {
            $postcode = $THEUSER->postcode();
        } else {
            $postcode = null;
        }

        $search_box = new \MySociety\TheyWorkForYou\Search\SearchBox();
        $search_box->homepage_panel_class = "panel--homepage--niassembly";
        $search_box->homepage_subhead = "Northern Ireland Assembly";
        $search_box->homepage_desc = "";
        $search_box->search_section = "ni";
        $search_box->quick_links = [];
        if (count($data["regional"])) {
            $constituency = $data["regional"][0]["constituency"];
            $search_box->add_quick_link('Find out more about your MLAs for ' . $constituency, '/postcode/?pc=' . $postcode, 'torso');
        }
        $search_box->add_quick_link('Create and manage email alerts', '/alert/', 'megaphone');
        $search_box->add_quick_link(gettext('Subscribe to our newsletter'), '/about/#about-mysociety', 'mail');
        $search_box->add_quick_link('Donate to support our work', '/support-us/', 'heart');
        $search_box->add_quick_link('Learn more about TheyWorkForYou', '/about/', 'magnifying-glass');
        return $search_box;
    }

    protected function getRegionalList() {
        global $THEUSER;

        $mreg = [];
        if ($THEUSER->isloggedin() && $THEUSER->postcode() != '' || $THEUSER->postcode_is_set()) {
            return \MySociety\TheyWorkForYou\Member::getRegionalList($THEUSER->postcode, 3, 'NIE');
        }

        return $mreg;
    }
}
