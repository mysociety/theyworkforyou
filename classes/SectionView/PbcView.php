<?php

namespace MySociety\TheyWorkForYou\SectionView;

class PbcView extends SectionView {
    public $major = 6;
    protected $class = 'StandingCommittee';
    protected $index_template = 'section/pbc_index';

    private $bill;
    private $session;

    public function __construct() {
        $this->session = get_http_var('session');
        $this->bill = str_replace('_', ' ', get_http_var('bill'));
        $this->list = new \StandingCommittee($this->session, $this->bill);
        parent::__construct();
    }

    # Public Bill Committees have a somewhat different structure to the rest
    public function display() {
        $data = $this->public_bill_committees();
        $data['location'] = $this->location;
        $data['current_assembly'] = $this->assembly;
        return $data;
    }

    protected function public_bill_committees() {
        global $this_page, $DATA, $PAGE;

        $id = get_http_var('id');

        $bill_id = null;
        if ($this->session && $this->bill) {
            $q = $this->list->db->query('select id,standingprefix from bills where title = :bill
                and session = :session', [
                ':bill' => $this->bill,
                ':session' => $this->session,
            ])->first();
            if ($q) {
                $bill_id = $q['id'];
                $standingprefix = $q['standingprefix'];
            }
        }

        if ($bill_id && $id) {
            return $this->display_section_or_speech([
                'gid' => $standingprefix . $id,
            ]);
        } elseif ($bill_id) {
            # Display the page for a particular bill
            $this_page = 'pbc_bill';
            $args =  [
                'id' => $bill_id,
                'title' => $this->bill,
                'session' => $this->session,
            ];
            $data = [];
            $data['content'] = $this->list->display('bill', $args, 'none');
            $data['session'] = $this->session;
            $data['template'] = 'section/pbc_bill';
            return $this->addCommonData($data);
        } elseif ($this->session && $this->bill) {
            # Illegal bill title, redirect to session page
            $URL = new \MySociety\TheyWorkForYou\Url('pbc_session');
            header('Location: ' . $URL->generate() . urlencode($this->session));
            exit;
        } elseif ($this->session) {
            # Display the bills for a particular session
            $this_page = 'pbc_session';
            $DATA->set_page_metadata($this_page, 'title', "Session $this->session");
            $args =  [
                'session' => $this->session,
            ];
            $data = [];
            $data['rows'] = $this->list->display('session', $args, 'none');
            $data['template'] = 'section/pbc_session';
            $data['session'] = $this->session;
            return $this->addCommonData($data);
        } else {
            return $this->display_front();
        }
    }

    protected function getViewUrls() {
        $urls = [];
        $day = new \MySociety\TheyWorkForYou\Url('pbc_front');
        $urls['pbcday'] = $day;
        return $urls;
    }

    protected function getSearchSections() {
        return [
            [ 'section' => 'pbc' ],
        ];
    }

    protected function front_content() {
        return $this->list->display('recent_pbc_debates', [ 'num' => 50 ], 'none');
    }

    protected function display_front() {
        global $DATA, $this_page;
        $this_page = 'pbc_front';

        $data = [];
        $data['template'] = $this->index_template;

        $content = [];
        $content['data'] = $this->front_content();

        $content['rssurl'] = $DATA->page_metadata($this_page, 'rss');

        $data['content'] = $content;
        return $this->addCommonData($data);
    }
}
