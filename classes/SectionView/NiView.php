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
            $homepage = new \MySociety\TheyWorkForYou\Homepage\NI();
            return $homepage->display();
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
}
