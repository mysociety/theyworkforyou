<?php

/*

The Glossary item handles:
	1. Search matching for particular items.
	2. Addition of glossary items
	3. Removal of glossary items
	4. Notification of pending glossary additions

Glossary items can only (at present) be added on the Search page,
and only in the event that the term has not already been defined.

[?] will it be possible to amend the term?

It should not be possible to add a term if no results are found during the search.

All Glossary items need to be confirmed by a moderator (unless posted by a moderator).
As they are being approved/declined they can be modified (spelling etc...).

*/

// This handles basic insertion and approval functions for all epobjects
include_once INCLUDESPATH."easyparliament/editqueue.php";
include_once INCLUDESPATH."easyparliament/searchengine.php";
include_once INCLUDESPATH."wikipedia.php";

class GLOSSARY {
	
	var $num_terms;			// how many glossary entries do we have
							// (changes depending on how GLOSSARY is called
	var $hansard_count;		// how many times does the phrase appear in hansard?
	var $query;				// search term
	var $glossary_id;		// if this is set then we only have 1 glossary term
	var $current_term;		// will only be set if we have a valid epobject_id
	var $current_letter;

	// constructor...
	function GLOSSARY($args=array()) {
	// We can optionally start the glossary with one of several arguments
	//		1. glossary_id - treat the glossary as a single term
	//		2. glossary_term - search within glossary for a term
	// With no argument it will pick up all items.

			$this->db = new ParlDB;
			
            $this->replace_order = array();
            $got = false;
			if (isset($args['s']) && ($args['s'] != "")) {
				$args['s'] = urldecode($args['s']);
				$this->search_glossary($args);
				$got = $this->get_glossary_item($args);
			}
			else {
				$got = $this->get_glossary_item($args);
			}
			if ($got && isset($args['sort']) && ($args['sort'] == 'regexp_replace')) {
				// We need to sort the terms in the array by "number of words in term".
				// This way, "prime minister" gets dealt with before "minister" when generating glossary links.
				
				// sort by number of words
				foreach ($this->terms as $glossary_id => $term) {
					$this->replace_order[$glossary_id] = count(explode(" ", $term['title']));
				}
				arsort($this->replace_order);
				
				// secondary sort for number of letters?
				// pending functionality...
				
				// We can either turn off the "current term" completely -
				// so that it never links to its own page,
				// Or we can handle it in $this->glossarise below
				/*
				if (isset($this->epobject_id)) {
					unset ($this->replace_order[$this->epobject_id]);
				}
				*/
			}
			
			// These stop stupid submissions.
			// everything should be lowercase.
			$this->stopwords = array( "the", "of", "to", "and", "for", "in", "a", "on", "is", "that", "will", "secretary", "are", "ask", "state", "have", "be", "has", "by", "with", "i", "not", "what", "as", "it", "hon", "he", "which", "from", "if", "been", "this", "s", "we", "at", "government", "was", "my", "an", "department", "there", "make", "or", "made", "their", "all", "but", "they", "how", "debate" );

	}

	function get_glossary_item($args=array()) {
		// Search for and fetch glossary item with title or glossary_id
		// We could also search glossary text that contains the title text, for cross references

		$this->alphabet = array();
		foreach (range ("A", "Z") as $letter) {
			$this->alphabet[$letter] = array();
		}
		
		$orderby = " ORDER by g.title";
		$where = " g.glossary_id=eq.glossary_id AND u.user_id=eq.user_id AND g.visible=1 AND eq.approved=1";		
		$q = $this->db->query("SELECT g.glossary_id, g.title, g.body, u.user_id, u.firstname, u.lastname FROM editqueue AS eq, glossary AS g, users AS u WHERE " . $where . $orderby . ";");
		if ($q->success() && $q->rows()) {
			for ($i=0; $i < $q->rows(); $i++) {
				$this->terms[ $q->field($i,"glossary_id") ] = $q->row($i);
				// Now add the epobject to the alphabet navigation.
				$first_letter = strtoupper(substr($q->field($i,"title"),0,1));
				$this->alphabet[$first_letter][] = $q->field($i,"glossary_id");
			}
			
			$this->num_terms = $q->rows();
		
			// If we were given a glossary_id, then we need one term in particular,
			// as well as knowing the next and previous terms for the navigation
			if (isset($args['glossary_id']) && ($args['glossary_id'] != "")) {
				$next = 0;
				foreach ($this->terms as $term) {
					if ($next == 1) {
						$this->next_term = $term;
						break;
					}
					elseif ($term['glossary_id'] == $args['glossary_id']) {
						$this->glossary_id = $args['glossary_id'];
						$this->current_term = $term;
						$next = 1;
						
					}
					else {
						$this->previous_term = $term;
					}
				}
				// The first term in the list has no previous, so we'll make it the last term
				if (!isset($this->previous_term)) {
					$this->previous_term = array_pop($this->terms);
				}
				// and the last has no next, so we'll make it the first
				if (!isset($this->next_term)) {
					$this->next_term = array_shift($this->terms);
				}
			}
			
			return ($this->num_terms);
		}
		else {
			return false;
		}
	}

