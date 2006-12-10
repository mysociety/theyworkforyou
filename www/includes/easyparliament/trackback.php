<?php

/*
For doing stuff with trackbacks.


To add a new trackback you'll need to do something like:
	$trackbackdata = array (
		'epobject_id' 	=> $epobject_id,
		'url' 			=> $url,
		'blog_name'		=> $blog_name,
		'title'			=> $title,
		'excerpt' 		=> $excerpt,
		'source_ip'		=> $source_ip
	);
	$TRACKBACK = new TRACKBACK();
	$TRACKBACK->add($trackbackdata);


To display stuff you'll need to do something like:

	$args = array (
		'epobject_id' => '4352'
	);
	$TRACKBACK = new TRACKBACK;
	$TRACKBACK->display('epobject_id', $args);


*/

class TRACKBACK {

	
	// Do trackbacks need moderation before appearing on the site?
	var $moderate_trackbacks = false;
	// Note, there's no code for processing trackbacks for moderation at the moment.
	// But switching this to true will mark all incoming trackbacks as invisible.


	function TRACKBACK () {
		
		$this->db = new ParlDB;
		
		// Set in init.php
		if (ALLOWTRACKBACKS == true) {
			$this->trackbacks_enabled = true;
		} else {
			$this->trackbacks_enabled = false;
		}
	}
	
	function trackbacks_enabled() { return $this->trackbacks_enabled; }
	function moderate_trackbacks() { return $this->moderate_trackbacks; }
	

	function display ($view, $args=array(), $format='html') {
		// $view is one of:
		//	'epobject_id' - display the pings for one epobject.
		// 	'recent' - to get the most recent pings to anywhere.
	
		// $args will have one of:
		//	'gid' - the gid of a hansard item (of the form 'debate_2003-02-28.475.3').
		//	'num' - the number of recent pings to show.
		global $PAGE;
		
		if ($view == 'epobject_id' || $view == 'recent') {
		
			// What function do we call for this view?
			$function = '_get_trackbacks_by_'.$view;
			// Get all the data that's to be rendered.
			$trackbackdata = $this->$function($args);
			
		} else {
			$PAGE->error_message ("You haven't specified a valid view type.");
			return false;
		}
		
		$data = array (
			'data' 	=> $trackbackdata,
			'info'	=> array (
				'view' => $view
			)
		);
		if (isset($args['num'])) {
			$data['info']['num'] = $args['num'];
		}
		
		$this->render($view, $data, $format);
	}



	function render ($view, $data, $format='html') {
		
		if ($format != 'html') {
			$format = 'html';
		}
		
		// We currently only have one kind of trackback template, so 
		// we're ignoring $view here I'm afraid...
		
		include (INCLUDESPATH."easyparliament/templates/$format/trackbacks" . ".php");
	
	}


