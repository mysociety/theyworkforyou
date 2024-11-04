<?php

namespace MySociety\TheyWorkForYou;

class Homepage {
    private $db;

    protected $mp_house = 1;
    protected $cons_type = 'WMC';
    protected $mp_url = 'yourmp';
    protected $page = 'overview';
    protected $houses = [1, 101];

    protected $recent_types = [
        'DEBATELIST' => ['recent_debates', 'debatesfront', 'Commons debates'],
        'LORDSDEBATELIST' => ['recent_debates', 'lordsdebatesfront', 'Lords debates'],
        'WHALLLIST' => ['recent_debates', 'whallfront', 'Westminster Hall debates'],
        'WMSLIST' => ['recent_wms', 'wmsfront', 'Written ministerial statements'],
        'WRANSLIST' => ['recent_wrans', 'wransfront', 'Written answers'],
        'StandingCommittee' => ['recent_pbc_debates', 'pbcfront', 'Public Bill committees'],
    ];

    public function __construct() {
        $this->db = new \ParlDB();
    }

    public function display() {
        global $this_page;
        $this_page = $this->page;

        $data = [];

        $common = new Common();
        $dissolution = Dissolution::dates();

        $data['debates'] = $this->getDebatesData();

        $user = new User();
        $data['mp_data'] = $user->getRep($this->cons_type, $this->mp_house);
        $data["commons_dissolved"] = isset($dissolution[1]);

        $data['regional'] = $this->getRegionalList();
        $data['popular_searches'] = []; # $common->getPopularSearches();
        $data['urls'] = $this->getURLs();
        $data['calendar'] = $this->getCalendarData();
        $data['featured'] = $this->getEditorialContent();
        $data['topics'] = $this->getFrontPageTopics();
        $data['divisions'] = $this->getRecentDivisions();
        $data['search_box'] = $this->getSearchBox($data);

        return $data;
    }

    protected function getSearchBox(array $data): Search\SearchBox {
        $search_box = new Search\SearchBox();
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

    protected function getRegionalList() {
        return null;
    }

    protected function getEditorialContent() {
        $debatelist = new \DEBATELIST();
        $featured = new Model\Featured();
        $gid = $featured->get_gid();
        $gidCheck = new Gid($gid);
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
                $more_url = new Url('debates');
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
        $topics = new Topics();
        return $topics->getFrontPageTopics();
    }

    private function getRecentDivisions() {
        $divisions = new Divisions();
        return $divisions->getRecentDebatesWithDivisions(5, $this->houses);
    }

    protected function getURLs() {
        $urls = [];

        return $urls;
    }

    protected function getDebatesData() {
        $debates = []; // holds the most recent data there is data for, indexed by type

        $recent_content = [];

        foreach ($this->recent_types as $class => $recent) {
            $class = "\\$class";
            $instance = new $class();
            $more_url = new Url($recent[1]);
            if ($recent[0] == 'recent_pbc_debates') {
                $content = [ 'data' => $instance->display($recent[0], ['num' => 5], 'none') ];
            } else {
                $content = $instance->display($recent[0], ['days' => 7, 'num' => 1], 'none');
                if (isset($content['data']) && count($content['data'])) {
                    $content = $content['data'][0];
                } else {
                    $content = [];
                }
            }
            if ($content) {
                $content['more_url'] = $more_url->generate();
                $content['desc'] = $recent[2];
                $recent_content[] = $content;
            }
        }

        $debates['recent'] = $recent_content;

        return $debates;
    }

    private function getCalendarData() {
        return Utility\Calendar::fetchFuture();
    }

}
