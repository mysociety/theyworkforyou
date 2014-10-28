<?php

namespace MySociety\TheyWorkForYou\SectionView;

class WhallView extends SectionView {
    protected $major = 2;
    protected $class = 'MySociety\TheyWorkForYou\HansardList\DebateList\WhallList';

    protected function front_content() {
        echo '<h2>Busiest Westminster Hall debates from the most recent week</h2>';
        $this->list->display('biggest_debates', array('days'=>7, 'num'=>20));
    }
}
