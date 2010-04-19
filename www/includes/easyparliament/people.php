<?php
/* For displaying lists of people. Currently just MPs and Peers.

Do:

$PEOPLE = new PEOPLE;
$PEOPLE->display('mps');

*/

class PEOPLE {

	function PEOPLE () {
		$this->db = new ParlDB;
	}

	function display ($view, $args=array(), $format='html') {
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
	
	
	
	function render ($view, $data, $format='html') {
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

	function _get_data_by_msps($args) {
		$args['house'] = 4;
		return $this->_get_data_by_group($args);
	}

	function _get_data_by_mlas($args) {
		$args['house'] = 3;
		return $this->_get_data_by_group($args);
	}

	function _get_data_by_peers($args) {
		$args['house'] = 2;
		return $this->_get_data_by_group($args);
	}

	function _get_data_by_mps ($args) {
		$args['house'] = 1;
		return $this->_get_data_by_group($args);
	}

	function _get_data_by_group($args) {
		// $args can have an optional 'order' element.

        $use_standing_down = ($args['house'] == 1 && !isset($args['date']));
        $use_extracol = (isset($args['order']) && in_array($args['order'], array('expenses', 'debates', 'safety')));
        $use_personinfo = ($use_standing_down || $use_extracol);

        # Defaults
		$order = 'last_name';
		$sqlorder = 'last_name, first_name';

		$query = 'SELECT distinct member.person_id, title, first_name, last_name, constituency, party, dept, position ';
        if ($use_standing_down) {
            $query .= ', data_value ';
            $personinfo_key = 'standing_down';
        } elseif ($use_extracol) {
            $query .= ', data_value ';
			$order = $args['order'];
			$sqlorder = 'data_value+0 DESC, last_name, first_name';
            unset($args['date']);
            $key_lookup = array(
                'expenses' => 'expenses2004_total',
                'debates' => 'debate_sectionsspoken_inlastyear',
                'safety' => 'swing_to_lose_seat_today',
            );
            $personinfo_key = $key_lookup[$order];
        }
        $query .= 'FROM member LEFT OUTER JOIN moffice ON member.person_id = moffice.person ';
		if (isset($args['date']))
			$query .= 'AND from_date <= date("' . $args['date'] . '") AND date("' . $args['date'] . '") <= to_date ';
		else
			$query .= 'AND to_date="9999-12-31" ';
        if ($use_personinfo) {
            $query .= 'LEFT OUTER JOIN personinfo ON member.person_id = personinfo.person_id AND data_key="' . $personinfo_key . '" ';
        }
		$query .= 'WHERE house=' . $args['house'] . ' ';
		if (isset($args['date']))
			$query .= 'AND entered_house <= date("' . $args['date'] . '") AND date("' . $args['date'] . '") <= left_house ';
		elseif (!isset($args['all']) || $args['house'] == 1)
			$query .= 'AND left_house = (SELECT MAX(left_house) FROM member) ';

		if (isset($args['order'])) {
            $order = $args['order'];
			if ($args['order'] == 'first_name') {
				$sqlorder = 'first_name, last_name';
			} elseif ($args['order'] == 'constituency') {
				$sqlorder = 'constituency';
			} elseif ($args['order'] == 'party') {
				$sqlorder = 'party, last_name, first_name, constituency';
			}
		}

		$q = $this->db->query($query . "ORDER BY $sqlorder");
	
		$data = array();
		for ($row=0; $row<$q->rows(); $row++) {
			$p_id = $q->field($row, 'person_id');
			$dept = $q->field($row, 'dept');
			$pos = $q->field($row, 'position');
			if (isset($data[$p_id])) {
				$data[$p_id]['dept'] = array_merge((array)$data[$p_id]['dept'], (array)$dept);
				$data[$p_id]['pos'] = array_merge((array)$data[$p_id]['pos'], (array)$pos);
			} else {
				$narray = array (
					'person_id' 	=> $p_id,
					'title' 	=> $q->field($row, 'title'),
					'first_name' 	=> $q->field($row, 'first_name'),
					'last_name' 	=> $q->field($row, 'last_name'),
					'constituency' 	=> $q->field($row, 'constituency'),
					'party' 	=> $q->field($row, 'party'),
					'dept'		=> $dept,
					'pos'		=> $pos
				);
				if ($use_standing_down) {
					$narray['standing_down'] = $q->field($row, 'data_value');
				} elseif ($use_extracol) {
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
	function by_peer_name($a, $b) {
		if (!$a['last_name'] && !$b['last_name'])
			return strcmp($a['constituency'], $b['constituency']);
		if (!$a['last_name'])
			return strcmp($a['constituency'], $b['last_name']);
		if (!$b['last_name'])
			return strcmp($a['last_name'], $b['constituency']);
		if (strcmp($a['last_name'], $b['last_name']))
			return strcmp($a['last_name'], $b['last_name']); 
		return strcmp($a['constituency'], $b['constituency']);
	}

	function listoptions($args) {
		global $THEUSER;
		$data = $this->_get_data_by_mps($args);
		if ($THEUSER->isloggedin() && $THEUSER->postcode() != '' || $THEUSER->postcode_is_set()) {
			$MEMBER = new MEMBER(array('postcode'=>$THEUSER->postcode(), 'house' => 1));
			print '<option value="'.$MEMBER->person_id().'">Your MP, '.$MEMBER->full_name().'</option>';
		}
		print '<optgroup label="MPs">';
		foreach ($data['data'] as $row) {
			print '<option';
			if (isset($args['pid']) && $args['pid']==$row['person_id']) print ' selected';
			print ' value="'.$row['person_id'].'">' . $row['first_name'].' '.$row['last_name'];
			print ', ' . $row['constituency'];
			print '</option>';
		}
		print '</optgroup> <optgroup label="Peers">';
		$data = $this->_get_data_by_peers($args);
		foreach ($data['data'] as $row) {
			print '<option';
			if (isset($args['pid']) && $args['pid']==$row['person_id']) print ' selected';
			print ' value="'.$row['person_id'].'">';
			print ucfirst(member_full_name(2, $row['title'], $row['first_name'], $row['last_name'], $row['constituency']));
			print '</option>';
		}
		print '</optgroup> <optgroup label="MLAs">';
		$data = $this->_get_data_by_mlas($args);
		foreach ($data['data'] as $row) {
			print '<option';
			if (isset($args['pid']) && $args['pid']==$row['person_id']) print ' selected';
			print ' value="'.$row['person_id'].'">';
			print ucfirst(member_full_name(3, $row['title'], $row['first_name'], $row['last_name'], $row['constituency']));
			print '</option>';
		}
		print '</optgroup> <optgroup label="MSPs">';
		$data = $this->_get_data_by_msps($args);
		foreach ($data['data'] as $row) {
			print '<option';
			if (isset($args['pid']) && $args['pid']==$row['person_id']) print ' selected';
			print ' value="'.$row['person_id'].'">';
			print ucfirst(member_full_name(4, $row['title'], $row['first_name'], $row['last_name'], $row['constituency']));
			print '</option>';
		}
		print '</optgroup>';
	}

}

