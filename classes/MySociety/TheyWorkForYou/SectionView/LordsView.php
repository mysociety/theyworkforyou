<?php

namespace MySociety\TheyWorkForYou\SectionView;

class LordsView extends SectionView {
    protected $major = 101;
    protected $class = 'MySociety\TheyWorkForYou\HansardList\DebateList\LordsDebateList';

    protected function front_content() {
        echo '<h2>Busiest debates from the most recent week</h2>';
        $this->list->display('biggest_debates', array('days'=>7, 'num'=>20));
    }
}
