<?php

namespace MySociety\TheyWorkForYou\SectionView;

class WransView extends SectionView {
    protected $major = 3;
    protected $class = 'WRANSLIST';

    protected function front_content() {
        echo '<h2>Some recent written answers</h2>';
        $this->list->display('recent_wrans', array('days'=>7, 'num'=>20));
    }
}
