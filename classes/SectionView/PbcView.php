<?php

namespace MySociety\TheyWorkForYou\SectionView;

class PbcView extends SectionView {
    protected $major = 6;
    protected $class = 'StandingCommittee';

    function __construct() {
        $this->session = get_http_var('session');
        $this->bill = str_replace('_', ' ', get_http_var('bill'));
        $this->list = new \StandingCommittee($this->session, $this->bill);
        parent::__construct();
    }

    # Public Bill Committees have a somewhat different structure to the rest
    public function display() {
        global $PAGE;
        $this->public_bill_committees();
        $PAGE->page_end();
    }

    protected function public_bill_committees() {
        global $this_page, $DATA;

        $id = get_http_var('id');

        $bill_id = null;
        if ($this->session && $this->bill) {
            $q = $this->list->db->query('select id,standingprefix from bills where title="'
                . mysql_real_escape_string($this->bill) . '"
                and session = "'.mysql_real_escape_string($this->session).'"');
            if ($q->rows()) {
                $bill_id = $q->field(0, 'id');
                $standingprefix = $q->field(0, 'standingprefix');
            }
        }

        if ($bill_id && $id) {
            $this->display_section_or_speech(array(
                'gid' => $standingprefix . $id,
            ));
        } elseif ($bill_id) {
            # Display the page for a particular bill
            $this_page = 'pbc_bill';
            $args = array (
                'id' => $bill_id,
                'title' => $this->bill,
                'session' => $this->session,
            );
            $this->list->display('bill', $args);
        } elseif ($this->session && $this->bill) {
            # Illegal bill title, redirect to session page
            $URL = new \URL('pbc_session');
            header('Location: ' . $URL->generate() . urlencode($this->session));
            exit;
        } elseif ($this->session) {
            # Display the bills for a particular session
            $this_page = 'pbc_session';
            $DATA->set_page_metadata($this_page, 'title', "Session $this->session");
            $args = array (
                'session' => $this->session,
            );
            $this->list->display('session', $args);
        } else {
            $this->display_front();
        }
    }

    protected function front_content() {
        echo '<h2>Most recent Public Bill committee debates</h2>
        <p><a href="/pbc/2014-15/">See all committees for the current session</a></p>';
        $this->list->display( 'recent_pbc_debates', array( 'num' => 50 ) );
    }
}
