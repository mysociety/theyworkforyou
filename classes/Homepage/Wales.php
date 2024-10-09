<?php

namespace MySociety\TheyWorkForYou\Homepage;

class Wales extends Base {
    protected $mp_house = 5;
    protected $cons_type = 'WAC';
    protected $page = 'seneddoverview';

    # Due to language, set things here
    public function __construct() {
        if (LANGUAGE == 'cy') {
            $this->houses = [11];
            $class = 'SENEDDCYLIST';
        } else {
            $this->houses = [10];
            $class = 'SENEDDENLIST';
        }
        $this->recent_types = [
            $class => ['recent_debates', 'senedddebatesfront', 'Senedd'],
        ];
    }

    public function display() {
        $data = parent::display();
        $data['popular_searches'] = null;
        $data['template'] = 'senedd/index';
        return $data;
    }

    protected function getEditorialContent(&$data) {
        $featured = [];
        if (count($data['debates']['recent'])) {
            $MOREURL = new \MySociety\TheyWorkForYou\Url('senedddebatesfront');
            $MOREURL->insert([ 'more' => 1 ]);
            $featured = array_shift($data['debates']['recent']);
            $featured['more_url'] = $MOREURL->generate();
            $featured['desc'] = 'Senedd';
            $featured['related'] = [];
            $featured['featured'] = false;
        }
        return $featured;
    }

    protected function getSearchBox(array $data): \MySociety\TheyWorkForYou\Search\SearchBox {
        $search_box = new \MySociety\TheyWorkForYou\Search\SearchBox();
        $search_box->homepage_panel_class = "panel--homepage--senedd";
        $search_box->homepage_subhead = gettext("Senedd / Welsh Parliament");
        $search_box->homepage_desc = "";
        $search_box->search_section = "senedd";
        $search_box->quick_links = [];
        if (count($data["regional"])) {
            // get all unique constituencies
            $constituencies = [];
            foreach ($data["regional"] as $member) {
                $constituencies[$member["constituency"]] = 1;
            }
            $constituencies = array_keys($constituencies);
            $search_box->add_quick_link(sprintf(gettext('Find out more about your MSs for %s and %s'), $constituencies[0], $constituencies[1]), '/postcode/?pc=' . $data["mp_data"]['postcode'], 'torso');
        }
        $search_box->add_quick_link(gettext('Create and manage email alerts'), '/alert/', 'megaphone');
        $search_box->add_quick_link(gettext('Subscribe to our newsletter'), '/about/#about-mysociety', 'mail');
        $search_box->add_quick_link(gettext('Donate to support our work'), '/support-us/', 'heart');
        $search_box->add_quick_link(gettext('Learn more about TheyWorkForYou'), '/about/', 'magnifying-glass');
        return $search_box;
    }

    protected function getRegionalList() {
        global $THEUSER;

        $mreg = [];
        if ($THEUSER->isloggedin() && $THEUSER->postcode() != '' || $THEUSER->postcode_is_set()) {
            return array_merge(
                \MySociety\TheyWorkForYou\Member::getRegionalList($THEUSER->postcode, 5, 'WAC'),
                \MySociety\TheyWorkForYou\Member::getRegionalList($THEUSER->postcode, 5, 'WAE')
            );
        }

        return $mreg;
    }
}
