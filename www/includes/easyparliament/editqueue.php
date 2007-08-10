<?php

/*

[From the WIKI]
The EDITQUEUE is a holding point for submitted content additions or modifications before they are made on the core data. It's quite a wide table, so as to hold as many different types of addition/modification as possible.

These hold details of actions waiting to be approved, such as:

    * creation or modification of glossary entries
    * creation or modification of attributes
    * associations
    * anything else? 

Specifying which of the above is happening is down to the edit_type field.

    * edit_id is the unique id of the EDITQUEUE record
    * user_id is the id of the user who submitted the edit
    * edit_type is the kind of edit being made
    * epobject_id_l and epobject_id_h hold both eopbject_ids in the case of a new association, with epobject_id_l holding the single epobject_id if the edit only applies to one object. If there's a completely new object being submitted, this is left blank until the content is approved and then filled in with the new object id.
    * time_start and time_end are for associations
    * title and body are for new or changed content for a glossary entry
    * submitted is the datetime that the content was initially submitted by the user
    * editor_id is the id of the editor who made the decision on it
    * approved says yay or nay to the edit
    * decided is the datetime when the decision was made 


	While we're here...
	Whenever a term is added to the glossary, it appears in the editqueue first.
	Here it sits until a moderator has approved it, then it goes on it's merry way
	to whichever db it be bound.
	
	Functions:
	
	add()			- pop one on the queue (should then alert moderators somehow)
	approve()		- say "yes!" and forward onwards
	decline()		- an outright "no!", in the bin you go
	refer()			- pass back to the user with suggested alterations
	get_pending()	- fetch a list of all TODOs
	etc...
*/

// [TODO] what happens when two things with the same name are in the editqueue?

class EDITQUEUE {

	var $pending_count = '';
	
	function EDITQUEUE () {
		$this->db = new ParlDB;
	}