	function search_glossary($args=array()) {
		// Search for and fetch glossary item with a title
		// Useful for the search page, and nowhere else (so far)
		
		// No point going any further without a term to search for
		if (isset($args['s'])) {
			$this->query = addslashes($args['s']);
			$this->search_matches = array();
			$this->num_search_matches = 0;
		} else { 
			return false;
		}
				
		$where = "g.glossary_id=eq.glossary_id AND u.user_id=eq.user_id AND g.visible=1 AND g.title LIKE '%" . $this->query . "%'";
		$orderby = " ORDER by g.title";
		
		$query = "SELECT g.glossary_id, g.title, g.body, u.user_id, u.firstname, u.lastname FROM editqueue AS eq, glossary AS g, users AS u WHERE " . $where . $orderby . ";";

		$q = $this->db->query($query);
		if ($q->success() && $q->rows()) {
			for ($i=0; $i < $q->rows(); $i++) {
				$this->search_matches[ $q->field($i,"glossary_id") ] = $q->row($i);
			}
			$this->num_search_matches = $q->rows();
			return ($this->num_search_matches);
		}
		else {
			return false;
		}
	}

	function get_most_recent($n) {
		// Fetch the $n most recent Glossary additions.
		// In the absence of $n fetches all glossary items ordered most recent first
	}
	
	function get_unmoderated() {
		// Fetch a list of all unmoderated (i.e. pending) comments.
	}

