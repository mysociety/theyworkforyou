<?php


/*	 The class for displaying one or more comments.
	(There's also a function for adding a new comment to the DB because I wasn't
	sure where else to put it!).
	
	This works similarly to the HANSARDLIST class.
	
	To display all the comments for an epobject you'll do:
	
		$args = array ('epobject_id' => $epobject_id);
		$COMMENTLIST = new COMMENTLIST;
		$COMMENTLIST->display ('ep', $args);
		
	This will call the _get_data_by_ep() function which passes variables to the
	_get_comment_data() function. This gets the comments from the DB and returns
	an array of comments.
	
	The render() function is then called, which includes a template and
	goes through the array, displaying the comments. See the HTML comments.php
	template for the format.
	NOTE: You'll need to pass the 'body' of the comment through filter_user_input()
	and linkify() first.
	
	You could also just call the $COMMENTLIST->render() array with an array
	of comment data and display directly (used for previewing user input).
	
*/

include_once INCLUDESPATH . 'dbtypes.php';

class COMMENTLIST {

	function COMMENTLIST () {
		global $this_page;
		
		$this->db = new ParlDB;
		
		// We use this to create permalinks to comments. For the moment we're
		// assuming they're on the same page we're currently looking at:
		// debate, wran, etc.	
		$this->page = $this_page;
	
	}
	

	function display ($view, $args=array(), $format='html') {
		// $view is what we're viewing by:
		//	'ep' is all the comments attached to an epobject.
		//	'user' is all the comments written by a user.
		//	'recent' is the most recent comments.
		
		// $args is an associative array of stuff like
		//	'epobject_id' => '37'
		// Where 'epobject_id' is an epobject_id.
		// Or 'gid' is a hansard item gid.

		// Replace a hansard object gid with an epobject_id.
//		$args = $this->_fix_gid($args);

		// $format is the format the data should be rendered in.
		
		if ($view == 'ep' || $view == 'user' || $view == 'recent' || $view == 'search') {
			// What function do we call for this view?
			$function = '_get_data_by_'.$view;
			// Get all the dta that's to be rendered.
			$data = $this->$function($args);

		} else {
			// Don't have a valid $view;
			$PAGE->error_message ("You haven't specified a view type.");
			return false;
		}
		
		if ($view == 'user') {
			$template = 'comments_user';
		} elseif ($view == 'recent') {
			$template = 'comments_recent';
		} elseif ($view == 'search') {
			$template = 'comments_search';
		} else {
			$template = 'comments';
		}
			
		$this->render($data, $format, $template);
		
		return true;
	}
	
	function render ($data, $format='html', $template='comments') {
		include (INCLUDESPATH."easyparliament/templates/$format/$template.php");
	}
	
	function _get_data_by_ep ($args) {
		// Get all the data attached to an epobject.
		global $PAGE;

		twfy_debug (get_class($this), "getting data by epobject");
		
		// What we return.
		$data = array();
		if (!is_numeric($args['epobject_id'])) {
			$PAGE->error_message ("Sorry, we don't have a valid epobject id");
			return $data;
		}
		
		// For getting the data.
		$input = array (
			'amount' => array (
				'user' => true
			),
			'where' => array (
				'comments.epobject_id=' => $args['epobject_id'],
				#'visible=' => '1'
			),
			'order' => 'posted ASC'
		);
		
		$commentsdata = $this->_get_comment_data($input);
		
		$data['comments'] = $commentsdata;

		if (isset($args['user_id']) && $args['user_id'] != '') {
			// We'll pass this on to the template so it can highlight the user's comments.
			$data['info']['user_id'] = $args['user_id'];
		}
		
		return $data;
	
	}
	
	
	