	function add ($trackbackdata) {
		/*
		$data = array (
			'epobject_id' 	=> '34533',
			'url' 			=> 'http://www.gyford.com/weblog/my_entry.html',
			'blog_name' 	=> "Phil's weblog",
			'title' 		=> 'Interesting speech',
			'excerpt' 		=> 'My MP has posted an interesting speech, etc',
			'source_ip' 	=> '123.123.123.123'
		);
		*/
		
		// This code originally based on stuff from http://wordpress.org/
				
		if ($this->trackbacks_enabled() == false) {
			$this->_trackback_response(1, 'Sorry, trackbacks are disabled.');
		}
		
		$epobject_id = $trackbackdata['epobject_id'];	
		
		
		// Check this epobject_id exists.
		$q = $this->db->query("SELECT epobject_id 
						FROM	epobject
						WHERE	epobject_id = '" . addslashes($epobject_id) . "'");
		
		if ($q->rows() == 0) {
			$this->_trackback_response(1, "Sorry, we don't have a valid epobject_id.");
		}
		
		
		// Still here? Then we're trackbacking to a valid hansard item.
		$url 		= $trackbackdata['url'];
		$source_ip	= $trackbackdata['source_ip'];
		// These all strip_tags too.
		$title 		= trim_characters(html_entity_decode($trackbackdata['title']), 0, 255);
		$excerpt 	= trim_characters(html_entity_decode($trackbackdata['excerpt']), 0, 255);
		$blog_name 	= trim_characters(html_entity_decode($trackbackdata['blog_name']), 0, 255);
		
		$visible 		= $this->moderate_trackbacks ? 0 : 1;
		
		$q = $this->db->query("INSERT INTO trackbacks
						(epobject_id, blog_name, title, excerpt, url, source_ip, posted, visible)
						VALUES
						('" . addslashes($epobject_id) . "',
						'" . addslashes($blog_name) . "',
						'" . addslashes($title) . "',
						'" . addslashes($excerpt) . "',
						'" . addslashes($url) . "',
						'" . addslashes($source_ip) . "',
						NOW(), 
						'$visible')
						");
	
		if ($q->success()) {
			// Return a success message.
			$this->_trackback_response(0);
		
		} else {
			die ("Sorry, we could not save the trackback to the database. Please <a href=\"mailto:" . CONTACTEMAIL . "\">email us</a> to let us know. Thanks.");
		}
	}




	function _get_trackbacks_by_epobject_id ($args) {
	
		// Returns an array of the trackback data for this particular
		// gid.
		
		// We need $args['epobject_id'].
		
		global $PAGE;
				
		if (!isset($args['epobject_id']) || $args['epobject_id'] == '') {
			$PAGE->error_message("We need an epobject_id to display trackbacks");
			return false;
		}
		
		$epobject_id = $args['epobject_id'];
	
		// What we return.
		$trackbackdata = array();
		
		$q = $this->db->query("SELECT trackback_id,
								epobject_id,
								blog_name,
								title,
								excerpt,
								url,
								posted
						FROM 	trackbacks
						WHERE 	epobject_id = '" . addslashes($epobject_id) . "'
						AND 	visible = 1
						ORDER BY posted ASC
						");
						
		if ($q->rows() > 0) {
			for ($row=0; $row<$q->rows(); $row++) {
				$trackbackdata[] = array (
					'trackback_id' 	=> $q->field($row, 'trackback_id'),
					'epobject_id'	=> $q->field($row, 'epobject_id'),
					'blog_name' 	=> $q->field($row, 'blog_name'),
					'title'			=> $q->field($row, 'title'),
					'excerpt'		=> $q->field($row, 'excerpt'),
					'url'			=> $q->field($row, 'url'),
					'posted'		=> $q->field($row, 'posted')
				);
			}		
		}
		
		return $trackbackdata;
	}


	function _get_trackbacks_by_recent ($args) {
	
		// Returns an array of the most recent trackback data for all objects.
		
		// We need $args['num'].
		
		global $PAGE;
				
		if (!is_numeric($args['num'])) {
			$PAGE->error_message("We need to know how many trackbacks to display.");
			return false;
		}
		
		$num = $args['num'];
	
		// What we return.
		$trackbackdata = array();
		
		$q = $this->db->query("SELECT trackback_id,
								epobject_id,
								blog_name,
								title,
								excerpt,
								url,
								posted
						FROM 	trackbacks
						WHERE 	visible = 1
						ORDER BY posted DESC
						LIMIt	$num
						");
						
		if ($q->rows() > 0) {
			for ($row=0; $row<$q->rows(); $row++) {
				$trackbackdata[] = array (
					'trackback_id' 	=> $q->field($row, 'trackback_id'),
					'epobject_id'	=> $q->field($row, 'epobject_id'),
					'blog_name' 	=> $q->field($row, 'blog_name'),
					'title'			=> $q->field($row, 'title'),
					'excerpt'		=> $q->field($row, 'excerpt'),
					'url'			=> $q->field($row, 'url'),
					'posted'		=> $q->field($row, 'posted')
				);
			}		
		}
		
		return $trackbackdata;
	}



	function _trackback_response($error = 0, $error_message = '') {
		// What gets sent back to someone pinging a page here.
		// This is only called from add().
		
		// This code originally based on stuff from http://wordpress.org/
		
		global $this_page, $PAGE;
		
		if ($this_page == 'trackback') {
			// This page just does XML.
			
			if ($error) {
				echo '<?xml version="1.0" encoding="iso-8859-1"?'.">\n";
				echo "<response>\n";
				echo "<error>1</error>\n";
				echo "<message>$error_message</message>\n";
				echo "</response>";
			} else {
				echo '<?xml version="1.0" encoding="iso-8859-1"?'.">\n";
				echo "<response>\n";
				echo "<error>0</error>\n";
				echo "</response>";
			}
			die();

		} else {
			// We're adding a trackback from a page that's probably expecting HTML.
			
			if ($error) {
				$PAGE->error_message($error_message);
			} else {
				print "<p>Trackback added successfully.</p>\n";
			}
		}
	}

}

?>
