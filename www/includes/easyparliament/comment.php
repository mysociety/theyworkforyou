<?php

/* A class for doing things with single comments.

	To access stuff about an existing comment you can do something like:
		$COMMENT = new COMMENT(37);
		$COMMENT->display();
	Where '37' is the comment_id.
	
	To create a new comment you should get a $data array prepared of
	the key/value pairs needed to create a new comment and do:
		$COMMENT = new COMMENT;
		$COMMENT->create ($data);
	
	You can delete a comment by doing $COMMENT->delete() (it isn't actually 
	deleted from the database, just set to invisible.
	
	You can also do $COMMENT->set_modflag() which happens when a user
	posts a report about a comment. The flag is unset when/if the report is
	rejected.
	
*/



class COMMENT {

	var $comment_id = '';
	var $user_id = '';
	var $epobject_id = '';
	var $body = '';
	var $posted = '';
	var $visible = false;
	var $modflagged = NULL;	// Is a datetime when set.
	var $firstname = '';	// Of the person who posted it.
	var $lastname = '';
	var $url = '';
	
	// So that after trying to init a comment, we can test for 
	// if it exists in the DB.
	var $exists = false;
	
	
	function COMMENT ($comment_id='') {
	
		$this->db = new ParlDB;

		// Set in init.php
		if (ALLOWCOMMENTS == true) {
			$this->comments_enabled = true;
		} else {
			$this->comments_enabled = false;
		}
		
		
		if (is_numeric($comment_id)) {
			// We're getting the data for an existing comment from the DB.
			
			$q = $this->db->query("SELECT user_id,
									epobject_id,
									body,
									posted,
									visible,
									modflagged
							FROM	comments
							WHERE 	comment_id='" . addslashes($comment_id) . "'
							");

			if ($q->rows() > 0) {
				
				$this->comment_id 	= $comment_id;
				$this->user_id		= $q->field(0, 'user_id');
				$this->epobject_id	= $q->field(0, 'epobject_id');
				$this->body			= $q->field(0, 'body');
				$this->posted		= $q->field(0, 'posted');
				$this->visible		= $q->field(0, 'visible');
				$this->modflagged	= $q->field(0, 'modflagged');
				
				// Sets the URL and username for this comment. Duh.
				$this->_set_url();
				$this->_set_username();
				
				$this->exists = true;
			} else {
				$this->exists = false;
			}
		}
	}
	
	
	// Use these for accessing the object's variables externally.
	function comment_id() 	{ return $this->comment_id; }
	function user_id() 		{ return $this->user_id; }
	function epobject_id() 	{ return $this->epobject_id; }
	function body() 		{ return $this->body; }
	function posted() 		{ return $this->posted; }
	function visible() 		{ return $this->visible; }
	function modflagged() 	{ return $this->modflagged; }
	function exists() 		{ return $this->exists; }
	function firstname() 	{ return $this->firstname; }
	function lastname()		{ return $this->lastname; }
	function url()	 		{ return $this->url; }
	
	function comments_enabled() { return $this->comments_enabled; }
	
	
	function create ($data) {
		// Inserts data for this comment into the database.
		// $data has 'epobject_id' and 'body' elements.
		// Returns the new comment_id if successful, false otherwise.
	
		global $THEUSER, $PAGE;
		
		if ($this->comments_enabled() == false) {
			$PAGE->error_message("Sorry, the posting of comments has been temporarily disabled.");
			return;
		}

		if (!$THEUSER->is_able_to('addcomment')) {
			$message = 	array (
				'title' => 'Sorry',
				'text' => 'You are not allowed to post comments.'
			);
			$PAGE->error_message($message);
			return false;
		}
		
		if (!is_numeric ($data['epobject_id'])) {
			$message = array (
				'title' => 'Sorry',
				'text' => "We don't have an epobject id."
			);
			$PAGE->error_message($message);
			return false;
		}
		
		if ($data['body'] == '') {
			$message = array (
				'title' => 'Whoops!',
				'text' => "You haven't entered a comment!."
			);
			$PAGE->error_message($message);
			return false;
		}
		
/*		
		if (is_numeric($THEUSER->user_id())) {
			// Flood check - make sure the user hasn't just posted a comment recently.
			// To help prevent accidental duplicates, among other nasty things.
			
			$flood_time_limit = 60; // How many seconds until a user can post again?
			
			$q = $this->db->query("SELECT comment_id
							FROM	comments
							WHERE	user_id = '" . $THEUSER->user_id() . "'
							AND		posted + 0 > NOW() - $flood_time_limit");

			if ($q->rows() > 0) {
				$message = array (
					'title' => 'Hold your horses!',
					'text' => "We limit people to posting one comment per $flood_time_limit seconds to help prevent duplicate postings. Please go back and try again, thanks."
				);
				$PAGE->error_message($message);
				return false;
			}
		}
*/

		// OK, let's get on with it...

		// Tidy up the HTML tags 
		// (but we don't make URLs into links; only when displaying the comment).
		$body = filter_user_input($data['body'], 'comment'); // In utility.php
		
		$posted = date('Y-m-d H:i:s', time());
							
		
		$q_gid = $this->db->query("select gid from hansard where epobject_id = '".addslashes($data['epobject_id'])."'");
		$data['gid'] = $q_gid->field(0, 'gid');

		$q = $this->db->query("INSERT INTO comments
						(user_id, epobject_id, body, posted, visible, original_gid)
						VALUES
						(
						'" . addslashes($THEUSER->user_id()) . "',
						'" . addslashes($data['epobject_id']) . "',
						'" . addslashes($body) . "',
						'" . $posted . "',
						1,
						'" . addslashes($data['gid']) . "'
						)");
		
		if ($q->success()) {
			// Set the object varibales up.
			$this->comment_id 	= $q->insert_id();
			$this->user_id	  	= $THEUSER->user_id();
			$this->epobject_id 	= $data['epobject_id'];
			$this->body			= $data['body'];
			$this->posted		= $posted;
			$this->visible		= 1;
		
			return $this->comment_id();
			
		} else {
			return false;
		}
	}
	


	function display ($format='html', $template='comments') {

		$data['comments'][0] = array (
			'comment_id'	=> $this->comment_id,
			'user_id'		=> $this->user_id,
			'epobject_id'	=> $this->epobject_id,
			'body'			=> $this->body,
			'posted'		=> $this->posted,
			'modflagged'	=> $this->modflagged,
			'url'			=> $this->url,
			'firstname'		=> $this->firstname,
			'lastname'		=> $this->lastname
		);	

		// Use the same renderer as the COMMENTLIST class.
		$COMMENTLIST = new COMMENTLIST();
		$COMMENTLIST->render($data, $format, $template);
	
	}
	
	
	function set_modflag($switch) {
		// $switch is either 'on' or 'off'.
		// The comment's modflag goes to on when someone reports the comment.
		// It goes to off when a commentreport has been resolved but the
		// comment HASN'T been deleted.
		global $PAGE;
		
		if ($switch == 'on') {
			$date = gmdate("Y-m-d H:i:s");
			$flag = "'$date'";
		
		} elseif ($switch == 'off') {
			$date = NULL;
			$flag = 'NULL';
		
		} else {
			$PAGE->error_message ("Why are you trying to switch this comment's modflag to '".htmlentities($switch)."'!");
		}
		
		$q = $this->db->query("UPDATE comments
						SET		modflagged = $flag
						WHERE 	comment_id = '" . $this->comment_id . "'
						");
		
		if ($q->success()) {
			$this->modflagged = $date;
			return true;
		} else {
			$message = array (
				'title' => 'Sorry',
				'text' => "We couldn't update the comment's modflag."
			);
			$PAGE->error_message($message);
			return false;
		}
		
	}
	


	function delete () {
		// Mark the comment as invisible.
		
		global $THEUSER, $PAGE;
		
		if ($THEUSER->is_able_to('deletecomment')) {
			$q = $this->db->query("UPDATE comments SET visible = '0' WHERE comment_id = '" . $this->comment_id . "'");

			if ($q->success()) {
				return true;
			} else {
				$message = array (
					'title' => 'Sorry',
					'text' => "We were unable to delete the comment."
				);
				$PAGE->error_message($message);
				return false;
			}
		
		} else {
			$message = array (
				'title' => 'Sorry',
				'text' => "You are not authorised to delete comments."
			);
			$PAGE->error_message($message);
			return false;
		}

	}
	
	
	
	function _set_url () {
		global $hansardmajors;
		// Creates and sets the URL for the comment.
				
		if ($this->url == '') {
		
			$q = $this->db->query("SELECT major,
									gid
							FROM	hansard
							WHERE	epobject_id = '" . addslashes($this->epobject_id) . "'
							");
			
			if ($q->rows() > 0) {
				 // If you change stuff here, you might have to change it in 
				 // $COMMENTLIST->_get_comment_data() too...
				 
				$gid = fix_gid_from_db($q->field(0, 'gid')); // In includes/utility.php
			
				$major = $q->field(0, 'major');
				$page = $hansardmajors[$major]['page'];
				$gidvar = $hansardmajors[$major]['gidvar'];
				
				$URL = new URL($page);
				$URL->insert(array($gidvar=>$gid));
				$this->url = $URL->generate() . '#c' . $this->comment_id;
			}	
		}
	}
	
	
	
	function _set_username () {
		// Gets and sets the user's name who posted the comment.
				
		if ($this->firstname == '' && $this->lastname == '') {
			$q = $this->db->query("SELECT firstname,
									lastname
							FROM	users
							WHERE	user_id = '" . addslashes($this->user_id) . "'
							");
							
			if ($q->rows() > 0) {
				$this->firstname = $q->field(0, 'firstname');
				$this->lastname = $q->field(0, 'lastname');
			}
		}
	}

	
	


}

?>
