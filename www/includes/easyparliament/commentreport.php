<?php

/*	Comment reports are when a user complains about a comment.
	A report is logged and an admin user can then either approve or reject
	the report. If they approve, the associated comment is deleted.
	

	To create a new comment report:
		$REPORT = new COMMENTREPORT;
		$REPORT->create($data);
	
	To view info about an existing report:
		$REPORT = new COMMENTREPORT($report_id);
		$REPORT->display();
		
	You can also do $REPORT->lock() and $REPORT->unlock() to ensure only 
	one person can process a report at a time.
	
	And finally you can $REPORT->resolve() to approve or reject the report.

*/

class COMMENTREPORT {

	var $report_id = '';
	var $comment_id = '';
	var $firstname = '';
	var $lastname = '';
	var $body = '';
	var $reported = NULL;	// datetime
	var $resolved = NULL; 	// datetime
	var $resolvedby = ''; 	// user_id
	var $locked = NULL; 	// datetime
	var $lockedby = '';		// user_id
	var $upheld = ''; 		// boolean

	// If the user was logged in, this will be set:
	var $user_id = '';
	// If the user wasn't logged in, this will be set:
	var $email = '';
	

	function COMMENTREPORT ($report_id='') {
		// Pass it a report id and it gets and sets this report's data.

		$this->db = new ParlDB;
		
		if (is_numeric($report_id)) {

			$q = $this->db->query("SELECT commentreports.comment_id,
									commentreports.user_id,
									commentreports.body,
									DATE_FORMAT(commentreports.reported, '" . SHORTDATEFORMAT_SQL . ' ' . TIMEFORMAT_SQL . "') AS reported,
									DATE_FORMAT(commentreports.resolved, '" . SHORTDATEFORMAT_SQL . ' ' . TIMEFORMAT_SQL . "') AS resolved,
									commentreports.resolvedby,
									commentreports.locked,
									commentreports.lockedby,
									commentreports.upheld,
									commentreports.firstname,
									commentreports.lastname,
									commentreports.email,
									users.firstname AS u_firstname,
									users.lastname AS u_lastname
							FROM	commentreports,
									users
							WHERE	commentreports.report_id = '" . mysql_escape_string($report_id) . "'
							AND		commentreports.user_id = users.user_id
							");
	
			if ($q->rows() > 0) {						
				$this->report_id		= $report_id;
				$this->comment_id 		= $q->field(0, 'comment_id');
				$this->body 			= $q->field(0, 'body');
				$this->reported 		= $q->field(0, 'reported');
				$this->resolved 		= $q->field(0, 'resolved');
				$this->resolvedby 		= $q->field(0, 'resolvedby');
				$this->locked 			= $q->field(0, 'locked');
				$this->lockedby			= $q->field(0, 'lockedby');
				$this->upheld 			= $q->field(0, 'upheld');
				
				if ($q->field(0, 'user_id') == 0) {
					// The report was made by a non-logged-in user.
					$this->firstname = $q->field(0, 'firstname');
					$this->lastname = $q->field(0, 'lastname');
					$this->email = $q->field(0, 'email');
				} else {
					// The report was made by a logged-in user.
					$this->firstname = $q->field(0, 'u_firstname');
					$this->lastname = $q->field(0, 'u_lastname');
					$this->user_id = $q->field(0, 'user_id');
				}
			} else {
				$q = $this->db->query("SELECT commentreports.comment_id,
									commentreports.user_id,
									commentreports.body,
									DATE_FORMAT(commentreports.reported, '" . SHORTDATEFORMAT_SQL . ' ' . TIMEFORMAT_SQL . "') AS reported,
									DATE_FORMAT(commentreports.resolved, '" . SHORTDATEFORMAT_SQL . ' ' . TIMEFORMAT_SQL . "') AS resolved,
									commentreports.resolvedby,
									commentreports.locked,
									commentreports.lockedby,
									commentreports.upheld,
									commentreports.firstname,
									commentreports.lastname,
									commentreports.email
							FROM	commentreports
							WHERE	commentreports.report_id = '" . mysql_escape_string($report_id) . "'");
	
				if ($q->rows() > 0) {						
				$this->report_id		= $report_id;
				$this->comment_id 		= $q->field(0, 'comment_id');
				$this->body 			= $q->field(0, 'body');
				$this->reported 		= $q->field(0, 'reported');
				$this->resolved 		= $q->field(0, 'resolved');
				$this->resolvedby 		= $q->field(0, 'resolvedby');
				$this->locked 			= $q->field(0, 'locked');
				$this->lockedby			= $q->field(0, 'lockedby');
				$this->upheld 			= $q->field(0, 'upheld');
				$this->firstname = $q->field(0, 'firstname');
				$this->lastname = $q->field(0, 'lastname');
				$this->email = $q->field(0, 'email');
				}
			}
		}	
	}
	
	
	function report_id () 		{ return $this->report_id; }
	function comment_id () 		{ return $this->comment_id; }
	function user_id () 		{ return $this->user_id; }
	function user_name () 		{ return $this->firstname . ' ' . $this->lastname; }
	function firstname ()		{ return $this->firstname; }
	function lastname () 		{ return $this->lastname; }
	function email ()			{ return $this->email; }
	function body () 			{ return $this->body; }
	function reported () 		{ return $this->reported; }
	function resolved () 		{ return $this->resolved; }
	function resolvedby () 		{ return $this->resolvedby; }
	function locked () 			{ return $this->locked; }
	function lockedby () 		{ return $this->lockedby; }
	function upheld () 			{ return $this->upheld; }


	function create ($COMMENT, $reportdata) {
		// For when a user posts a report on a comment.
		// $reportdata is an array like:
		//	array (
		//		'body' => 'some text',
		//		'firstname'	=> 'Billy',
		//		'lastname'	=> 'Nomates',
		//		'email'		=> 'billy@nomates.com'
		//	)
		// But if the report was made by a logged-in user, only the
		// 'body' element should really contain anything, because
		// we use $THEUSER's id to get the rest.
		
		// $COMMENT is an existing COMMENT object, needed for setting
		// its modflag and comment_id.
		
		global $THEUSER, $PAGE;

		if (!$THEUSER->is_able_to('reportcomment')) {
			$PAGE->error_message ("Sorry, you are not allowed to post reports.");
			return false;
		}

		if (is_numeric($THEUSER->user_id()) && $THEUSER->user_id() > 0) {
			// Flood check - make sure the user hasn't just posted a report recently.
			// To help prevent accidental duplicates, among other nasty things.
			// (Non-logged in users are all id == 0.)
			
			$flood_time_limit = 20; // How many seconds until a user can post again?
			
			$q = $this->db->query("SELECT report_id
							FROM	commentreports
							WHERE	user_id = '" . $THEUSER->user_id() . "'
							AND		reported + 0 > NOW() - $flood_time_limit");
			
			if ($q->rows() > 0) {
				$PAGE->error_message("Sorry, we limit people to posting one report per $flood_time_limit seconds to help prevent duplicate reports. Please go back and try again, thanks.");
				return false;
			}
		}
		

		// Tidy up body.
		$body = filter_user_input($reportdata['body'], 'comment'); // In utility.php
		
		$time = gmdate("Y-m-d H:i:s");

		if ($THEUSER->isloggedin()) {
			$sql = "INSERT INTO commentreports
									(comment_id, body, reported, user_id)
							VALUES	('" . mysql_escape_string($COMMENT->comment_id()) . "',
									'" . mysql_escape_string($body) . "', 
									'$time',
									'" . mysql_escape_string($THEUSER->user_id()) . "'
									) 
						";
		} else {
			$sql = "INSERT INTO commentreports
									(comment_id, body, reported, firstname, lastname, email)
							VALUES	('" . mysql_escape_string($COMMENT->comment_id()) . "',
									'" . mysql_escape_string($body) . "', 
									'$time',
									'" . mysql_escape_string($reportdata['firstname']) . "',
									'" . mysql_escape_string($reportdata['lastname']) . "',
									'" . mysql_escape_string($reportdata['email']) . "'
									) 
						";
		}
			
		$q = $this->db->query($sql);
		
		if ($q->success()) {
			// Inserted OK, so set up this object's variables.
			$this->report_id 	= $q->insert_id();
			$this->comment_id 	= $COMMENT->comment_id();
			$this->body			= $body;
			$this->reported		= $time;
			
			if ($THEUSER->isloggedin()) {
				$this->user_id		= $THEUSER->user_id();
				$this->firstname	= $THEUSER->firstname();
				$this->lastname		= $THEUSER->lastname();
			} else {
				$this->email		= $reportdata['email'];
				$this->firstname 	= $reportdata['firstname'];
				$this->lastname		= $reportdata['lastname'];
			}
				
			
			// Set the comment's modflag to on.
			$COMMENT->set_modflag('on');
			
			
			// Notify those who need to know that there's a new report.
			
			$URL = new URL('admin_commentreport');
			$URL->insert(array(
				'rid'=>$this->report_id,
				'cid'=>$this->comment_id
			));
			
			$emailbody = "A new comment report has been filed by " . $this->user_name() . ".\n\n";
			$emailbody .= "COMMENT:\n" . $COMMENT->body() . "\n\n";
			$emailbody .= "REPORT:\n" . $this->body . "\n\n";
			$emailbody .= "To manage this report follow this link: http://" . DOMAIN . $URL->generate('none') . "\n";
			
			send_email(REPORTLIST, 'New comment report', $emailbody);
			
			
			// Send an email to the user to thank them.

			if ($THEUSER->isloggedin()) {
				$email = $THEUSER->email();
			} else {
				$email = $this->email();
			}
				
			$data = array (
				'to' 			=> $email,
				'template' 		=> 'report_acknowledge'
			);
			$merge = array (
				'FIRSTNAME' 	=> $this->firstname(),
				'LASTNAME' 		=> $this->lastname(),
				'COMMENTURL' 	=> "http://" . DOMAIN . $COMMENT->url(),
				'REPORTBODY' 	=> strip_tags($this->body())
			);
			
			
			// send_template_email in utility.php.
			send_template_email($data, $merge);
				
			return true;
		} else {
			return false;
		}
		
	}


	function display () {

		$data = array();
		
		if (is_numeric($this->report_id)) {
			$data = array (
				'report_id' 	=> $this->report_id(),
				'comment_id' 	=> $this->comment_id(),
				'user_id' 		=> $this->user_id(),
				'user_name' 	=> $this->user_name(),
				'body' 			=> $this->body(),
				'reported' 		=> $this->reported(),
				'resolved' 		=> $this->resolved(),
				'resolvedby' 	=> $this->resolvedby(),
				'locked' 		=> $this->locked(),
				'lockedby'		=> $this->lockedby(),
				'upheld'	 	=> $this->upheld()
			);
		} 
				
		$this->render($data);
	}
	
	
	
	function render($data) {
		global $PAGE;
		
		$PAGE->display_commentreport($data);
	
	}
	
	
	function lock () {
		// Called when an admin user goes to examine a report, so that
		// only one person can edit at once.		

		global $THEUSER, $PAGE;
		
		if ($THEUSER->is_able_to('deletecomment')) {
			$time = gmdate("Y-m-d H:i:s");
			
			$q = $this->db->query ("UPDATE commentreports
							SET		locked = '$time',
									lockedby = '" . $THEUSER->user_id() . "'
							WHERE	report_id = '" . $this->report_id . "'
							");
		
			if ($q->success()) {
				$this->locked = $time;
				$this->lockedby = $THEUSER->user_id();
				return true;
			} else {
				$PAGE->error_message ("Sorry, we were unable to lock this report.");
				return false;
			}
		} else {
			$PAGE->error_message ("You are not authorised to delete comments.");
			return false;
		}
	}
	
	
	function unlock () {
		// Unlock a comment so it can be examined by someone else.
	
		$q = $this->db->query ("UPDATE commentreports
						SET		locked = NULL,
								lockedby = NULL
						WHERE	report_id = '" . $this->report_id . "'
						");
	
		if ($q->success()) {
			$this->locked = NULL;
			$this->lockedby = NULL;
			return true;
		} else {
			return false;
		}
	}
	


	function resolve ($upheld, $COMMENT) {
		// Resolve a report.
		// $upheld is true or false.
		// $COMMENT is an existing COMMENT object - we need this so 
		// that we can set its modflagged to off and/or delete it.
		global $THEUSER, $PAGE;
		
		$time = gmdate("Y-m-d H:i:s");
		
		if ($THEUSER->is_able_to('deletecomment')) {
			// User is allowed to do this.
		
			if (!$this->resolved) {
				// Only if this report hasn't been previously resolved.
				
				if ($upheld) {
					
					$success = $COMMENT->delete();
					
					if (!$success) {
						// Abort!
						return false;
					}
					
					$upheldsql = '1';
					
				} else {
					$upheldsql = '0';
					
					// Report has been removed, so un-modflag this comment.
					$COMMENT->set_modflag('off');
				}
		
				$q = $this->db->query("UPDATE commentreports 
								SET 	resolved = '$time',
										resolvedby = '" . mysql_escape_string($THEUSER->user_id()) . "',
										locked = NULL,
										lockedby = NULL,
										upheld = '$upheldsql'
								WHERE 	report_id = '" . mysql_escape_string($this->report_id) . "'
								");
								
				if ($q->success()) {
				
					$this->resolved = $time;
					$this->resolvedby = $THEUSER->user_id();
					$this->locked = NULL;
					$this->lockedby = NULL;
					$this->upheld = $upheld;
					
					return true;
				} else {
					$PAGE->error_message ("Sorry, we couldn't resolve this report.");
					return false;
				}
			} else {
				$PAGE->error_message ("This report has already been resolved (on " . $this->resolved . ")");
				return false;
			}

		} else {
			$PAGE->error_message ("You are not authorised to resolve reports.");
			return false;
		}	
	}

	

}

?>
