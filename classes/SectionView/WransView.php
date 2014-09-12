<?php

namespace MySociety\TheyWorkForYou\SectionView;

class WransView extends SectionView {
    protected $major = 3;
    protected $class = 'WRANSLIST';

    protected function front_content() {
        echo '<h2>Some recent written answers</h2>';
        $this->list->display('recent_wrans', array('days'=>7, 'num'=>20));
    }

    # If we don't have "q"/"r" in the GID, we use this counter to output on any
    # speech bar the first (assuming that's the question)
    private $votelinks_so_far = 0;

    protected function generate_votes ($votes, $id, $gid) {
        /*
        Returns HTML for the 'Does this answer the question?' links (wrans) in the sidebar.
        $votes = => array (
            'user'    => array ( 'yes' => '21', 'no' => '3' ),
            'anon'    => array ( 'yes' => '132', 'no' => '30' )
        )
        */

        global $this_page;

        # If there's a "q" we assume it's a question and ignore it
        if (strstr($gid, 'q')) {
            return;
        }

        $data = array();
        if ($this->votelinks_so_far > 0 || strstr($gid, 'r')) {
            $yesvotes = $votes['user']['yes'] + $votes['anon']['yes'];
            $novotes = $votes['user']['no'] + $votes['anon']['no'];

            $yesplural = $yesvotes == 1 ? 'person thinks' : 'people think';
            $noplural = $novotes == 1 ? 'person thinks' : 'people think';

            $URL = new \URL($this_page);
            $returl = $URL->generate();
            $VOTEURL = new \URL('epvote');
            $VOTEURL->insert(array('v'=>'1', 'id'=>$id, 'ret'=>$returl));
            $yes_vote_url = $VOTEURL->generate();
            $VOTEURL->insert(array('v'=>'0'));
            $no_vote_url = $VOTEURL->generate();

            $data = array(
                'yesvotes' => $yesvotes,
                'yesplural' => $yesplural,
                'yesvoteurl' => $yes_vote_url,
                'novoteurl' => $no_vote_url,
                'novotes' => $novotes,
                'noplural' => $noplural,
            );
        }

        $this->votelinks_so_far++;
        return $data;
    }
}