	function add($data) {
		// This does the bare minimum.
		// The upper object should make sure it's passsing good data.
		// (for now!)
		
		/*
		print "<pre>";
		print_r ($data);
		print "</pre>";
		*/
		
		// For editqueue in this instance we need:
		//		user_id INTEGER,
		//		edit_type INTEGER,
		//		(epobject_id_l),
		//		title VARCHAR(255),
		//		body TEXT,
		//		submitted DATETIME,
		
		global $THEUSER;
		
		$q = $this->db->query("INSERT INTO editqueue
						(user_id, edit_type, title, body, submitted)
						VALUES
						(
						'" . addslashes($THEUSER->user_id()) . "',
						'" . $data['edit_type'] . "',
						'" . addslashes($data['title']) . "',
						'" . addslashes($data['body']) . "',
						'" . $data['posted'] . "'
						);");
		
		if ($q->success()) {
			// Set the object variables up.
			$this->editqueue_id 	= $q->insert_id();
			$this->title			= $data['title'];
			$this->body				= $data['body'];
			$this->posted			= $data['posted'];

			return $this->editqueue_id;
			
		} else {
			return false;
		}
	}
	
	function approve($data) {
	// Approve items for inclusion
	// Create new epobject and update the editqueue
		
		global $THEUSER;

		// We need a list of editqueue items to play with	
		$this->get_pending();
		if (!isset($this->pending)) {
			return false;
		}		
		$timestamp = date('Y-m-d H:i:s', time());
		
		foreach ($data['approvals'] as $approval_id) {
			// create a new epobject
			// 		title VARCHAR(255),
			// 		body TEXT,
			// 		type INTEGER,
			// 		created DATETIME,
			// 		modified DATETIME,
			/*print "<pre>";
			print_r($data);
			print "</pre>";*/
			// Check to see that we actually have something to approve 
			if (!isset($this->pending[$approval_id])) {
				break;
			}
			$q = $this->db->query("INSERT INTO epobject
							(title, body, type, created)
							VALUES
							('" . addslashes($this->pending[$approval_id]['title']) . "',
							'" . addslashes($this->pending[$approval_id]['body']) . "',
							'" . $data['epobject_type'] . "',
							'" . $timestamp . "');");

			// If that didn't work we can't go any further...
			if (!$q->success()) {
				print "epobject trouble";
				return false;
			}
			$this->current_epobject_id = $q->insert_id();
			
			// depending on the epobject type, we'll need to make
			// entries in different tables. 
			switch ($data['epobject_type']) {
				
				// glossary item
				case 2:
					$previous_insert_id = $q->insert_id();
					$q = $this->db->query("INSERT INTO glossary
									(epobject_id, type, visible)
									VALUES
									('" . $q->insert_id() . "',
									'2',
									'1');");
					// Again, no point carrying on if this fails,
					// so remove the previous entry
					if (!$q->success()) {
						print "glossary trouble!";
						$q = $this->db->query("delete from epobject where epobject_id=" . $previous_insert_id . "");
						return false;
					}
					break;
				
			}
			$this->current_subclass_id = $q->insert_id();

			// Then finally update the editqueue with
			// the new epobject id and approval details.
			$q = $this->db->query("UPDATE editqueue
							SET
							epobject_id_l='" .  $this->current_epobject_id. "',
							editor_id='" . addslashes($THEUSER->user_id()) . "',
							approved='1',
							decided='" . $timestamp . "'
							WHERE edit_id=" . $approval_id . ";");
			if (!$q->success()) {
				break;
			}
			else {
				// Now send them an email telling them they've been approved
				
			
				// Scrub that one from the list of pending items
				unset ($this->pending[$approval_id]);
			}
		}
		
		$this->update_pending_count();
		
		return true;
	}
	
	function decline($data) {
	// Decline a list of term submissions from users
	
		global $THEUSER;

		// We need a list of editqueue items to play with	
		$this->get_pending();
		if (!isset($this->pending)) {
			return false;
		}		
		$timestamp = date('Y-m-d H:i:s', time());

		foreach ($data['declines'] as $decline_id) {
			// Check to see that we actually have something to decline 
			if (!isset($this->pending[$decline_id])) {
				break;
			}

			// Update the editqueue with setting approved=0
			$q = $this->db->query("UPDATE editqueue
							SET
							editor_id='" . addslashes($THEUSER->user_id()) . "',
							approved='0',
							decided='" . $timestamp . "'
							WHERE edit_id=" . $decline_id . ";");
			if (!$q->success()) {
				break;
			}
			else {
				// Scrub that one from the list of pending items
				unset ($this->pending[$decline_id]);
			}
		}
		
		$this->update_pending_count();
		
		return true;

	}
	
	function modify($args) {
	// Moderate a post,
	// log it in editqueue,
	// update glossary_id
	
		// 1. Add the new item into the queue
		$q = $this->db->query();
	
	// 2. if successful, set the previous editqueue item to approved=0;
	
	}
	
	function get_pending() {
	// Fetch all pending editqueue items.
	// Sets $this->pending and returns a body count.
	// Return organised by type? - maybe not for the moment
		
		$q = $this->db->query("SELECT eq.edit_id, eq.user_id, u.firstname, u.lastname, eq.glossary_id, eq.title, eq.body, eq.submitted FROM editqueue AS eq, users AS u WHERE eq.user_id = u.user_id AND eq.approved IS NULL ORDER BY eq.submitted DESC;");
		if ($q->success() && $q->rows()) {
			for ($i = 0; $i < ($q->rows()); $i++) {
				$this->pending[	$q->field($i,"edit_id") ] = $q->row($i);
			}
			
			$this->update_pending_count();
			
			return true;
		}
		else {
			return false;
		}
	}
	
	function display() {
	// Print all our pending items out in a nice list or something
	// Add links later for "approve, decline, refer"
	// Just get the fucker working for now
		
			$URL = new URL('admin_glossary_pending');
			$URL->reset();
			$form_link = $URL->generate('url');
		
		?><form action="<?php echo $form_link ?>" method="post"><?php
		foreach ($this->pending as $editqueue_id => $pender) {
			
			$URL = new URL('admin_glossary_pending');
			$URL->insert(array('approve' => $editqueue_id));
			$approve_link = $URL->generate('url');

			$URL = new URL('admin_glossary_pending');
			$URL->insert(array('modify' => $editqueue_id));
			$modify_link = $URL->generate('url');

			$URL = new URL('admin_glossary_pending');
			$URL->insert(array('decline' => $editqueue_id));
			$decline_link = $URL->generate('url');
			
			?><div class="pending-item"><label for="<?php echo $editqueue_id; ?>"><input type="checkbox" name="approve[]" value="<?php echo $editqueue_id; ?>" id="<?php echo $editqueue_id; ?>"><strong><?php echo $pender['title']; ?></strong></label>
			<p><?php echo $pender['body']; ?><br>
			<small>
				<a href="<?php echo $approve_link; ?>">approve</a>
				&nbsp;|&nbsp;
				<a href="<?php echo $modify_link; ?>">modify</a>
				&nbsp;|&nbsp;
				<a href="<?php echo $decline_link; ?>">decline</a>
				<br>Submitted by: <em><?php echo $pender['firstname']; ?>&nbsp;<?php echo $pender['lastname']; ?></em>
			</small></p></div>
		<?php
		}
		?><input type="submit" value="Approve checked items">
		</form><?php
	}

///////////////////
// PRIVATE FUNCTIONS

	function update_pending_count() {
	// Just makes sure we're showing the right number of pending items
		$this->pending_count = count($this->pending);
	}

}


// Glossary overrides
class GLOSSEDITQUEUE extends EDITQUEUE {

	function approve($data) {
	// Approve items for inclusion
	// Create new epobject and update the editqueue
		
		global $THEUSER;

		// We need a list of editqueue items to play with	
		$this->get_pending();
		if (!isset($this->pending)) {
			return false;
		}		
		$timestamp = date('Y-m-d H:i:s', time());
		
		foreach ($data['approvals'] as $approval_id) {
			// create a new epobject
			// 		title VARCHAR(255),
			// 		body TEXT,
			// 		type INTEGER,
			// 		created DATETIME,
			// 		modified DATETIME,
			/*print "<pre>";
			print_r($data);
			print "</pre>";*/
			// Check to see that we actually have something to approve 
			if (!isset($this->pending[$approval_id])) {
				break;
			}
			$q = $this->db->query("INSERT INTO glossary
							(title, body, type, created, visible)
							VALUES
							('" . addslashes($this->pending[$approval_id]['title']) . "',
							'" . addslashes($this->pending[$approval_id]['body']) . "',
							'" . $data['epobject_type'] . "',
							'" . $timestamp . "',
							1);");

			// If that didn't work we can't go any further...
			if (!$q->success()) {
				print "glossary trouble";
				return false;
			}
			$this->current_epobject_id = $q->insert_id();

			// Then finally update the editqueue with
			// the new epobject id and approval details.
			$q = $this->db->query("UPDATE editqueue
							SET
							glossary_id='" .  $this->current_epobject_id. "',
							editor_id='" . addslashes($THEUSER->user_id()) . "',
							approved='1',
							decided='" . $timestamp . "'
							WHERE edit_id=" . $approval_id . ";");
			if (!$q->success()) {
				break;
			}
			else {
				// Scrub that one from the list of pending items
				unset ($this->pending[$approval_id]);
			}
		}
		
		$this->update_pending_count();
		
		return true;
	}

}

?>