	function create(&$data) {
		// Add a Glossary definition.
		// Sets visiblity to 0, and awaits moderator intervention.
		// For this we need to start up an epobject of type 2 and then an editqueue item
		// where editqueue.epobject_id_l = epobject.epobject_id
		
		$EDITQUEUE = new GLOSSEDITQUEUE();
		
		/*
		print "<pre>";
		print_r ($data);
		print "</pre>";
		*/
		
		// Assuming that everything is ok, we will need:
		// For epobject:
		// 		title VARCHAR(255),
		// 		body TEXT,
		// 		type INTEGER,
		// 		created DATETIME,
		// 		modified DATETIME,
		// and for editqueue:
		//		edit_id INTEGER PRIMARY KEY NOT NULL,
		//		user_id INTEGER,
		//		edit_type INTEGER,
		//		epobject_id_l INTEGER,
		//		title VARCHAR(255),
		//		body TEXT,
		//		submitted DATETIME,
		//		editor_id INTEGER,
		//		approved BOOLEAN,
		//		decided DATETIME
		
		global $THEUSER;

		if (!$THEUSER->is_able_to('addterm')) {
			error ("Sorry, you are not allowed to add Glossary terms.");
			return false;
		}
		
		if ($data['title'] == '') {
			error ("Sorry, you can't define a term without a title");
			return false;
		}
		
		if ($data['body'] == '') {
			error ("You haven't entered a definition!");
			return false;
		}
		
		if (is_numeric($THEUSER->user_id())) {
			// Flood check - make sure the user hasn't just posted a term recently.
			// To help prevent accidental duplicates, among other nasty things.
			
			$flood_time_limit = 20; // How many seconds until a user can post again?
			
			$q = $this->db->query("SELECT glossary_id
							FROM	editqueue
							WHERE	user_id = '" . $THEUSER->user_id() . "'
							AND		submitted + 0 > NOW() - $flood_time_limit");

			if ($q->rows() > 0) {
				error("Sorry, we limit people to posting one term per $flood_time_limit seconds to help prevent duplicate postings. Please go back and try again, thanks.");
				return false;
			}
		}
		
		// OK, let's get on with it...

		// Tidy up the HTML tags 
		// (but we don't make URLs into links; only when displaying the comment).
		// We can display Glossary terms the same as the comments
		$data['title'] = filter_user_input($data['title'], 'comment_title'); // In utility.php
		$data['body'] = filter_user_input($data['body'], 'comment'); // In utility.php
		// Add the time and the edit type for the editqueue
		$data['posted'] = date('Y-m-d H:i:s', time());
		$data['edit_type'] = 2;
		
		// Add the item to the edit queue
		$success = $EDITQUEUE->add($data);
		
		if ($success) {
			return ($success);	
		} else {
			return false;
		}
	}
	
	function delete($glossary_id)
	{
		
		$q = $this->db->query("DELETE from glossary where glossary_id=".$glossary_id." LIMIT 1;");
		// if that worked, we need to update the editqueue,
		// and remove the term from the already generated object list.
		if ($q->affected_rows() >= 1) {
			unset($this->replace_order[$glossary_id]);
			unset($this->terms[$glossary_id]);
		}
	}
	
	function glossarise($body, $tokenize=0, $urlize=0) {
	// Turn a body of text into a link-up wonderland of glossary joy
	
		global $this_page;
	
		$findwords = array();
		$replacewords = array();
		$this->titlesfind = array();
		$this->titlesreplace = array();
		$URL = new URL("glossary");
		$URL->insert(array("gl" => ""));
	
		// External links shown within their own definition
		// should be the complete and linked url.
		// NB. This should only match when $body is a definition beginning with "http:"
		if (preg_match("/^(http:*[^\s])$/i", $body)) {
			$body = "<a href=\"" . $body . "\" title=\"External link to " . $body . "\">" . $body . "</a>";
			return ($body);

		} 

		// otherwise, just replace everything.

		// generate links from URL when wanted
		// NB WRANS is already doing this
		if ($urlize == 1) {
			$body = preg_replace("~(http(s)?:\/\/[^\s\n]*)\b(\/)?~i", "<a href=\"\\0\">\\0</a>", $body);
		}
		
		// check for any glossary terms to replace
		foreach ($this->replace_order as $glossary_id => $count) {
			
			$term_body = $this->terms[$glossary_id]['body'];
			$term_title = $this->terms[$glossary_id]['title'];
			
			$URL->update(array("gl" => $glossary_id));
			$findwords[$glossary_id] = "/([^>\.\'\/])\b(" . $term_title . ")\b([^<\'])/i";
			// catch glossary terms within their own definitions
			if ($glossary_id == $this->glossary_id) {
				$replacewords[] = "\\1<strong>\\2</strong>\\3";
			}
			else {
				if ($this_page == "admin_glossary"){
					$link_url = "#gl".$glossary_id;
				}
				else {
					$link_url = $URL->generate('url');
				}
				$replacewords[] = "\\1<a href=\"" . $link_url . "\" title=\"##". $glossary_id . "##\" class=\"glossary\">\\2</a>\\3";
			}
			
			// Deal with the link titles in a separate process afterwards to avoid recursion.
			$this->titlesfind[] = "/\#\#$glossary_id\#\#/";
			// truncate the definition to 80 chars
			$this->titlesreplace[] = htmlentities(trim_characters($term_body, 0, 80));
		}
		// Highlight all occurrences of another glossary term in the definition.
		$body = preg_replace($findwords, $replacewords, $body);

		// Replace any phrases in wikipedia
		// TODO: Merge this code into above, so our gloss and wikipedia
		// don't clash (e.g. URLs getting doubly munged etc.)
		$body = wikipedize($body);  
	
		// Then translate all the title tag codes.
		// (this stops preg replace replacing content in the title tags)
		if ($tokenize == 0) {
			$body = $this->glossarise_titletags($body);
		}

		return ($body);
	}
	
	function glossarise_titletags($body) {
		$body = preg_replace($this->titlesfind, $this->titlesreplace, $body);
		return ($body);
	}
}

?>
