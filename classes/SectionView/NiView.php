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
        return $this->list->display('biggest_debates', ['days' => 30, 'num' => 20], 'none');
    }

    protected function getURLs($data) {
        $urls = [];

        $day = new \MySociety\TheyWorkForYou\Url('nidebates');
        $urls['niday'] = $day;

        $urls['day'] = $day;

        return $urls;
    }

    protected function getSearchSections() {
        return [
            [ 'section' => 'ni' ],
        ];
    }

    protected function display_front_ni() {
        global $this_page;
        $this_page = "nioverview";

        $data = [];

        $data['popular_searches'] = null;


        $data['urls'] = $this->getURLs($data);

        $DEBATELIST = new \NILIST();

        $debates = $DEBATELIST->display('recent_debates', ['days' => 30, 'num' => 6], 'none');
        $MOREURL = new \MySociety\TheyWorkForYou\Url('nidebatesfront');
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
                $debate['desc'] = "Northern Ireland Assembly debates";
                $debate['more_url'] = $MOREURL->generate();
                $recent[] = $debate;
            }
        }

        $featured = [];
        if ($random_debate) {
            $featured = $random_debate;
            $featured['more_url'] = $MOREURL->generate();
            $featured['desc'] = 'Northern Ireland Assembly debate';
            $featured['related'] = [];
            $featured['featured'] = false;
        }

        $data['featured'] = $featured;
        $data['debates'] = [ 'recent' => $recent];

        $data['regional'] = $this->getMLAList();
        $data['search_box'] = $this->getSearchBox($data);
        $data['current_assembly'] = 'ni';
        $data['template'] = 'ni/index';

        return $data;
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

    protected function getMLAList() {
        global $THEUSER;

        $mreg = [];
        if ($THEUSER->isloggedin() && $THEUSER->postcode() != '' || $THEUSER->postcode_is_set()) {
            return \MySociety\TheyWorkForYou\Member::getRegionalList($THEUSER->postcode, 3, 'NIE');
        }

        return $mreg;
    }
}
