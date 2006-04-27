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
	
		$validviews = array('mps', 'peers');
		
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
		
		include (FILEPATH."/../includes/easyparliament/templates/$format/people_$view" . ".php");
		return true;
	
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

		$order = 'last_name';
		$sqlorder = 'last_name, first_name';
		$query = 'SELECT person_id, title, first_name, last_name, constituency, party, dept, position
			FROM member LEFT OUTER JOIN moffice ON member.person_id = moffice.person AND to_date="9999-12-31"
			WHERE house=' . $args['house'] . ' AND left_house = (SELECT MAX(left_house) FROM member) ';
		if (isset($args['order'])) {
			if ($args['order'] == 'first_name') {
				$order = 'first_name';
				$sqlorder = 'first_name, last_name';
			} elseif ($args['order'] == 'constituency') {
				$order = 'constituency';
				$sqlorder = 'constituency';
			} elseif ($args['order'] == 'party') {
				$order = 'party';
				$sqlorder = 'party, last_name, first_name, constituency';
			} elseif ($args['order'] == 'expenses') {
				$order = 'expenses';
				$sqlorder = 'data_value+0 DESC, last_name, first_name';
				$query = 'SELECT member.person_id, first_name, last_name, constituency, party, dept, position, data_value
					FROM member LEFT OUTER JOIN moffice ON member.person_id=moffice.person AND to_date="9999-12-31", personinfo
					WHERE member.person_id = personinfo.person_id AND house=1 AND left_house = (SELECT MAX(left_house) FROM member)
					AND data_key="expenses2004_total" ';
			} elseif ($args['order'] == 'debates') {
				$order = 'debates';
				$sqlorder = 'data_value+0 DESC, last_name, first_name';
				$query = 'SELECT member.person_id, first_name, last_name, constituency, party, dept, position, data_value
					FROM member LEFT OUTER JOIN moffice ON member.person_id=moffice.person AND to_date="9999-12-31", personinfo
					WHERE member.person_id = personinfo.person_id AND house=1 AND left_house = (SELECT MAX(left_house) FROM member)
					AND data_key="debate_sectionsspoken_inlastyear" ';
			}
		}
		
		$q = $this->db->query($query . "ORDER BY $sqlorder");
	
		$data = array();
		
		for ($row=0; $row<$q->rows(); $row++) {
			$p_id = $q->field($row, 'person_id');
			$dept = $q->field($row, 'dept');
			$pos = $q->field($row, 'position');
			if (isset($data[$p_id])) {
				$data[$p_id]['dept'] = array_merge($data[$p_id]['dept'], $dept);
				$data[$p_id]['pos'] = array_merge($data[$p_id]['pos'], $pos);
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
				if ($order=='expenses' || $order=='debates') {
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
		if ($args['house'] == 2)
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
		return strcmp($a['last_name'], $b['last_name']); 
	}

	function listoptions($args) {
		global $THEUSER;
		$data = $this->_get_data_by_mps($args);
		if ($THEUSER->isloggedin() && $THEUSER->postcode() != '' || $THEUSER->postcode_is_set()) {
			$MEMBER = new MEMBER(array('postcode'=>$THEUSER->postcode()));
			print '<option value="'.$MEMBER->person_id().'">Your MP, '.$MEMBER->full_name().'</option>';
		}
		print '<optgroup label="MPs">';
		$opik = array();
		foreach ($data['data'] as $row) {
			// Lembit Opik is special
		        if ($row['last_name']=='&Ouml;pik') {
		                $opik = $row;
		                continue;
		        }
			if (count($opik) && strcmp('Opik', $row['last_name'])<0) {
				print '<option value="'.$opik['person_id'].'">' . $opik['first_name'].' '.$opik['last_name'].'</option>';
				$opik = array();
			}
			print '<option';
			if (isset($args['pid']) && $args['pid']==$row['person_id']) print ' selected';
			print ' value="'.$row['person_id'].'">' . $row['first_name'].' '.$row['last_name'].'</option>';
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
		print '</optgroup>';
	}

}

?>
