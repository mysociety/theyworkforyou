<?php

namespace MySociety\TheyWorkForYou\SectionView;

class WhallView extends SectionView {
    protected $major = 2;
    protected $class = 'WHALLLIST';

    protected function front_content() {
        return $this->list->display('biggest_debates', array('days'=>7, 'num'=>20), 'none');
    }
}
