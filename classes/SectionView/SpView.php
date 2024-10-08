<?php

namespace MySociety\TheyWorkForYou\SectionView;

class SpView extends SectionView {
    public $major = 7;
    protected $class = 'SPLIST';

    protected function front_content() {
        return $this->list->display('biggest_debates', ['days' => 7, 'num' => 20], 'none');
    }

    protected function get_question_mentions_html($row_data) {
        return '';
    }

    protected function getViewUrls() {
        $urls = [];
        $day = new \MySociety\TheyWorkForYou\Url('spdebates');
        $urls['spdebatesday'] = $day;
        $urls['day'] = $day;
        return $urls;
    }

    protected function getSearchSections() {
        return [
            [ 'section' => 'sp' ],
        ];
    }
}
