<?php

namespace MySociety\TheyWorkForYou\SectionView;

class DebatesView extends SectionView {
    public $major = 1;
    protected $class = 'DEBATELIST';

    protected function display_front() {
        global $PAGE, $DATA, $this_page;

        // No date or debate id. Show some recent debates

        $this_page = "alldebatesfront";

        $DEBATELIST = new \DEBATELIST;
        $debates = array();
        $debates['data'] = $DEBATELIST->display('biggest_debates', array('days'=>7, 'num'=>10), 'none');
        $args = array( 'months' => 1 );
        $debates['calendar'] = $DEBATELIST->display('calendar', $args, 'none');
        $debates['rssurl'] = $DATA->page_metadata($this_page, 'rss');

        $this_page = "whallfront";

        $whall = array();

        $WHALLLIST = new \WHALLLIST;
        $whall['data'] = $WHALLLIST->display('biggest_debates', array('days'=>7, 'num'=>10), 'none');
        $args = array( 'months' => 1 );
        $whall['calendar'] = $WHALLLIST->display('calendar', $args, 'none');
        $whall['rssurl'] = $DATA->page_metadata($this_page, 'rss');

        $this_page = "lordsdebatesfront";

        $lords = array();

        $LORDSDEBATELIST = new \LORDSDEBATELIST;
        $lords['data'] = $LORDSDEBATELIST->display('biggest_debates', array('days'=>7, 'num'=>10), 'none');
        $args = array( 'months' => 1 );
        $lords['calendar'] = $LORDSDEBATELIST->display('calendar', $args, 'none');

        $lords['rssurl'] = $DATA->page_metadata($this_page, 'rss');

        $data = array(
            'debates' => $debates,
            'lords' => $lords,
            'whall' => $whall
        );

        $data['template'] = 'section/index';
        $this_page = "alldebatesfront";

        return $data;
    }

    protected function getSearchSections() {
        return array(
            array( 'section' => 'debates', 'title' => 'House of Commons' ),
            array( 'section' => 'lords', 'title' => 'House of Lords' ),
            array( 'section' => 'whall', 'title' => 'Westminster Hall' )
        );
    }
}
