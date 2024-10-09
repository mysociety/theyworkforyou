<?php

namespace MySociety\TheyWorkForYou\Homepage;

class UK extends Base {
    protected $mp_house = 1;
    protected $cons_type = 'WMC';
    protected $page = 'overview';
    protected $houses = [1, 101];
    private $mp_url = 'yourmp';

    protected $recent_types = [
        'DEBATELIST' => ['recent_debates', 'debatesfront', 'Commons debates'],
        'LORDSDEBATELIST' => ['recent_debates', 'lordsdebatesfront', 'Lords debates'],
        'WHALLLIST' => ['recent_debates', 'whallfront', 'Westminster Hall debates'],
        'WMSLIST' => ['recent_wms', 'wmsfront', 'Written ministerial statements'],
        'WRANSLIST' => ['recent_wrans', 'wransfront', 'Written answers'],
        'StandingCommittee' => ['recent_pbc_debates', 'pbcfront', 'Public Bill committees'],
    ];

    public function display() {
        $data = parent::display();
        $data['calendar'] = $this->getCalendarData();
        $data['topics'] = $this->getFrontPageTopics();
        return $data;
    }

    protected function getSearchBox(array $data): \MySociety\TheyWorkForYou\Search\SearchBox {
        $search_box = new \MySociety\TheyWorkForYou\Search\SearchBox();
        $search_box->homepage_panel_class = "panel--homepage--overall";
        $search_box->homepage_subhead = "";
        $search_box->homepage_desc = "Understand who represents you, across the UK’s Parliaments.";
        $search_box->search_section = "";
        $search_box->quick_links = [];
        if (count($data["mp_data"])) {
            $search_box->add_quick_link('Find out more about your MP (' . $data["mp_data"]['name'] . ')', $data["mp_data"]['mp_url'], 'torso');
        }
        $search_box->add_quick_link('Create and manage email alerts', '/alert/', 'megaphone');
        $search_box->add_quick_link(gettext('Subscribe to our newsletter'), '/about/#about-mysociety', 'mail');
        $search_box->add_quick_link('Donate to support our work', '/support-us/', 'heart');
        $search_box->add_quick_link('Learn more about TheyWorkForYou', '/about/', 'magnifying-glass');
        return $search_box;
    }

    protected function getEditorialContent(&$data) {
        $debatelist = new \DEBATELIST();
        $featured = new \MySociety\TheyWorkForYou\Model\Featured();
        $gid = $featured->get_gid();
        $gidCheck = new \MySociety\TheyWorkForYou\Gid($gid);
        $gid = $gidCheck->checkForRedirect();
        if ($gid) {
            $title = $featured->get_title();
            $context = $featured->get_context();
            $related = $featured->get_related();
            $item = $this->getFeaturedDebate($gid, $title, $context, $related);
        } else {
            $item = $debatelist->display('recent_debates', ['days' => 7, 'num' => 1], 'none');
            if (isset($item['data']) && count($item['data'])) {
                $item = $item['data'][0];
                $more_url = new \MySociety\TheyWorkForYou\Url('debates');
                $item['more_url'] = $more_url->generate();
                $item['desc'] = 'Commons Debates';
                $item['related'] = [];
                $item['featured'] = false;
            } else {
                $item = [];
            }
        }

        return $item;
    }

    public function getFeaturedDebate($gid, $title, $context, $related) {
        if (strpos($gid, 'lords') !== false) {
            $debatelist = new \LORDSDEBATELIST();
        } elseif (strpos($gid, 'westminhall') !== false) {
            $debatelist = new \WHALLLIST();
        } else {
            $debatelist = new \DEBATELIST();
        }

        $item = $debatelist->display('featured_gid', ['gid' => $gid], 'none');
        $item = $item['data'];
        $item['headline'] = $title;
        $item['context'] = $context;
        $item['featured'] = true;

        $related_debates = [];
        foreach ($related as $related_gid) {
            if ($related_gid) {
                $related_item = $debatelist->display('featured_gid', ['gid' => $related_gid], 'none');
                $related_debates[] = $related_item['data'];
            }
        }
        $item['related'] = $related_debates;
        return $item;
    }

    protected function getFrontPageTopics() {
        $topics = new \MySociety\TheyWorkForYou\Topics();
        return $topics->getFrontPageTopics();
    }

    private function getCalendarData() {
        return \MySociety\TheyWorkForYou\Utility\Calendar::fetchFuture();
    }

}
