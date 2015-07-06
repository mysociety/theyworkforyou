<?php
/**
 * People Class
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou;

include_once( dirname(__FILE__) . '/../www/includes/easyparliament/people.php' );

/**
 * People
 */

class People extends \PEOPLE {

    public $type;
    public $rep_name;
    public $rep_plural;
    public $house;
    public $cons_type;
    public $people_url;
    public $db;

    public function __construct( $type ) {
        global $this_page;
        $this_page = $type;
        $this->type = $type;
        $this->db = new \ParlDB();
        $this->setTypeData();
    }

    public function getData($args = array()) {
        $data = $this->display($this->type, $args, 'none');

        $user = new User();
        $data['mp_data'] = $user->getMP($this->people_url, $this->cons_type, $this->house);

        $data['data'] = $this->addImagesToData($data['data']);
        $data['urls'] = $this->addUrlsToData($data);
        $data['order'] = isset($args['order']) ? $args['order'] : 'l';
        $data['rep_plural'] = $this->rep_plural;
        $data['rep_name'] = $this->rep_name;
        $data['type'] = $this->type;

        return $data;
    }

    public function getArgs() {
        $args = array();

        if (get_http_var('f') == 'csv') {
            $args['f'] = 'csv';
        }

        $date = get_http_var('date');
        if ($date) {
            $date = parse_date($date);
            if ($date) {
                $args['date'] = $date['iso'];
            }
        } elseif (get_http_var('all')) {
            $args['all'] = true;
        }

        if ( $this->type == 'peers' ) {
            $args['order'] = 'name';
        }

        $order = get_http_var('o');
        $orders = array(
            'n' => 'name', 'f' => 'given_name', 'l' => 'family_name',
            'c' => 'constituency', 'p' => 'party', 'd' => 'debates',
        );
        if (array_key_exists($order, $orders)) {
            $args['order'] = $orders[$order];
        }

        return $args;
    }

    public function setMetaData($args) {
        global $this_page, $DATA;

        if (isset($args['date'])) {
            $DATA->set_page_metadata($this_page, 'title', $this->rep_plural . ', as on ' . format_date($args['date'], LONGDATEFORMAT));
        } elseif (isset($args['all'])) {
            $DATA->set_page_metadata($this_page, 'title', 'All ' . $this->rep_plural . ', including former ones');
        } else {
            $DATA->set_page_metadata($this_page, 'title', 'All ' . $this->rep_plural);
        }
    }

    public function sendAsCSV($data) {
        header('Content-Type: text/csv');
        $out = fopen('php://output', 'w');

        $headers = array('Person ID', 'First name', 'Last name', 'Party', 'Constituency', 'URI');
        if ($data['info']['order'] == 'debates') {
            $headers[] = 'Debates spoken in the last year';
        }
        fputcsv($out, $headers);

        foreach ($data['data'] as $pid => $details) {
            $row = array(
                $details['person_id'],
                $details['given_name'],
                $details['family_name'],
                $details['party'],
                $details['constituency'],
                'http://www.theyworkforyou.com/mp/' . $details['url']
            );
            if ($data['info']['order'] == 'debates') {
                $row[] = $details['data_value'];
            }
            fputcsv($out, $row);
        }

        fclose($out);
    }

    private function setTypeData() {
        switch( $this->type ) {
        case 'mlas':
            $this->rep_name = 'MLA';
            $this->rep_plural = 'MLAs';
            $this->house = 3;
            $this->cons_type = 'NLI';
            $this->people_url = 'mla';
            break;
        case 'msps':
            $this->rep_name = 'MSP';
            $this->rep_plural = 'MSPs';
            $this->house = 4;
            $this->cons_type = 'SPC';
            $this->people_url = 'yourmsp';
            break;
        case 'peers':
            $this->rep_name = 'Member of the House of Lords';
            $this->rep_plural = 'Members of the House of Lords';
            $this->house = 2;
            $this->cons_type = 'S';
            $this->people_url = 'yourmsp';
            break;
        default:
            $this->rep_name = 'MPs';
            $this->rep_plural = 'MPs';
            $this->house = 1;
            $this->cons_type = 'WMC';
            $this->people_url = 'yourmp';
        }
    }

    private function addImagesToData($data) {
        $new_data = array();
        foreach ( $data as $pid => $details ) {
            list($image, ) = Utility\Member::findMemberImage($pid, true, true);
            $details['image'] = $image;
            $new_data[$pid] = $details;
        }

        return $new_data;
    }

    private function addUrlsToData() {
        global $this_page;

        $urls = array();

        $URL = new \URL($this_page);

        $urls['plain'] = $URL->generate();

        $URL->insert(array( 'o' => 'n'));
        $urls['by_name'] = $URL->generate();

        $URL->insert(array( 'o' => 'l'));
        $urls['by_last'] = $URL->generate();

        $URL->insert(array( 'o' => 'f'));
        $urls['by_first'] = $URL->generate();

        $URL->insert(array( 'o' => 'p'));
        $urls['by_party'] = $URL->generate();

        $URL->insert(array( 'f' => 'csv'));
        $URL->remove(array( 'o'));
        $urls['by_csv'] = $URL->generate();

        return $urls;
    }

}
