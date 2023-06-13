<?php
/**
 * People Class
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou;

/**
 * People
 */

class People {

    public $type;
    public $rep_name;
    public $rep_plural;
    public $house;
    public $cons_type;
    public $reg_cons_type;
    public $subs_missing_image = true;
    public $db;

    public function __construct() {
        global $this_page;
        $this_page = $this->type;
        $this->db = new \ParlDB();
    }

    protected function getRegionalReps($user) {
        return $user->getRegionalReps($this->reg_cons_type, $this->house);
    }

    public function getData($args = array()) {
        $data = $this->_get_data_by_group($args);

        $user = new User();
        if ( $reps = $this->getRegionalReps($user) ) {
            $data['reps'] = $reps;
        }

        $data['mp_data'] = $user->getRep($this->cons_type, $this->house);
        $data['data'] = $this->addImagesToData($data['data']);
        $data['urls'] = $this->addUrlsToData();
        $data['order'] = isset($args['order']) ? $args['order'] : 'l';
        $data['rep_plural'] = $this->rep_plural;
        $data['rep_name'] = $this->rep_name;
        $data['type'] = $this->type;

        $country = Utility\House::getCountryDetails($this->house);
        $data['current_assembly'] = $country[2];

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
            'c' => 'constituency', 'p' => 'party',
        );
        if (array_key_exists($order, $orders)) {
            $args['order'] = $orders[$order];
        }

        return $args;
    }

    public function setMetaData($args) {
        global $this_page, $DATA;

        if (isset($args['date'])) {
            $DATA->set_page_metadata($this_page, 'title', sprintf(gettext('%s, as on %s'), $this->rep_plural, format_date($args['date'], LONGDATEFORMAT)));
        } elseif (isset($args['all'])) {
            $DATA->set_page_metadata($this_page, 'title', sprintf(gettext('All %s, including former ones'), $this->rep_plural));
        } else {
            $DATA->set_page_metadata($this_page, 'title', sprintf(gettext('All %s'), $this->rep_plural));
        }
    }

    protected function getCSVHeaders() {
        return array(gettext('Person ID'), gettext('Name'), gettext('Party'), gettext('Constituency'), gettext('URI'));
    }

    protected function getCSVRow($details) {
        return array(
            $details['person_id'],
            $details['name'],
            $details['party'],
            $details['constituency'],
            'https://www.theyworkforyou.com/mp/' . $details['url']
        );
    }

    public function sendAsCSV($data) {
        header('Content-Disposition: attachment; filename=' . $this->type . '.csv');
        header('Content-Type: text/csv');
        $out = fopen('php://output', 'w');

        $headers = $this->getCSVHeaders();
        fputcsv($out, $headers);

        foreach ($data['data'] as $pid => $details) {
            $row = $this->getCSVRow($details);
            fputcsv($out, $row);
        }

        fclose($out);
    }

    private function addImagesToData($data) {
        $new_data = array();
        foreach ( $data as $pid => $details ) {
            list($image, ) = Utility\Member::findMemberImage($pid, true, $this->subs_missing_image);
            $details['image'] = $image;
            $new_data[$pid] = $details;
        }

        return $new_data;
    }

    private function addUrlsToData() {
        global $this_page;

        $urls = array();

        $URL = new Url($this_page);

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
        if ( $date = get_http_var('date') ) {
            $URL->insert(array('date' => $date));
        }
        $urls['by_csv'] = $URL->generate();

        return $urls;
    }

    private function _get_data_by_group($args) {
        # Defaults
        $order = 'family_name';
        $sqlorder = 'family_name, given_name';

        $params = array();
        $query = 'SELECT distinct member.person_id, title, given_name, family_name, lordofname, constituency, party, left_reason ';
        $query .= 'FROM member JOIN person_names p ON p.person_id = member.person_id AND p.type = "name" ';
        if (isset($args['date'])) {
            $query .= 'AND start_date <= :date AND :date <= end_date ';
            $params[':date'] = $args['date'];
        } else {
            $query .= 'AND end_date="9999-12-31" ';
        }
        $query .= 'WHERE house=' . $this->house . ' ';
        if (isset($args['date'])) {
            $query .= 'AND entered_house <= :date AND :date <= left_house ';
        } elseif (!isset($args['all']) || $this->house == HOUSE_TYPE_COMMONS) {
            $query .= 'AND left_house = (SELECT MAX(left_house) FROM member) ';
        }

        // $args can have an optional 'order' element.
        if (isset($args['order'])) {
            $order = $args['order'];
            if ($args['order'] == 'given_name') {
                $sqlorder = 'given_name, family_name';
            } elseif ($args['order'] == 'constituency') {
                $sqlorder = 'constituency';
            } elseif ($args['order'] == 'party') {
                $sqlorder = 'party, family_name, given_name, constituency';
            }
        }

        $q = $this->db->query($query . "ORDER BY $sqlorder", $params);

        $data = array();
        foreach ($q as $row) {
            $p_id = $row['person_id'];
            if (!isset($data[$p_id])) {
                $name = member_full_name($this->house, $row['title'], $row['given_name'], $row['family_name'], $row['lordofname']);
                $constituency = gettext($row['constituency']);
                $url = make_member_url($name, $constituency, $this->house, $p_id);
                $narray = array (
                    'person_id' 	=> $p_id,
                    'given_name' => $row['given_name'],
                    'family_name' => $row['family_name'],
                    'lordofname' => $row['lordofname'],
                    'name' => $name,
                    'url' => $url,
                    'constituency' 	=> $constituency,
                    'party' 	=> $row['party'],
                    'left_reason' 	=> $row['left_reason'],
                );
                $data[$p_id] = $narray;
            }
        }
        if ($this->house == HOUSE_TYPE_LORDS && ($order == 'name' || $order == 'constituency')) {
            uasort($data, 'by_peer_name');
        }

        $data = array (
            'info' => array (
                'order' => $order
            ),
            'data' => $data
        );

        return $data;

    }

}
