<?php
/* For displaying lists of people. Currently just MPs and Peers.

Do:

$PEOPLE = new PEOPLE;
$PEOPLE->display('mps');

*/

class PEOPLE {

    public function PEOPLE() {
        $this->db = new ParlDB;
    }

    public function display ($view, $args=array(), $format='html') {
        global $PAGE;

        $validviews = array('mps', 'peers', 'mlas', 'msps');

        if (in_array($view, $validviews)) {

            // What function do we call for this view?
            $function = '_get_data_by_'.$view;

            // Get all the data that's to be rendered.
            $data = $this->$function($args);

        } else {
            $PAGE->error_message ("You haven't specified a view type.");
            return false;
        }

        $return = $this->render($view, $data, $format);

        return $return;
    }



    public function render($view, $data, $format='html') {
        // Once we have the data that's to be rendered,
        // include the template.

        // No format, so don't use the template sets.
        if ($format == 'none') {
            return $data;
        }

        //This should really be a single template? (rjp)
        include (INCLUDESPATH."easyparliament/templates/$format/people_$view" . ".php");
        return true;

    }

    public function _get_data_by_msps($args) {
        $args['house'] = 4;
        return $this->_get_data_by_group($args);
    }

    public function _get_data_by_mlas($args) {
        $args['house'] = 3;
        return $this->_get_data_by_group($args);
    }

    public function _get_data_by_peers($args) {
        $args['house'] = 2;
        return $this->_get_data_by_group($args);
    }

    public function _get_data_by_mps($args) {
        $args['house'] = 1;
        return $this->_get_data_by_group($args);
    }

    public function _get_data_by_group($args) {
        // $args can have an optional 'order' element.

        $use_extracol = (isset($args['order']) && in_array($args['order'], array('debates')));
        $use_personinfo = $use_extracol;

        # Defaults
        $order = 'family_name';
        $sqlorder = 'family_name, given_name';

        $params = array();
        $query = 'SELECT distinct member.person_id, title, given_name, family_name, lordofname, constituency, party, left_reason, dept, position ';
        if ($use_extracol) {
            $query .= ', data_value ';
            $order = $args['order'];
            $sqlorder = 'data_value+0 DESC, family_name, given_name';
            unset($args['date']);
            $key_lookup = array(
                'debates' => 'debate_sectionsspoken_inlastyear',
            );
            $personinfo_key = $key_lookup[$order];
        }
        $query .= 'FROM member LEFT OUTER JOIN moffice ON member.person_id = moffice.person ';
        if (isset($args['date'])) {
            $query .= 'AND from_date <= :date AND :date <= to_date ';
            $params[':date'] = $args['date'];
        } else {
            $query .= 'AND to_date="9999-12-31" ';
        }
        if ($use_personinfo) {
            $query .= 'LEFT OUTER JOIN personinfo ON member.person_id = personinfo.person_id AND data_key="' . $personinfo_key . '" ';
        }
        $query .= ' JOIN person_names p ON p.person_id = member.person_id AND p.type = "name" ';
        if (isset($args['date']))
            $query .= 'AND start_date <= :date AND :date <= end_date ';
        else
            $query .= 'AND end_date="9999-12-31" ';
        $query .= 'WHERE house=' . $args['house'] . ' ';
        if (isset($args['date']))
            $query .= 'AND entered_house <= :date AND :date <= left_house ';
        elseif (!isset($args['all']) || $args['house'] == 1)
            $query .= 'AND left_house = (SELECT MAX(left_house) FROM member) ';

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
        for ($row=0; $row<$q->rows(); $row++) {
            $p_id = $q->field($row, 'person_id');
            $dept = $q->field($row, 'dept');
            $pos = $q->field($row, 'position');
            if (isset($data[$p_id])) {
                $data[$p_id]['dept'] = array_merge((array) $data[$p_id]['dept'], (array) $dept);
                $data[$p_id]['pos'] = array_merge((array) $data[$p_id]['pos'], (array) $pos);
            } else {
                $name = member_full_name($args['house'], $q->field($row, 'title'),
                    $q->field($row, 'given_name'), $q->field($row, 'family_name'),
                    $q->field($row, 'lordofname'));
                $constituency = $q->field($row, 'constituency');
                $url = make_member_url($name, $constituency, $args['house'], $p_id);
                $narray = array (
                    'person_id' 	=> $p_id,
                    'given_name' => $q->field($row, 'given_name'),
                    'family_name' => $q->field($row, 'family_name'),
                    'lordofname' => $q->field($row, 'lordofname'),
                    'name' => $name,
                    'url' => $url,
                    'constituency' 	=> $constituency,
                    'party' 	=> $q->field($row, 'party'),
                    'left_reason' 	=> $q->field($row, 'left_reason'),
                    'dept'		=> $dept,
                    'pos'		=> $pos
                );
                if ($use_extracol) {
                    $narray['data_value'] = $q->field($row, 'data_value');
                }

                if ($narray['party'] == 'SPK') {
                    $narray['party'] = '-';
                    $narray['pos'] = 'Speaker';
                    $narray['dept'] = 'House of Commons';
                } elseif ($narray['party'] == 'CWM' || $narray['party'] == 'DCWM') {
                    $narray['party'] = '-';
                    $narray['pos'] = 'Deputy Speaker';
                    $narray['dept'] = 'House of Commons';
                }

                $data[$p_id] = $narray;
            }
        }
        if ($args['house'] == 2 && ($order == 'name' || $order == 'constituency'))
            uasort($data, array($this, 'by_peer_name'));

        $data = array (
            'info' => array (
                'order' => $order
            ),
            'data' => $data
        );

        return $data;

    }
    public function by_peer_name($a, $b) {
        if (!$a['family_name'] && !$b['family_name'])
            return strcmp($a['lordofname'], $b['lordofname']);
        if (!$a['family_name'])
            return strcmp($a['lordofname'], $b['family_name']);
        if (!$b['family_name'])
            return strcmp($a['family_name'], $b['lordofname']);
        if (strcmp($a['family_name'], $b['family_name']))
            return strcmp($a['family_name'], $b['family_name']);
        return strcmp($a['lordofname'], $b['lordofname']);
    }

}
