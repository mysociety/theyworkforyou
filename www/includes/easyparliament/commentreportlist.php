<?php

/* 	Just for displaying lists of comment reports.
	Do
		$LIST = new COMMENTREPORTLIST;
		$LIST->display();
	
	The display stuff could all be extended more like COMMENTLIST if we need
	anything more advanced (templates, different formats, different types of
	data fetched, etc.). 
*/


class COMMENTREPORTLIST {

	function COMMENTREPORTLIST () {
		$this->db = new ParlDB;
	}
	
	
	function display () {
	
		// Update any locks on the reports before we get the data.
		// So that if we're displaying a list of reports that are
		// available for editing, it's accurate.
		$this->_update_locked();
	
		$data = $this->_get_data_by_recent ();
		
		$this->render($data);
	
	}
	
	
	function render ($data) {

		global $PAGE;
		
		$PAGE->display_commentreportlist($data);
	
	}
	
	
	function _update_locked () {
		// Unlocks any reports that have been locked for more than some minutes.
		
		// How old do locks have to be to get unlocked?
		$minutes = 10;
		
		$time = gmdate("Y-m-d H:i:s", (time() - ($minutes * 60)));
		
		$q = $this->db->query("UPDATE commentreports
						SET 	locked = NULL,
								lockedby = 0
						WHERE	locked < '$time'
						");
	
	}
	

	function _get_data_by_recent () {
		// Returns the most recent comment reports in an array.
		// The array is suitable for rendering by $PAGE->display_table.
		// Contains 'header' and 'rows' arrays.
		
		$number_to_fetch = 100;
		
		$q = $this->db->query("SELECT comments.comment_id,
								commentreports.report_id,
								commentreports.body,
								DATE_FORMAT(commentreports.reported, '" . SHORTDATEFORMAT_SQL . ' ' . TIMEFORMAT_SQL . "') AS reported,
								commentreports.locked,
								users.firstname,
								users.lastname
						FROM	comments,
								users,
								commentreports
						WHERE	commentreports.resolved IS NULL
						AND		commentreports.comment_id = comments.comment_id
						AND		commentreports.user_id = users.user_id
						ORDER BY commentreports.reported ASC
						LIMIT	$number_to_fetch
						");

		$r = $this->db->query("SELECT comments.comment_id,
		commentreports.report_id,
		commentreports.body,
		DATE_FORMAT(commentreports.reported, '". SHORTDATEFORMAT_SQL . ' ' . TIMEFORMAT_SQL . "') AS reported,
		commentreports.locked,
		commentreports.firstname,
		commentreports.lastname
		FROM comments, commentreports
		WHERE commentreports.resolved IS NULL
		AND commentreports.comment_id = comments.comment_id
		AND commentreports.user_id IS NULL
		ORDER BY commentreports.reported ASC
		LIMIT $number_to_fetch");

		$data = array();
		
		if ($q->rows() > 0) {
		
			for ($n=0; $n<$q->rows(); $n++) {
								
				$data[] = array (
					'report_id'		=> $q->field($n,'report_id'),
					'comment_id' 	=> $q->field($n,'comment_id'),
					'firstname'		=> $q->field($n, 'firstname'),
					'lastname'		=> $q->field($n, 'lastname'),
					'body'			=> $q->field($n, 'body'),
					'reported'		=> $q->field($n, 'reported'),
					'locked'		=> $q->field($n, 'locked')
				);
			}
			
		}
		if ($r->rows() > 0) {
			for ($n=0; $n<$r->rows(); $n++) {
				$data[] = array (
					'report_id'		=> $r->field($n,'report_id'),
					'comment_id' 	=> $r->field($n,'comment_id'),
					'firstname'		=> $r->field($n, 'firstname'),
					'lastname'		=> $r->field($n, 'lastname'),
					'body'			=> $r->field($n, 'body'),
					'reported'		=> $r->field($n, 'reported'),
					'locked'		=> $r->field($n, 'locked')
				);
			}
		}

		return $data;
	}
	
	

}

?>
