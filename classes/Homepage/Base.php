<?php

namespace MySociety\TheyWorkForYou\Homepage;

abstract class Base {
    public function display() {
        global $this_page;
        $this_page = $this->page;

        $data = [];

        $common = new \MySociety\TheyWorkForYou\Common();

        $data['debates'] = $this->getDebatesData();

        $user = new \MySociety\TheyWorkForYou\User();
        $data['mp_data'] = $user->getRep($this->cons_type, $this->mp_house);

        $data['regional'] = $this->getRegionalList();
        $data['popular_searches'] = $common->getPopularSearches();
        $data['featured'] = $this->getEditorialContent($data);
        $data['divisions'] = $this->getRecentDivisions();
        $data['search_box'] = $this->getSearchBox($data);

        return $data;
    }

    abstract protected function getSearchBox(array $data): \MySociety\TheyWorkForYou\Search\SearchBox;
    abstract protected function getEditorialContent(array &$data);

    protected function getRegionalList() {
        return null;
    }

    private function getRecentDivisions() {
        $divisions = new \MySociety\TheyWorkForYou\Divisions();
        return $divisions->getRecentDebatesWithDivisions(5, $this->houses);
    }

    protected function getDebatesData() {
        $debates = []; // holds the most recent data there is data for, indexed by type

        $recent_content = [];

        foreach ($this->recent_types as $class => $recent) {
            $class = "\\$class";
            $instance = new $class();
            $more_url = new \MySociety\TheyWorkForYou\Url($recent[1]);
            if ($recent[0] == 'recent_pbc_debates') {
                $content = [ 'data' => $instance->display($recent[0], ['num' => 5], 'none') ];
            } elseif ($recent[1] == 'senedddebatesfront' || $recent[1] == 'nidebatesfront') {
                $content = $instance->display($recent[0], ['days' => 30, 'num' => 6], 'none');
                # XXX Bit hacky, for now
                foreach ($content['data'] as $d) {
                    $d['more_url'] = $more_url->generate();
                    $d['desc'] = '';
                    $recent_content[] = $d;
                }
                $content = [];
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
}
