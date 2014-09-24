<?php

namespace MySociety\TheyWorkForYou\SectionView;

class SpwransView extends WransView {
    protected $major = 8;
    protected $class = 'SPWRANSLIST';

    public function display() {
        global $PAGE;
        if ($spid = get_http_var('spid')) {
            $this->spwrans_redirect($spid);
            $PAGE->page_end();
        } else {
            return parent::display();
        }
    }

    private function spwrans_redirect($spid) {
        global $PAGE;

        # We have a Scottish Parliament ID, need to find the date
        $SPWRANSLIST = new \SPWRANSLIST;
        $gid = $SPWRANSLIST->get_gid_from_spid($spid);
        if ($gid) {
            if (preg_match('/uk\.org\.publicwhip\/spwa\/(\d{4}-\d\d-\d\d\.(.*))/',$gid,$m)) {
                $URL = new \URL('spwrans');
                $URL->reset();
                $URL->insert( array('id' => $m[1]) );
                $fragment_identifier = '#g' . $m[2];
                header('Location: http://' . DOMAIN . $URL->generate('none') . $fragment_identifier, true, 303);
                exit;
            } elseif (preg_match('/uk\.org\.publicwhip\/spor\/(\d{4}-\d\d-\d\d\.(.*))/',$gid,$m)) {
                $URL = new \URL('spdebates');
                $URL->reset();
                $URL->insert( array('id' => $m[1]) );
                $fragment_identifier = '#g' . $m[2];
                header('Location: http://' . DOMAIN . $URL->generate('none') . $fragment_identifier, true, 303);
                exit;
            } else {
                $PAGE->error_message ("Strange GID ($gid) for that Scottish Parliament ID.");
            }
        }
        $PAGE->error_message ("Couldn't match that Scottish Parliament ID to a GID.");
    }
}