	function _get_data_by_user ($args) {
		// Get a user's most recent comments.
		// Could perhaps be modified to get different lists of a user's
		// comments by things in $args?
		global $PAGE;
		
		twfy_debug (get_class($this), "getting data by user");
		
		// What we return.
		$data = array();
			
		if (!is_numeric($args['user_id'])) {
			$PAGE->error_message ("Sorry, we don't have a valid user id");
			return $data;
		}	
		
		if (isset($args['num']) && is_numeric($args['num'])) {
			$num = $args['num'];
		} else {
			$num = 10;
		}

		if (isset($args['page']) && is_numeric($args['page'])) {
			$page = $args['page'];
		} else {
			$page = 1;
		}

		$limit = $num*($page-1) . ',' . $num;

		// We're getting the most recent comments posted to epobjects.
		// We're grouping them by epobject so we can just link to each hansard thing once.
		// When there are numerous comments on an epobject we're getting the most recent
		// 		comment_id and posted date.
		// We're getting the body and speaker details for the epobject.
		// We're NOT getting the comment bodies. Why? Because adding them to this query
		// would fetch the text for the oldest comment on an epobject group, rather
		// than the most recent. So we'll get the comment bodies later...
		$q = $this->db->query("SELECT MAX(comments.comment_id) AS comment_id, 
								MAX(comments.posted) AS posted, 
								COUNT(*) AS total_comments,
								comments.epobject_id,
								hansard.major, 
								hansard.gid, 
								users.firstname, 
								users.lastname, 
								epobject.body,
								member.first_name, 
								member.last_name
						FROM 	comments, hansard, users, epobject
						LEFT OUTER JOIN member ON hansard.speaker_id = member.member_id 
						WHERE 	comments.epobject_id = epobject.epobject_id 
						AND 	comments.epobject_id = hansard.epobject_id 
						AND 	comments.user_id = users.user_id 
						AND 	users.user_id='" . addslashes($args['user_id']) . "' 
						AND 	visible='1' 
						GROUP BY epobject_id
						ORDER BY posted DESC
						LIMIT " . $limit
						);
		
		$comments = array();
		$comment_ids = array();
		
		if ($q->rows() > 0) {
		
			for ($n=0; $n<$q->rows(); $n++) {
			
				// All the basic stuff...
				$comments[$n] = array (
					'comment_id'		=> $q->field($n, 'comment_id'),
					'posted'			=> $q->field($n, 'posted'),
					'total_comments'	=> $q->field($n, 'total_comments'),
					'epobject_id'		=> $q->field($n, 'epobject_id'),
					'firstname'			=> $q->field($n, 'firstname'),
					'lastname'			=> $q->field($n, 'lastname'),
					// Hansard item body, not comment body.
					'hbody'				=> $q->field($n, 'body'), 
					'speaker'			=> array (
						'first_name'	=> $q->field($n, 'first_name'),
						'last_name'		=> $q->field($n, 'last_name')
					)
				);
				
				// Add the URL...
				$urldata = array (
					'major' 		=> $q->field($n, 'major'),
					'gid'			=> $q->field($n, 'gid'),
					'comment_id'	=> $q->field($n, 'comment_id'),
					'user_id'		=> $args['user_id']
				);
					
				$comments[$n]['url'] = $this->_comment_url($urldata);
				
				// We'll need these for getting the comment bodies.
				$comment_ids[] = $q->field($n, 'comment_id');
			
			}

			$in = implode(', ', $comment_ids);
			
			$r = $this->db->query("SELECT comment_id,
									body
							FROM	comments
							WHERE	comment_id IN ($in)
							");
			
			if ($r->rows() > 0) {
			
				$commentbodies = array();
				
				for ($n=0; $n<$r->rows(); $n++) {
					$commentbodies[ $r->field($n, 'comment_id') ] = $r->field($n, 'body');
				}
				
				// This does rely on both this and the previous query returning 
				// stuff in the same order...
				foreach ($comments as $n => $commentdata) {
					$comments[$n]['body'] = $commentbodies[ $comments[$n]['comment_id'] ];		
				}
			}
		}
		
		$data['comments'] = $comments;
		$data['results_per_page'] = $num;
		$data['page'] = $page;
		$q = $this->db->query('SELECT COUNT(DISTINCT(epobject_id)) AS count FROM comments WHERE visible=1 AND user_id=' . $args['user_id']);
		$data['total_results'] = $q->field(0, 'count');
		return $data;	
	
	}
	
	
	
	function _get_data_by_recent ($args) {
		// $args should contain 'num', indicating how many to get.
		// and perhaps pid too, for a particular person
		
		twfy_debug (get_class($this), "getting data by recent");

		// What we return.
		$data = array();
	
		if (isset($args['num']) && is_numeric($args['num'])) {
			$num = $args['num'];
		} else {
			$num = 25;
		}

		if (isset($args['page']) && is_numeric($args['page'])) {
			$page = $args['page'];
		} else {
			$page = 1;
		}

		$limit = $num*($page-1) . ',' . $num;

		$where = array(
			'visible=' => '1'
		);
		if (isset($args['pid']) && is_numeric($args['pid'])) {
			$where['person_id='] = $args['pid'];
		}
		$input = array (
			'amount' => array (
				'user' => true
			),
			'where'  => $where,
			'order' => 'posted DESC',
			'limit' => $limit
		);
		
		$commentsdata = $this->_get_comment_data($input);
		
		$data['comments'] = $commentsdata;
		$data['results_per_page'] = $num;
		$data['page'] = $page;
		if (isset($args['pid']) && is_numeric($args['pid'])) {
			$data['pid'] = $args['pid'];
			$q = 'SELECT title, first_name, last_name, constituency, house FROM member WHERE left_house="9999-12-31" and person_id = ' . $args['pid'];
			$q = $this->db->query($q);
			$data['full_name'] = member_full_name($q->field(0, 'house'), $q->field(0, 'title'), $q->field(0, 'first_name'), $q->field(0, 'last_name'), $q->field(0,'constituency'));
			$q = 'SELECT COUNT(*) AS count FROM comments,hansard,member WHERE visible=1 AND comments.epobject_id = hansard.epobject_id and hansard.speaker_id = member.member_id and person_id = ' . $args['pid'];
		} else {
			$q = 'SELECT COUNT(*) AS count FROM comments WHERE visible=1';
		}
		$q = $this->db->query($q);
		$data['total_results'] = $q->field(0, 'count');
		return $data;
	}


	function _get_data_by_search ($args) {
		// $args should contain 'num', indicating how many to get.
		
		twfy_debug (get_class($this), "getting data by search");

		// What we return.
		$data = array();
	
		if (isset($args['num']) && is_numeric($args['num'])) {
			$num = $args['num'];
		} else {
			$num = 10;
		}

		if (isset($args['page']) && is_numeric($args['page'])) {
			$page = $args['page'];
		} else {
			$page = 1;
		}

		$limit = $num*($page-1) . ',' . $num;

		$input = array (
			'amount' => array (
				'user'=> true
			),
			'where'  => array (
				'comments.body LIKE' => "%$args[s]%"
			),
			'order' => 'posted DESC',
			'limit' => $limit
		);
		
		$commentsdata = $this->_get_comment_data($input);
		
		$data['comments'] = $commentsdata;
		$data['search'] = $args['s'];
		#		$data['results_per_page'] = $num;
		#		$data['page'] = $page;
		#		$q = $this->db->query('SELECT COUNT(*) AS count FROM comments WHERE visible=1');
		#		$data['total_results'] = $q->field(0, 'count');
		return $data;
	}


	function _comment_url($urldata) {
		global $hansardmajors;

		// Pass it the major and gid of the comment's epobject and the comment_id.
		// And optionally the user's id, for highlighting the comments on the destination page.
		// It returns the URL for the comment.
	
		$major 		= $urldata['major'];
		$gid 		= $urldata['gid'];
		$comment_id = $urldata['comment_id'];
		$user_id = isset($urldata['user_id']) ? $urldata['user_id'] : false;
		
		// If you change stuff here, you might have to change it in 
		// $COMMENT->_set_url() too...
		
		// We'll generate permalinks for each comment.
		// Assuming every comment is from the same major...
		$page = $hansardmajors[$major]['page'];
		$gidvar = $hansardmajors[$major]['gidvar'];
		
		$URL = new URL($page);
		
		$gid = fix_gid_from_db($gid); // In includes/utility.php
		$URL->insert(array($gidvar => $gid ));
		if ($user_id) {
			$URL->insert(array('u' => $user_id));
		}
		$url = $URL->generate() . '#c' . $comment_id;
		
		return $url;
	}
	
	

/*	function _fix_gid ($args) {

		// Replace a hansard object gid with an epobject_id.
		// $args may have a 'gid' element. If so, we replace it
		// with the hansard object's epobject_id as 'epobject_id', because
		// comments are tied to epobject_ids.
		// Returns the corrected $args array.
		
		global $this_page;
		
		if (isset($args['gid']) && !isset($args['epobject_id'])) {

			if ($this_page == 'wran' || $this_page == 'wrans') {
				$gidextra = 'wrans';
			} else {
				$gidextra = 'debate';
			}

			$q = $this->db->query ("SELECT epobject_id FROM hansard WHERE gid = 'uk.org.publicwhip/" . $gidextra . '/' . addslashes($args['gid']) . "'");

			if ($q->rows() > 0) {
				unset($args['gid']);
				$args['epobject_id'] = $q->field(0, 'epobject_id');
			}
		}
		
		return $args;

	}
	*/
	
	function _get_comment_data ($input) {
		// Generic function for getting hansard data from the DB.
		// It returns an empty array if no data was found.
		// It returns an array of items if 1 or more were found.
		// Each item is an array of key/value pairs. 
		// eg:
		/*	
			array (
				0	=> array (
					'comment_id'	=> '2',
					'user_id'		=> '10',
					'body'			=> 'The text of the comment is here.',
					etc...
				),
				1	=> array (
					'comment_id'	=> '3',
					etc...
				)
			);
		*/
		
		// $input is an array of things needed for the SQL query:
		// 'amount' has one or more of :
		//		'user'=>true - Users' names.
		//  	'hansard'=>true - Body text from the hansard items.
		// 'where' is an associative array of stuff for the WHERE clause, eg:
		// 		array ('id=' => '37', 'posted>' => '2003-12-31 00:00:00');
		// 'order' is a string for the $order clause, eg 'hpos DESC'.
		// 'limit' as a string for the $limit clause, eg '21,20'.
				
		$amount = isset($input['amount']) ? $input['amount'] : array();
		$wherearr = $input['where'];
		$order = isset($input['order']) ? $input['order'] : '';
		$limit = isset($input['limit']) ? $input['limit'] : '';
	
		// The fields to fetch from db. 'table' => array ('field1', 'field2').
		$fieldsarr = array (
			'comments' => array ('comment_id', 'user_id', 'epobject_id', 'body', 'posted', 'modflagged', 'visible'),
			'hansard' => array ('major', 'gid')
		);

		// Yes, we need the gid of a comment's associated hansard object
		// to make the comment's URL. And we have to go via the epobject
		// table to do that.
		$join = 'INNER JOIN epobject ON comments.epobject_id = epobject.epobject_id
					INNER JOIN hansard ON comments.epobject_id = hansard.epobject_id';
		
		// Add on the stuff for getting a user's details.
		if (isset($amount['user']) && $amount['user'] == true) {
			$fieldsarr['users'] = array ('firstname', 'lastname', 'user_id');
			// Like doing "FROM comments, users" but it's easier to add
			// an "INNER JOIN..." automatically to the query.
			$join .= ' INNER JOIN users ON comments.user_id = users.user_id ';
		}
		
		// Add on that we need to get the hansard item's body.
		if (isset($amount['hansard']) && $amount['hansard'] == true) {
			$fieldsarr['epobject'] = array('body');
			$fieldsarr['member'] = array ('first_name', 'last_name');
			$join .= ' LEFT OUTER JOIN member ON hansard.speaker_id = member.member_id';
		}

		if (isset($wherearr['person_id='])) {
			$join .= ' INNER JOIN member ON hansard.speaker_id = member.member_id';
		}

		$fieldsarr2 = array ();
		// Construct the $fields clause.
		foreach ($fieldsarr as $table => $tablesfields) {
			foreach ($tablesfields as $n => $field) {
				// HACK.
				// If we're getting the body of a hansard object, we need to 
				// get it AS 'hbody', so we don't confuse with the comment's 'body'
				// element.
				if ($table == 'epobject' && $field == 'body') {
					$field .= ' AS hbody';
				}
				$fieldsarr2[] = $table.'.'.$field;
			}
		}
		$fields = implode(', ', $fieldsarr2);	

		
		$wherearr2 = array ();
		// Construct the $where clause.
		foreach ($wherearr as $key => $val) {
			$wherearr2[] = "$key'" . addslashes($val) . "'";
		}
		$where = implode (" AND ", $wherearr2);


		if ($order != '') {
			$order = "ORDER BY $order";
		}
		if ($limit != '') {
			$limit = "LIMIT $limit";
		}
		
		// Finally, do the query!
		$q = $this->db->query ("SELECT $fields
						FROM 	comments
						$join
						WHERE $where
						$order
						$limit
						");


		// Format the data into an array for returning.
		$data = array ();
				
		if ($q->rows() > 0) {
		
			// If you change stuff here, you might have to change it in 
			// $COMMENT->_set_url() too...
			
			// We'll generate permalinks for each comment.
			// Assuming every comment is from the same major...
			
			for ($n=0; $n<$q->rows(); $n++) {

				// Put each row returned into its own array in $data.
				foreach ($fieldsarr as $table => $tablesfields) {
					foreach ($tablesfields as $m => $field) {
						
						// HACK 2.
						// If we're getting the body of a hansard object, we have 
						// got it AS 'hbody', so we didn't duplicate the comment's 'body'
						// element.
						if ($table == 'epobject' && $field == 'body') {
							$field = 'hbody';
						}
						
						$data[$n][$field] = $q->field($n, $field);
					}
				}

				$urldata = array(
					'major' => $q->field($n, 'major'),
					'gid' => $data[$n]['gid'],
					'comment_id' => $data[$n]['comment_id'],
#					'user_id' => 
				);
				$data[$n]['url'] = $this->_comment_url($urldata);
			}
		}
		
		return $data;
						
	}
	

}
