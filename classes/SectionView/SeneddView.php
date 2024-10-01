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
        return $this->list->display('biggest_debates', ['days' => 30, 'num' => 20], 'none');
    }

    protected function getURLs($data) {
        $urls = [];

        $day = new \MySociety\TheyWorkForYou\Url('senedddebates');
        $urls['seneddday'] = $day;
        $urls['day'] = $day;

        return $urls;
    }

    protected function getSearchSections() {
        return [
            [ 'section' => 'wales' ],
        ];
    }

    protected function display_front_senedd() {
        global $this_page;
        $this_page = "seneddoverview";

        $data = [];

        $data['popular_searches'] = null;

        $user = new \MySociety\TheyWorkForYou\User();
        $data['mp_data'] = $user->getRep("WMC", 1);

        $data['urls'] = $this->getURLs($data);

        $DEBATELIST = new $this->class();

        $debates = $DEBATELIST->display('recent_debates', ['days' => 30, 'num' => 6], 'none');
        $MOREURL = new \MySociety\TheyWorkForYou\Url('senedddebatesfront');
        $MOREURL->insert([ 'more' => 1 ]);

        // this makes sure that we don't repeat this debate in the list below
        $random_debate = null;
        if (isset($debates['data']) && count($debates['data'])) {
            $random_debate = $debates['data'][0];
        }

        $recent = [];
        if (isset($debates['data']) && count($debates['data'])) {
            // at the start of a session there may be less than 6
            // debates
            $max = 6;
            if (count($debates['data']) < 6) {
                $max = count($debates['data']);
            }
            for ($i = 1; $i < $max; $i++) {
                $debate = $debates['data'][$i];
                $debate['desc'] = "Senedd";
                $debate['more_url'] = $MOREURL->generate();
                $recent[] = $debate;
            }
        }

        $featured = [];
        if ($random_debate) {
            $featured = $random_debate;
            $featured['more_url'] = $MOREURL->generate();
            $featured['desc'] = 'Senedd';
            $featured['related'] = [];
            $featured['featured'] = false;
        }

        $data['featured'] = $featured;
        $data['debates'] = [ 'recent' => $recent];

        $data['regional'] = $this->getMSList();
        $data['search_box'] = $this->getSearchBox($data);
        $data['template'] = 'senedd/index';

        return $data;
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

    protected function getMSList() {
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
