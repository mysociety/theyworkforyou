<?php

namespace MySociety\TheyWorkForYou\SectionView;

class SpView extends SectionView {
    protected $major = 7;
    protected $class = 'SPLIST';

    protected function front_content() {
        echo '<h2>Busiest debates from the most recent week</h2>';
        $this->list->display('biggest_debates', array('days'=>7, 'num'=>20));
    }
}
