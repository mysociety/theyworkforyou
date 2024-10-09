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
            $homepage = new \MySociety\TheyWorkForYou\Homepage\Wales();
            return $homepage->display();
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
}
