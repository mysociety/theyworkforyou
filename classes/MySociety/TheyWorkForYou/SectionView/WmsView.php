<?php

namespace MySociety\TheyWorkForYou\SectionView;

class WmsView extends SectionView {
    protected $major = 4;
    protected $class = 'WMSLIST';

    protected function front_content() {
        echo '<h2>Some recent written ministerial statements</h2>';
        $this->list->display('recent_wms', array('days'=>7, 'num'=>20));
    }
}
