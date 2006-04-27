<?php

include_once INCLUDESPATH."easyparliament/searchengine.php";
include_once INCLUDESPATH."easyparliament/searchlog.php";

/*

The HANSARDLIST class and its children, DEBATELIST and WRANSLIST, display data about 
Hansard objects. You call display things by doing something like:

		$LIST = new DEBATELIST;
		$LIST->display('gid', array('gid'=>'2003-10-30.422.4') );
	
	The second line could be replaced with something like one of these:

		$LIST->display('date', array('date'=>'2003-12-31') );
		$LIST->display('recent');
		$LIST->display('member', array('id'=>37) );
	
	
Basic structure...

	The display() function calls a get function which returns all the data that 
	is to be displayed. The function name depends on the $view (ie, 'gid', 'recent', 
	etc). 
	Once we have an array of data, the render() function is called, which includes
	a template. This cycles through the data array and outputs HTML.
	
	Most of the data is fetched from the database by the _get_hansard_data() function.
	
	The COMMENTSLIST class is simpler and works in a similar fashion - that might help
	you get your head round how this all works...
	
Future stuff...

	You could have multiple templates for different formats. Eg, to output stuff in 
	XML, duplicate the HTML template and change what you need to create XML instead.
	Then call the display() function something like this:
		$LIST->display('gid', array('gid'=>'2003-10-30.422.4'), 'xml' );
	(You'll need to allow the 'xml' format in render() too).
	
	No support for pages of results yet. This would be passed in in the $args array
	and used in the LIMIT of the _get_hansard_data() function.
	The template could then display links to next/prev pages in the sequence.




*/

class HANSARDLIST {

	// We add 'wrans' or 'debate' onto the end of this in the appropriate classes'
	// constructors.
	// If you change this, change it in COMMENTSLIST->_fix_gid() too!
	// And in TRACKBACK too.
	var $gidprefix = 'uk.org.publicwhip/';
	
	
	// This will be used to cache information about speakers on this page
	// so we don't have to keep fetching the same data from the DB.
	var $speakers = array ();
	/*
	$this->speakers[ $speaker_id ] = array (
		"first_name"	=> $first_name,
		"last_name"		=> $last_name,
		"constituency"	=> $constituency,
		"party"			=> $party,
		"person_id"	    => $person_id,
		"url"			=> "/member/?id=$speaker_id"
	);
	*/
	
	// This will be used to cache mappings from epobject_id to gid,
	// so we don't have to continually fetch the same data in get_hansard_data().
	var $epobjectid_to_gid = array ();
	/*
	$this->epobjectid_to_gid[ $epobject_id ] => $gid;
	*/
	
	
	// This is so we can tell what type of thing we're displaying from outside 
	// the object. eg, so we know if we should be able to post comments to the
	// item. It will have a value set if we are displaying by 'gid' (not 'date').
	// Use htype() to access it.
	var $htype;
	
	
	// Reset to the relevant major ID in DEBATELIST or WRANSLIST
	var $major;
	
	
	// When we view a particular item, we set these to the epobject_id and gid
	// of the item so we can attach Trackbacks etc to it from outside.
	var $epobject_id;
	var $gid;
	
	
	// This will be set if $this->most_recent_day() is called. Just so we
	// don't need to call it and it's lengthy query again.
	var $most_recent_day;


	function HANSARDLIST () {
		$this->db = new ParlDB;
	}
	
	
	
	function display ($view, $args=array(), $format='html') {
		// $view is what we're viewing by:
		// 	'gid' is the gid of a hansard object,
		//	'date' is all items on a date,
		//	'person' is a person's recent debates/wrans,
		//	'recent' is a number of recent dates with items in.
		//  'recent_mostvotes' is the speeches with the most votes in the last x days.
		//	'search' is all debates/wrans that match a search term.
		//	'biggest_debates' is biggest recent debates (obviously only for DEBATESLIST).
		//  'recent_wrans' is some recent written answers (obv only for WRANSLIST).
		
		// $args is an associative array of stuff like 
		//	'gid' => '2003-10-30.422.4'  or
		//	'd' => '2003-12-31' or
		//	's' => 'my search term'
		//	'o' => Sort order: 'r' for relevance, 'd' for date
		
		// $format is the format the data should be rendered in,
		// using that set of templates (or 'none' for just returning
		// the data). 
		
		global $PAGE;

		$validviews = array ('calendar', 'date', 'gid', 'person', 'search', 'recent', 'recent_mostvotes', 'biggest_debates', 'recent_wrans', 'recent_wms', 'column', 'mp');
		if (in_array($view, $validviews)) {

			// What function do we call for this view?
			$function = '_get_data_by_'.$view;
			// Get all the data that's to be rendered.
			$data = $this->$function($args);
			if (isset($data['info']['redirected_gid'])) {
				return $data['info']['redirected_gid'];
			}

		} else {
			// Don't have a valid $view.
			$PAGE->error_message ("You haven't specified a view type.");
			return false;
		}

		// Set the values of this page's headings depending on the data we've fetched.
		if (isset($data['info'])) {
			$PAGE->set_hansard_headings($data['info']);
		}
		
		// Glossary $view_override (to avoid too much code duplication...)
		if (isset($args['view_override'])) {
			$view = $args['view_override'];
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
		
		include (FILEPATH."/../includes/easyparliament/templates/$format/hansard_$view" . ".php");
		return true;
	
	}
	
	
	function total_items () {
		// Returns number of items in debates or wrans, depending on which class this is, 
		// DEBATELIST or WRANSLIST.
		
		$q = $this->db->query("SELECT COUNT(*) AS count FROM hansard WHERE major='" . $this->major . "'");
		
		return $q->field(0, 'count');
	}
	
	
	
	function most_recent_day () {
		// Very simple. Returns an array of stuff about the most recent data
		// for this major:

		// array (
		//		'hdate'		=> 'YYYY-MM-DD',
		//		'timestamp' => 124453679,
		//		'listurl'	=> '/foo/?id=bar'
		// )
				
		// When we do this function the first time we cache the 
		// results in this variable. As it's an expensive query.
		if (isset($this->most_recent_day)) {
			return $this->most_recent_day;
		}
		
		// What we return.
		$data = array();
		
		$q = $this->db->query("SELECT MAX(hdate) AS hdate
						FROM 	hansard
						WHERE	major = '" . $this->major . "'
						");		
		if ($q->rows() > 0) {
			
			$hdate = $q->field(0, 'hdate');
			if ($hdate) {
				$URL = new URL($this->listpage);
				$URL->insert( array('d'=>$hdate) );
			
				// Work out a timestamp which is handy for comparing to now.
				list($year, $month, $date) = explode('-', $hdate);
				$timestamp = gmmktime (0, 0, 0, $month, $date, $year);			
				
				$data = array (
					'hdate'		=> $hdate,
					'timestamp'	=> $timestamp,
					'listurl'	=> $URL->generate()
				);
				
				// This is just because it's an expensive query
				// and we really want to avoid doing it more than once.
				// So we're caching it.
				$this->most_recent_day = $data;
			}
		}
		
		return $data;
	}
	
	
	function htype () {
		return $this->htype;
	}

	function epobject_id () {
		return $this->epobject_id;
	}

	function gid () {
		return $this->gid;
	}
	
	
	function _get_section ($itemdata) {
		// Pass it an array of data about an item and it will return an
		// array of data about the item's section heading.

		debug (get_class($this), "getting an item's section");
		
		// What we return.
		$sectiondata = array ();

		if ($itemdata['htype'] != '10') {

			// This item is a subsection, speech or procedural, 
			// or a wrans questions/answer,
			// so get the section info above this item.
									
			// For getting hansard data.
			$input = array (
				'amount' => array (
					'body' => true
				),
				'where' => array (
					'hansard.epobject_id=' => $itemdata['section_id']
				)
			);
			
			$sectiondata = $this->_get_hansard_data($input);
			
			if (count($sectiondata) > 0) {
				$sectiondata = $sectiondata[0];
			}
		
		} else {
			// This item *is* a section, so just return that.
			
			$sectiondata = $itemdata;
		
		}
		
		return $sectiondata;
	}
	
	

	function _get_subsection ($itemdata) {
		// Pass it an array of data about an item and it will return an
		// array of data about the item's subsection heading.

		debug (get_class($this), "getting an item's subsection");
		
		// What we return.
		$subsectiondata = array ();
		
		if ($itemdata['htype'] == '12' || $itemdata['htype'] == '13' ) {
			// This item is a speech or procedural, so get the 
			// subsection info above this item.
			
			// For getting hansard data.
			$input = array (
				'amount' => array (
					'body' => true
				),
				'where' => array (
					'hansard.epobject_id=' => $itemdata['subsection_id']
				)
			);
			
			$subsectiondata = $this->_get_hansard_data($input);
			if (count($subsectiondata) == 0)
				$subsectiondata = null;
			else
				$subsectiondata = $subsectiondata[0];

		} elseif ($itemdata['htype'] == '11') {
			// It's a subsection, so use the item itself.
			$subsectiondata = $itemdata;
		}

		return $subsectiondata;
	}
	


	function _get_nextprev_items ($itemdata) {
		global $hansardmajors;

		// Pass it an array of item info, of a section/subsection, and this will return
		// data for the next/prev items.
				
		debug (get_class($this), "getting next/prev items");
		
		// What we return.
		$nextprevdata = array ();

		
		$prev_item_id = false;
		$next_item_id = false;

		if ($itemdata['htype'] == '10' || 
			$itemdata['htype'] == '11') {
			// Debate subsection or section - get the next one.
			if ($hansardmajors[$itemdata['major']]['type'] == 'other') {
				$where = 'htype = 11';
			} else {
				$where = "(htype = 10 OR htype = 11)";
			}
		} else {
			// Anything else in debates - get the next element that isn't
			// a subsection or section, and is within THIS subsection.
			$where = "subsection_id = '" . $itemdata['subsection_id'] . "' AND (htype != 10 AND htype != 11)";
		}
					
		if (isset($where)) {
			// Find if there are next/previous debate items of our
			// chosen type today.
			
			// For sections/subsections, 
			// this will find headings with no content, but I failed to find
			// a vaguely simple way to do this. So this is it for now...

			// Find the epobject_id of the previous item (if any):
			$q = $this->db->query("SELECT epobject_id 
							FROM 	hansard 
							WHERE 	hdate = '" . $itemdata['hdate'] . "' 
							AND 	hpos < '" . $itemdata['hpos'] . "' 
							AND 	major = '" . $itemdata['major'] . "'
							AND 	$where
							ORDER BY hpos DESC
							LIMIT 1");
			
			if ($q->rows() > 0) {
				$prev_item_id = $q->field(0, 'epobject_id');
			}

			// Find the epobject_id of the next item (if any):
			$q = $this->db->query("SELECT epobject_id 
							FROM 	hansard 
							WHERE 	hdate = '" . $itemdata['hdate'] . "' 
							AND 	hpos > '" . $itemdata['hpos'] . "'  
							AND 	major = '" . $itemdata['major'] . "'
							AND 	$where
							ORDER BY hpos ASC
							LIMIT 1");
			
			if ($q->rows() > 0) {
				$next_item_id = $q->field(0, 'epobject_id');
			}

		}

		// Now we're going to get the data for the next and prev items
		// that we will use to make the links on the page.
		
		// Previous item.
		if ($prev_item_id) {
			// We have a previous one to link to.
			$wherearr['hansard.epobject_id='] = $prev_item_id;
			
			// For getting hansard data.
			$input = array (
				'amount' => array (
					'body' => true,
					'speaker' => true
				),
				'where' => $wherearr,
				'order' => 'hpos DESC',
				'limit' => 1
			);
			
			$prevdata = $this->_get_hansard_data($input);

			if (count($prevdata) > 0) {
				if ($itemdata['htype'] == '10' || $itemdata['htype'] == '11') {
					// Linking to the prev (sub)section.
					$thing = $hansardmajors[$this->major]['singular'];
					$nextprevdata['prev'] = array (
						'body'		=> "Previous $thing",
						'url'		=> $prevdata[0]['listurl'],
						'title'		=> $prevdata[0]['body']
					);
				} else {
					// Linking to the prev speaker.
					
					if (isset($prevdata[0]['speaker']) && count($prevdata[0]['speaker']) > 0) {
						$title = $prevdata[0]['speaker']['first_name'] . ' ' . $prevdata[0]['speaker']['last_name'];
					} else {
						$title = '';
					}
					$nextprevdata['prev'] = array (
						'body'		=> 'Previous speaker',
						'url'		=> $prevdata[0]['commentsurl'],
						'title'		=> $title
					);
				}
			}
		}

		// Next item.
		if ($next_item_id) {
			// We have a next one to link to.
			$wherearr['hansard.epobject_id='] = $next_item_id;
			
			// For getting hansard data.
			$input = array (
				'amount' => array (
					'body' => true,
					'speaker' => true
				),
				'where' => $wherearr,
				'order' => 'hpos ASC',
				'limit' => 1
			);
			$nextdata = $this->_get_hansard_data($input);

			if (count($nextdata) > 0) {
				if ($itemdata['htype'] == '10' || $itemdata['htype'] == '11') {
					// Linking to the next (sub)section.
					$thing = $hansardmajors[$this->major]['singular'];
					$nextprevdata['next'] = array (
						'body'		=> "Next $thing",
						'url'		=> $nextdata[0]['listurl'],
						'title'		=> $nextdata[0]['body']
					);
				} else {
					// Linking to the next speaker.
					
					if (isset($nextdata[0]['speaker']) && count($nextdata[0]['speaker']) > 0) {
						$title = $nextdata[0]['speaker']['first_name'] . ' ' . $nextdata[0]['speaker']['last_name'];
					} else {
						$title = '';
					}
					$nextprevdata['next'] = array (
						'body'		=> 'Next speaker',
						'url'		=> $nextdata[0]['commentsurl'],
						'title'		=> $title
					);
				}
			}
		}

		
		$URL = new URL($this->listpage);
		
		if ($itemdata['htype'] == '10' || $itemdata['htype'] == '11') {

			// Create URL for this (sub)section's date.
			
			$URL->insert(array ('d'=>$itemdata['hdate']));
			$URL->remove(array('id'));

			$things = $hansardmajors[$itemdata['major']]['title'];	
	
			$nextprevdata['up'] = array (
				'body'		=> "All $things on " . format_date($itemdata['hdate'], SHORTDATEFORMAT),
				'title'		=> '',
				'url' 		=> $URL->generate()
			);
		} else {

			// We'll be setting $nextprevdata['up'] within $this->get_data_by_gid()
			// because we need to know the name and url of the parent item, which 
			// we don't have here. Life sucks.
		
		}


		return $nextprevdata;

	}
	
	
	function _get_nextprev_dates ($date) {
		global $hansardmajors;
		// Pass it a yyyy-mm-dd date and it'll return an array
		// containing the next/prev dates that contain items from 
		// $this->major of hansard object.
		
		debug (get_class($this), "getting next/prev dates");
		
		// What we return.
		$nextprevdata = array ();

		$URL = new URL($this->listpage);

		$looper = array ("next", "prev");
		
		foreach ($looper as $n => $nextorprev) {
		
			$URL->reset();
			
			if ($nextorprev == 'next') {
				$q = $this->db->query("SELECT MIN(hdate) AS hdate
							FROM 	hansard
							WHERE 	major = '" . $this->major . "'
							AND		hdate > '" . mysql_escape_string($date) . "'
							");
			} else {
				$q = $this->db->query("SELECT MAX(hdate) AS hdate
							FROM 	hansard
							WHERE 	major = '" . $this->major . "'
							AND		hdate < '" . mysql_escape_string($date) . "'
							");
			}

			// The '!= NULL' bit is needed otherwise I was getting errors
			// when displaying the first day of debates.
			if ($q->rows() > 0 && $q->field(0, 'hdate') != NULL) {

				$URL->insert( array( 'd'=>$q->field(0, 'hdate') ) );
				
				if ($nextorprev == 'next') {
					$body = 'Next day';
				} else {
					$body = 'Previous day';
				}
				
				$title = format_date($q->field(0, 'hdate'), SHORTDATEFORMAT);
				
				$nextprevdata[$nextorprev] = array (
					'hdate'		=> $q->field(0, 'hdate'),
					'url'	 	=> $URL->generate(),
					'body'		=> $body,
					'title'		=> $title
				);
			}
		}	

		$year = substr($date, 0, 4);
		$URL = new URL($hansardmajors[$this->major]['page_year']);
		$thing = $hansardmajors[$this->major]['plural'];
		$URL->insert(array('y'=>$year));
			
		$nextprevdata['up'] = array (
			'body' 	=> "All of $year's $thing",
			'title'	=> '',
			'url' 	=> $URL->generate()
		);

		return $nextprevdata;

	}



	function _validate_date ($args) {
		// Used when we're viewing things by (_get_data_by_date() functions).
		// If $args['date'] is a valid yyyy-mm-dd date, it is returned.
		// Else false is returned.
		global $PAGE;
		
		if (isset($args['date'])) {
			$date = $args['date'];
		} else {
			$PAGE->error_message ("Sorry, we don't have a date.");
			return false;
		}
		
		if (!preg_match("/^(\d\d\d\d)-(\d{1,2})-(\d{1,2})$/", $date, $matches)) {
			$PAGE->error_message ("Sorry, '".htmlentities($date)."' isn't of the right format (YYYY-MM-DD).");
			return false;
		}
				
		list($string, $year, $month, $day) = $matches;
		
		if (!checkdate($month, $day, $year)) {
			$PAGE->error_message ("Sorry, '".htmlentities($date)."' isn't a valid date.");
			return false;
		}

		$day = substr("0$day", -2);
		$month = substr("0$month", -2);
		$date = "$year-$month-$day";

		// Valid date!
		return $date;
	}
	
	
	
	function _get_item ($args) {
		global $PAGE;
	
		if (!isset($args['gid']) && $args['gid'] == '') {
			$PAGE->error_message ("Sorry, we don't have an item gid.");
			return false;
		}
		
			
		// Get all the data just for this epobject_id.
		$input = array (
			'amount' => array (
				'body' => true,
				'speaker' => true,
				'comment' => true,
				'votes' => true
			),
			'where' => array (
				// Need to add the 'uk.org.publicwhip/debate/' or whatever on before
				// looking in the DB.
				'gid=' => $this->gidprefix . $args['gid']
			)
		);

		debug (get_class($this), "looking for redirected gid");
		$gid = $this->gidprefix . $args['gid'];
		$q = $this->db->query ("SELECT gid_to FROM gidredirect WHERE gid_from = '" . mysql_escape_string($gid) . "'");
		if ($q->rows() == 0) {
			$itemdata = $this->_get_hansard_data($input);
		} else {
			do {
				$gid = $q->field(0, 'gid_to');
				$q = $this->db->query("SELECT gid_to FROM gidredirect WHERE gid_from = '" . mysql_escape_string($gid) . "'");
			} while ($q->rows() > 0);
			$redirected_gid = $gid;
			debug (get_class($this), "found redirected gid $redirected_gid" );
			$input['where'] = array('gid=' => $redirected_gid);
			$itemdata = $this->_get_hansard_data($input);
			// Store that it is one, in case caller wants to do proper redirect
			if (count($itemdata) > 0 ) {
			    $itemdata[0]['redirected_gid'] = fix_gid_from_db($redirected_gid);
			}
		}
		
		if (count($itemdata) > 0) {
			$itemdata = $itemdata[0];
		}
			
		if (count($itemdata) == 0) {
			if (strstr($args['gid'], 'a')) {
				$check_gid = str_replace('a','',$args['gid']);
				$input['where'] = array('gid=' => $this->gidprefix . $check_gid);
				$itemdata = $this->_get_hansard_data($input);
				if (count($itemdata) > 0) {
					$itemdata[0]['redirected_gid'] = $check_gid;
					$itemdata = $itemdata[0];
					return $itemdata;
				}
			}
			$q = $this->db->query('SELECT source_url FROM hansard WHERE gid LIKE "uk.org.publicwhip/lords/'.mysql_escape_string($args['gid']).'%"');
			$u = '';
			if ($q->rows()) {
				$u = $q->field(0, 'source_url');
				$u = '<br><a href="'. $u . '">' . $u . '</a>';
			}
			$PAGE->error_message ("Sorry, there is no Hansard object with a gid of '".htmlentities($args['gid'])."'. If you've just followed a link in an alert email or from a search page, this is probably because the text was actually said in a Lords debate, which we're currently starting to alpha-test. Unfortunately, the email alerts script and search can get a bit confused, and sent out results that the website can't yet show. To hopefully make it up, below might possibly be a link to the actual Lords debate, which will contain the text you wished to be alerted on: $u");
			return false;
		}
		
		return $itemdata;

	}



	function _get_data_by_date ($args) {
		// For displaying the section and subsection headings as
		// links for an entire day of debates/wrans.
	
		global $DATA, $this_page;
		
		debug (get_class($this), "getting data by date");
		
		// Where we'll put all the data we want to render.
		$data = array ();
		
		
		$date = $this->_validate_date($args);
	
		if ($date) {		
		
			$nextprev = $this->_get_nextprev_dates($date);
			
			// We can then access this from $PAGE and the templates.
			$DATA->set_page_metadata($this_page, 'nextprev', $nextprev);
			
			
			// Get all the sections for this date.
			// Then for each of those we'll get the subsections and rows.
			$input = array (
				'amount' => array (
					'body' => true,
					'comment' => true,
					'excerpt' => true
				),
				'where' => array (
					'hdate=' => "$date",
					'htype=' => '10',
					'major=' => $this->major
				),
				'order' => 'hpos'
			);
			
			$sections = $this->_get_hansard_data($input);

			if (count($sections) > 0) {
				
				// Where we'll keep the full list of sections and subsections.
				$data['rows'] = array();
				
				for ($n=0; $n<count($sections); $n++) {
					// For each section on this date, get the subsections within it.

					// Get all the section data.
					$sectionrow = $this->_get_section($sections[$n]);
					
					// Get the subsections within the section.						
					$input = array (
						'amount' => array (
							'body' => true,
							'comment' => true,
							'excerpt' => true
						),
						'where' => array (
							'section_id='	=> $sections[$n]['epobject_id'],
							'htype='		=> '11',
							'major='		=> $this->major
						),
						'order' => 'hpos'
					);
					
					$rows = $this->_get_hansard_data($input);

					// Put the section at the top of the rows array.
					array_unshift ($rows, $sectionrow);
					
					// Add the section heading and the subsections to the full list.
					$data['rows'] = array_merge ($data['rows'], $rows);
				}
			}

			// For page headings etc.
			$data['info']['date'] = $date;
			$data['info']['major'] = $this->major;
		}
				
		return $data;		
	}
	

	function _get_data_by_recent ($args) {
		// Like _get_data_by_id() and _get_data_by_date()
		// this returns a $data array suitable for sending to a template.
		// It lists recent dates with debates/wrans on them, with links.
				
		if (isset($args['days']) && is_numeric($args['days'])) {
			$limit = 'LIMIT ' . $args['days'];
		} else {
			$limit = '';
		}
		
		if ($this->major != '') {
			// We must be in DEBATELIST or WRANSLIST.
			
			$major = "WHERE major = '" . $this->major . "'";
		}
		
		$data = array ();
		
		$q = $this->db->query ("SELECT DISTINCT(hdate)
						FROM 	hansard
						$major	
						ORDER BY hdate DESC
						$limit
						");

		if ($q->rows() > 0) {

			$URL = new URL($this->listpage);
			
			for ($n=0; $n<$q->rows(); $n++) {
				$rowdata = array();
				
				$rowdata['body'] = format_date($q->field($n, 'hdate'), SHORTDATEFORMAT);
				$URL->insert(array('d'=>$q->field($n, 'hdate')));
				$rowdata['listurl'] = $URL->generate();
				
				$data['rows'][] = $rowdata;
			}
		}
		
		$data['info']['text'] = 'Recent dates';
		
		
		return $data;
	}
	



	function _get_data_by_person ($args) {
		// Display a person's most recent debates.
		global $PAGE, $hansardmajors;
		$items_to_list = isset($args['max']) ? $args['max'] : 10;
	
		// Where we'll put all the data we want to render.
		$data = array ();
		
		if (!isset($args['person_id']) || !is_numeric($args['person_id'])) {
			$PAGE->error_message ("Sorry, we need a valid person ID.");
			return $data;
		}

		$where = 'person_id = ' . $args['person_id'];

		if (isset($this->major)) {
			$majorwhere = "AND hansard.major = '" . $this->major . "' ";
		} else {
			// We're getting results for all debates/wrans/etc.
			$majorwhere = '';
		}

		$q = $this->db->query("SELECT hansard.subsection_id,
								hansard.section_id,
								hansard.htype,
								hansard.gid,
								hansard.major,
								hansard.hdate,
								hansard.htime,
								hansard.speaker_id,
								COUNT(*) AS total_speeches,
								epobject.body,
								epobject_section.body AS body_section,
								epobject_subsection.body AS body_subsection,
								epobject_subsection.epobject_id AS epobject_id_subsection,
                                hansard_subsection.gid AS gid_subsection
						FROM	hansard
						LEFT JOIN member
				ON hansard.speaker_id = member.member_id
						LEFT JOIN epobject
                                ON hansard.epobject_id = epobject.epobject_id
						LEFT JOIN epobject AS epobject_section
                                ON hansard.section_id = epobject_section.epobject_id
						LEFT JOIN epobject AS epobject_subsection
                                ON hansard.subsection_id = epobject_subsection.epobject_id
						LEFT JOIN hansard AS hansard_subsection
                                ON hansard.subsection_id = hansard_subsection.epobject_id
						WHERE	$where
								$majorwhere
						GROUP BY hansard.subsection_id
						ORDER BY hansard.hdate DESC, hansard.hpos DESC
						LIMIT	$items_to_list
						");


		$speeches = array ();
		
		if ($q->rows() > 0) {
			for ($n=0; $n<$q->rows(); $n++) {
			
				$speech = array (
					'subsection_id'	=> $q->field($n, 'subsection_id'),
					'section_id'	=> $q->field($n, 'section_id'),
					'htype'			=> $q->field($n, 'htype'),
					'major'			=> $q->field($n, 'major'),
					'hdate'			=> $q->field($n, 'hdate'),
					'htime'			=> $q->field($n, 'htime'),
					'speaker_id'	=> $q->field($n, 'speaker_id'),
					'body'			=> $q->field($n, 'body'),
					'body_section'  => $q->field($n, 'body_section'),
					'body_subsection'  => $q->field($n, 'body_subsection'),
					'total_speeches' => $q->field($n, 'total_speeches')
				);
				// Remove the "uk.org.publicwhip/blah/" from the gid:
				// (In includes/utility.php)
				$speech['gid'] = fix_gid_from_db( $q->field($n, 'gid') );

                // Cache parent id to speed up _get_listurl
                $this->epobjectid_to_gid[$q->field($n, 'epobject_id_subsection') ] = fix_gid_from_db( $q->field($n, 'gid_subsection') );
		
				$url_args = array ('m'=>$q->field($n, 'speaker_id'));
				$speech['listurl'] = $this->_get_listurl($speech, $url_args);
				
				$speeches[] = $speech;
			}
		}

		if (count($speeches) > 0) {
			// Get the subsection texts.
			
			for ($n=0; $n<count($speeches); $n++) {
				$thing = $hansardmajors[$speeches[$n]['major']]['title'];
				// Add the parent's body on...
				$speeches[$n]['parent']['body'] = $thing . ' &#8212; ' . $speeches[$n]['body_section'];
				if ($speeches[$n]['subsection_id'] != $speeches[$n]['section_id']) {
					$speeches[$n]['parent']['body'] .= ': ' . $speeches[$n]['body_subsection'];
				}
			}
			
			$data['rows'] = $speeches;
		
		} else {
			$data['rows'] = array ();
		}
		return $data;
	
	}
	
	
	
	function _get_data_by_search ($args) {
		
		// Creates a fairly standard $data structure for the search function.
		// Will probably be rendered by the hansard_search.php template.
		
		// $args is an associative array with 's'=>'my search term' and
		// (optionally) 'p'=>1  (the page number of results to show) annd
        // (optionall) 'pop'=>1 (if "popular" search link, so don't log)
		global $PAGE, $hansardmajors;

		if (isset($args['s'])) {
			// $args['s'] should have been tidied up by the time we get here.
			// eg, by doing filter_user_input($s, 'strict');
			$searchstring = $args['s'];
		} else {
			$PAGE->error_message("No search string");
			return false;
		}

		// What we'll return.
		$data = array ();
		
		$data['info']['s'] = $args['s'];
		
		// Allows us to specify how many results we want
		// Mainly for glossary term adding
		if (isset($args['num']) && $args['num']) {
			$results_per_page = $args['num'];
		}
		else {
			$results_per_page = 20;
		}
		if ($results_per_page > 1000)
			$results_per_page = 1000;
		
		$data['info']['results_per_page'] = $results_per_page;

	    // What page are we on?
		if (isset($args['p']) && is_numeric($args['p'])) {
			$page = $args['p'];
		} else {
			$page = 1;
		}
		$data['info']['page'] = $page;

		if (isset($args['e'])) {
			$encode = 'url';
		} else {
			$encode = 'html';
		}

        // Fetch count of number of matches
		global $SEARCHENGINE;
	
		$data['searchdescription'] = $SEARCHENGINE->query_description_long();
		$count = $SEARCHENGINE->run_count();
		$data['info']['total_results'] = $count;
		// Log this query so we can improve them - if it wasn't a "popular
		// query" link
		if (! isset($args['pop']) or $args['pop'] != 1) {
			global $SEARCHLOG;
			$SEARCHLOG->add(
			array('query' => $searchstring,
				'page' => $page,
				'hits' => $count));
		}
		// No results.
		if ($count <= 0) {
			$data['rows'] = array();
			return $data;
		}

		// For Xapian's equivalent of an SQL LIMIT clause.
		$first_result = ($page-1) * $results_per_page;
		$data['info']['first_result'] = $first_result + 1; // Take account of LIMIT's 0 base.
	
		// Get the gids from Xapian
		$sort_order = 'date';
		if (isset($args['o'])) {
			if ($args['o']=='d') $sort_order = 'date';
			elseif ($args['o']=='c') $sort_order = 'created';
			elseif ($args['o']=='r') $sort_order = 'relevance';
		}

		$SEARCHENGINE->run_search($first_result, $results_per_page, $sort_order);
		$gids = $SEARCHENGINE->get_gids();
		if ($sort_order=='created') {
			$createds = $SEARCHENGINE->get_createds();
		}
		$relevances = $SEARCHENGINE->get_relevances();
		if (count($gids) <= 0) {
			// No results.
			$data['rows'] = array();
			return $data;
		}
		#if ($sort_order=='created') { print_r($gids); }
	
		// We'll put all the data in here before giving it to a template.
		$rows = array();
		
		// We'll cache the ids=>first_names/last_names of speakers here.
		$speakers = array();
		
		// We'll cache (sub)section_ids here:
		$hansard_to_gid = array();
		
		// Cycle through each result, munge the data, get more, and put it all in $data.
		for ($n=0; $n<count($gids); $n++) {
			$gid = $gids[$n];
			$relevancy = $relevances[$n];
			if ($sort_order=='created') {
				$created = substr($createds[$n], 0, strpos($createds[$n], ':'));
				if ($created<$args['threshold']) {
					$data['info']['total_results'] = $n;
					break;
				}
			}

			// Get the data for the gid from the database
			$q = $this->db->query("SELECT hansard.gid,
                                    hansard.hdate,
                                    hansard.section_id,
                                    hansard.subsection_id,
                                    hansard.htype,
                                    hansard.major,
                                    hansard.speaker_id,
				    hansard.hpos,
                                    epobject.body
                            FROM hansard, epobject
                            WHERE hansard.gid = '$gid'
                            AND hansard.epobject_id = epobject.epobject_id"
                        );

			if ($q->rows() > 1)
				$PAGE->error_message("Got more than one row getting data for $gid");
			if ($q->rows() == 0) {
				$PAGE->error_message("Unexpected missing gid $gid while searching");
				continue;
			}
		
			$itemdata = array();
			
			$itemdata['gid'] 			= fix_gid_from_db( $q->field(0, 'gid') );
			$itemdata['hdate'] 			= $q->field(0, 'hdate');	
			$itemdata['htype'] 			= $q->field(0, 'htype');		
			$itemdata['major'] 			= $q->field(0, 'major');
			$itemdata['section_id'] 	= $q->field(0, 'section_id');
			$itemdata['subsection_id'] 	= $q->field(0, 'subsection_id');
			$itemdata['relevance'] 		= $relevances[$n];			
			$itemdata['speaker_id'] 	= $q->field(0, 'speaker_id');
			$itemdata['hpos']		= $q->field(0, 'hpos');


			//////////////////////////
			// 1. Trim and highlight the body text.
			
			$body = $q->field(0, 'body');
		
			// We want to trim the body to an extract that is centered
			// around the position of the first search word.
			
			// we don't use strip_tags as it doesn't replace tags with spaces,
			// which means some words end up stuck together
			$extract = strip_tags_tospaces($body);

			// $bestpos is the position of the first search word
			$bestpos = $SEARCHENGINE->position_of_first_word($extract);
				
			// Where do we want to extract from the $body to start?
			$length_of_extract = 400; // characters.
			$startpos = $bestpos - ($length_of_extract / 2);
			if ($startpos < 0) {
				$startpos = 0;
			}
			
			// Trim it to length and position, adding ellipses.
			$extract = trim_characters ($extract, $startpos, $length_of_extract);

			// Highlight search words 
			$extract = $SEARCHENGINE->highlight($extract);
			
			$itemdata['body'] = $extract;

			//////////////////////////
			// 2. Create the URL to link to this bit of text.
			
			$id_data = array (
				'major'			=> $itemdata['major'],
				'htype' 		=> $itemdata['htype'],
				'gid' 			=> $itemdata['gid'],
				'section_id'	=> $itemdata['section_id'],
				'subsection_id'	=> $itemdata['subsection_id']
			);
			
			// We append the query onto the end of the URL as variable 's'
			// so we can highlight them on the debate/wrans list page.
			$url_args = array ('s' => $searchstring);

			$itemdata['listurl'] = $this->_get_listurl($id_data, $url_args, $encode);
			
			
			
			//////////////////////////
			// 3. Get the speaker for this item, if applicable.
			
			if ( $itemdata['speaker_id'] != 0) {
				$itemdata['speaker'] = $this->_get_speaker($itemdata['speaker_id'], $itemdata['hdate']);
			}
			
			
			//////////////////////////
			// 4. Get data about the parent (sub)section. TODO: CHECK THIS for major==4
			
			if ($itemdata['major'] && $hansardmajors[$itemdata['major']]['type'] == 'debate') {
				// Debate
				if ($itemdata['htype'] != 10) {
					$section = $this->_get_section($itemdata);
					$itemdata['parent']['body'] = $section['body'];
#					$itemdata['parent']['listurl'] = $section['listurl'];
					if ($itemdata['section_id'] != $itemdata['subsection_id']) {
						$subsection = $this->_get_subsection($itemdata);
						$itemdata['parent']['body'] .= ': ' . $subsection['body'];
#						$itemdata['parent']['listurl'] = $subsection['listurl'];
					}

				} else {
					// It's a section, so it will be its own title.
					$itemdata['parent']['body'] = $itemdata['body'];
					$itemdata['body'] = '';
				}
				
			} else {
				// Wrans or WMS
				$section = $this->_get_section($itemdata);
				$subsection = $this->_get_subsection($itemdata);
				$body = ($itemdata['major']==3?'Written Answers':'Written Ministerial Statements') . ' &#8212; ';
				if (isset($section['body'])) $body .= $section['body'];
				if (isset($subsection['body'])) $body .= ': ' . $subsection['body'];
				if (isset($subsection['listurl'])) $listurl = $subsection['listurl'];
				else $listurl = '';
				$itemdata['parent'] = array (
					'body' => $body,
					'listurl' => $listurl
				);
			}

			// Add this item's data onto the main array we'll be returning.
			$rows[] = $itemdata;
			
		}

		$data['rows'] = $rows;
	
		return $data;
	}
	


	function _get_data_by_calendar ($args) {
		// We should have come here via _get_data_by_calendar() in
		// DEBATELIST or WRANLIST, so $this->major should now be set.
		
		// You can ask for:
		// * The most recent n months - $args['months'] => n
		// * All months from one year - $args['year'] => 2004
		// * One month - $args['year'] => 2004, $args['month'] => 8
		// * The months from this year so far (no $args variables needed).
		
		// $args['onday'] may be like '2004-04-20' - if it appears in the 
		// calendar, this date will be highlighted and will have no link.
		
		// Returns a data structure of years, months and dates:
		// $data = array(
		// 		'info' => array (
		//			'page' => 'debates',
		//			'major'	=> 1
		//			'onpage' => '2004-02-01'
		//		),
		// 		'years' => array (
		//			'2004' => array (
		//				'01' => array ('01', '02', '03' ... '31'),
		//				'02' => etc...
		//			)
		//		)
		// )
		// It will just have entries for days for which we have relevant
		// hansard data.
		// But months that have no data will still have a month array (empty).
	
		// $data['info'] may have 'year' => 2004 if we're just viewing a single year.
		// $data['info'] may have 'prevlink' => '/debates/?y=2003' or something
		// if we're viewing recent months.
		
		global $DATA, $this_page, $PAGE, $hansardmajors;
		
		// What we return.
		$data = array(
			'info' => array(
				'page' => $this->listpage,
				'major' => $this->major
			)
		);
		
		// Set a variable so we know what we're displaying...
		if (isset($args['months']) && is_numeric($args['months'])) {
		
			// A number of recent months (may wrap around to previous year).
			$action = 'recentmonths';
			
			// A check to prevent anyone requestion 500000 months.
			if ($args['months'] > 12) {
				$PAGE->error_message("Sorry, you can't view " . $args['months'] . " months.");
				return $data;
			}
		
		} elseif (isset($args['year']) && is_numeric($args['year'])) {
			
			if (isset($args['month']) && is_numeric($args['month'])) {
				// A particular month.
				$action = 'month';
			} else {
				// A single year.
				$action = 'year';
			}
		
		} else {
			// The year to date so far.
			$action = 'recentyear';
		}
		
		if (isset($args['onday'])) {
			// Will be highlighted.
			$data['info']['onday'] = $args['onday'];
		}
		
		// This first if/else section is simply to fill out these variables:
		
		$firstyear = '';
		$firstmonth = '';
		$finalyear = '';
		$finalmonth = '';

		if ($action == 'recentmonths' || $action == 'recentyear') {
			
			// We're either getting the most recent $args['months'] data 
			// Or the most recent year's data.
			// (Not necessarily recent to *now* but compared to the most
			// recent date for which we have relevant hansard data.)
			// 'recentyear' will include all the months that haven't happened yet.

			// Find the most recent date we have data for.
			$q = $this->db->query("SELECT MAX(hdate) AS hdate
							FROM	hansard
							WHERE	major = '" . mysql_escape_string($this->major) . "'
							");

			if ($q->field(0, 'hdate') != NULL) {
				$recentdate = $q->field(0, 'hdate');
			} else {
				$PAGE->error_message("Couldn't find the most recent date");
				return $data;
			}

			// What's the first date of data we need to fetch?
			list($finalyear, $finalmonth, $day) = explode('-', $recentdate);					

			$finalyear = intval($finalyear);
			$finalmonth = intval($finalmonth);
				
			if ($action == 'recentmonths') {
			
				// We're getting this many recent months.
				$months_to_fetch = $args['months'];

				// The month we need to start getting data.
				$firstmonth = intval($finalmonth) - $months_to_fetch + 1;

				$firstyear = $finalyear;

				if ($firstmonth < 1) {
					// Wrap round to previous year.
					$firstyear--;
					// $firstmonth is negative, hence the '+'.
					$firstmonth = 12 + $firstmonth; // ()
				};
				
			} else {
				// $action == 'recentyear'
				
				// Get the most recent year's results.
				$firstyear = $finalyear;
				$firstmonth = 1;
			}
			


		} else {
			// $action == 'year' or 'month'.

			$firstyear = $args['year'];
			$finalyear = $args['year'];

			if ($action == 'month') {
				$firstmonth = intval($args['month']);
				$finalmonth = intval($args['month']);
			} else {
				$firstmonth = 1;
				$finalmonth = 12;
			}


			// Check there are some dates for this year/month.
			$q = $this->db->query("SELECT epobject_id
							FROM	hansard
							WHERE	hdate >= '" . mysql_escape_string($firstyear) . "-" . mysql_escape_string($firstmonth) . "-01'
							AND 	hdate <= '" . mysql_escape_string($finalyear) . "-" . mysql_escape_string($finalmonth) . "-31'
							LIMIT 	1
							");
			
			if ($q->rows() == 0) {
				// No data in db, so return empty array!
				return $data;
			}
			
		}

		// OK, Now we have $firstyear, $firstmonth, $finalyear, $finalmonth set up.
	
		// Get the data...
		
		if ($finalyear > $firstyear || $finalmonth >= $firstmonth) {
			$where = "AND hdate <= '" . mysql_escape_string($finalyear) . "-" . mysql_escape_string($finalmonth) . "-31'";
		} else {
			$where = '';
		}

		$q =  $this->db->query("SELECT 	DISTINCT(hdate) AS hdate
						FROM		hansard
						WHERE		major = '" . mysql_escape_string($this->major) . "'
						AND			hdate >= '" . mysql_escape_string($firstyear) . "-" . mysql_escape_string($firstmonth) . "-01'
						$where
						ORDER BY	hdate ASC
						");
		
		if ($q->rows() > 0) {

			// We put the data in this array. See top of function for the structure.
			$years = array();
			
			for ($row=0; $row<$q->rows(); $row++) {
				
				list($year, $month, $day) = explode('-', $q->field($row, 'hdate'));
				
				$month = intval($month);
				$day = intval($day);

				// Add as a link.
				$years[$year][$month][] = $day;
			}

			// If nothing happened on one month we'll have fetched nothing for it.
			// So now we need to fill in any gaps with blank months.
			
			// We cycle through every year and month we're supposed to have fetched.
			// If it doesn't have an array in $years, we create an empty one for that
			// month.
			for ($y = $firstyear; $y <= $finalyear; $y++) {

				if (!isset($years[$y])) {
					$years[$y] = array(1=>array(), 2=>array(), 3=>array(), 4=>array(), 5=>array(), 6=>array(), 7=>array(), 8=>array(), 9=>array(), 10=>array(), 11=>array(), 12=>array());
				} else {
				
					// This year is set. Check it has all the months...
					
					$minmonth = $y == $firstyear ? $firstmonth : 1;
					$maxmonth = $y == $finalyear ? $finalmonth : 12;
				
					for ($m = $minmonth; $m <= $maxmonth; $m++) {
						if (!isset($years[$y][$m])) {
							$years[$y][$m] = array();
						}
					}
					ksort($years[$y]);

				}
			}

			$data['years'] = $years;
		}
		
		// Set the next/prev links.

		$YEARURL = new URL($hansardmajors[$this->major]['page_year']);
			
		if ($this_page == 'debatesyear' || $this_page == 'wransyear' || $this_page == 'whallyear' || $this_page == 'wmsyear' || $this_page == 'lordsdebatesyear') {
			// Only need next/prev on these pages.
			// Not sure this is the best place for this, but...

			$nextprev = array();
			
			$UPURL = new URL('hansard');
			
			$nextprev['up'] = array (
				'body' => 'House of Commons',
				'url' => $UPURL->generate(),
				'title' => ''
			);
			
			if ($action == 'recentyear') {
				// Assuming there will be a previous year!
				
				$YEARURL->insert(array('y'=> $firstyear-1));
				
				$nextprev['prev'] = array (
					'body' => $firstyear - 1,
					'url' => $YEARURL->generate()				
				);
			
			} else {
				// action is 'year'.

				$nextprev['prev'] = array ('body' => 'Previous year');
				$nextprev['next'] = array ('body' => 'Next year');
				
				$q = $this->db->query("SELECT DATE_FORMAT(MIN(hdate), '%Y') AS minyear,
										DATE_FORMAT(MAX(hdate), '%Y') AS maxyear
										FROM	hansard WHERE major = " . $this->major . "
								LIMIT	1");
				
				$minyear = $q->field(0, 'minyear');
				$maxyear = $q->field(0, 'maxyear');

				if ($action == 'year' && $minyear < $firstyear) {

					$prevyear = $firstyear - 1;
					
					$YEARURL->insert(array('y'=>$prevyear));
					
					$nextprev['prev']['title'] = $prevyear;
					$nextprev['prev']['url'] = $YEARURL->generate();
				}

				if ($maxyear > $finalyear) {
					
					$nextyear = $finalyear + 1;
					
					$YEARURL->insert(array('y'=>$nextyear));
					
					$nextprev['next']['title'] = $nextyear;
					$nextprev['next']['url'] = $YEARURL->generate();
				}
			}
			
			// Will be used in $PAGE.
			$DATA->set_page_metadata($this_page, 'nextprev', $nextprev);
		}
		
		return $data;
	
	}
	
	
	
	function _get_hansard_data ($input) {
		global $hansardmajors;
		// Generic function for getting hansard data from the DB.
		// It returns an empty array if no data was found.
		// It returns an array of items if 1 or more were found.
		// Each item is an array of key/value pairs. 
		// eg:
		/*	
			array (
				0	=> array (
					'epobject_id'	=> '2',
					'htype'			=> '10',
					'section_id'		=> '0',
					etc...
				),
				1	=> array (
					'epobject_id'	=> '3',
					etc...
				)
			);
		*/
		
		// $input['amount'] is an associative array indicating what data should be fetched.
		// It has the structure
		// 	'key' => true
		// Where 'true' indicates the data of type 'key' should be fetched.
		// Leaving a key/value pair out is the same as setting a key to false.
		
		// $input['amount'] can have any or all these keys:
		//	'body' 		- Get the body text from the epobject table.
		//	'comment' 	- Get the first comment (and totalcomments count) for this item.
		//	'votes'		- Get the user votes for this item.
		//	'speaker'	- Get the speaker for this item, where applicable.
		//  'excerpt' 	- For sub/sections get the body text for the first item within them.
		
		// $input['wherearr'] is an associative array of stuff for the WHERE clause, eg:
		// 	array ('id=' => '37', 'date>' => '2003-12-31');
		// $input['order'] is a string for the $order clause, eg 'hpos DESC'.
		// $input['limit'] as a string for the $limit clause, eg '21,20'.

		$amount 		= isset($input['amount']) ? $input['amount'] : array();
		$wherearr 		= isset($input['where']) ? $input['where'] : array();
		$order 			= isset($input['order']) ? $input['order'] : '';
		$limit 			= isset($input['limit']) ? $input['limit'] : '';
		$listurl_args	= isset($input['listurl_args']) ? $input['listurl_args'] : array();
		
		
		// The fields to fetch from db. 'table' => array ('field1', 'field2').
		$fieldsarr = array (
			'hansard' => array ('epobject_id', 'htype', 'gid', 'hpos', 'section_id', 'subsection_id', 'hdate', 'htime', 'source_url', 'major')
		);
		
		if (isset($amount['speaker']) && $amount['speaker'] == true) {
			$fieldsarr['hansard'][] = 'speaker_id';
		}
		
		if ((isset($amount['body']) && $amount['body'] == true) || 
			(isset($amount['comment']) && $amount['comment'] == true)
			) {
			$fieldsarr['epobject'] = array ('title', 'body');
			$join = 'LEFT OUTER JOIN epobject ON hansard.epobject_id = epobject.epobject_id';
		} else {
			$join = '';
		}

		
		$fieldsarr2 = array ();
		// Construct the $fields clause.
		foreach ($fieldsarr as $table => $tablesfields) {
			foreach ($tablesfields as $n => $field) {
				$fieldsarr2[] = $table.'.'.$field;
			}
		}
		$fields = implode(', ', $fieldsarr2);
		
		$wherearr2 = array ();
		// Construct the $where clause.
		foreach ($wherearr as $key => $val) {
			$wherearr2[] = "$key'" . mysql_escape_string($val) . "'";
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
						FROM 	hansard
						$join
						WHERE $where
						$order
						$limit
						");
		
		// Format the data into an array for returning.
		$data = array ();
						
		if ($q->rows() > 0) {
		
			for ($n=0; $n<$q->rows(); $n++) {
			
				// Where we'll store the data for this item before adding
				// it to $data.
				$item = array();
				
				// Put each row returned into its own array in $data.
				foreach ($fieldsarr as $table => $tablesfields) {
					foreach ($tablesfields as $m => $field) {
						$item[$field] = $q->field($n, $field);
					}
				}
				
				if (isset($item['gid'])) {
					// Remove the "uk.org.publicwhip/blah/" from the gid:
					// (In includes/utility.php)
					$item['gid'] = fix_gid_from_db( $item['gid'] );
				}
				
				
				// Get the number of items within a section or subsection.
				// It could be that we can do this in the main query?
				// Not sure.
				if ( ($this->major && $hansardmajors[$this->major]['type']=='debate') && ($item['htype'] == '10' || $item['htype'] == '11') ) {
					
					if ($item['htype'] == '10') {
						// Section - get a count of items within this section that 
						// don't have a subsection heading.
						$where = "section_id = '" . $item['epobject_id'] . "' 
							AND subsection_id = '" . $item['epobject_id'] . "'";
					
					} else {
						// Subsection - get a count of items within this subsection.
						$where = "subsection_id = '" . $item['epobject_id'] . "'";
					}
				
					$r = $this->db->query("SELECT COUNT(*) AS count 
									FROM 	hansard 
									WHERE 	$where
									");
									
					if ($r->rows() > 0) {
						$item['contentcount'] = $r->field(0, 'count');
					} else {
						$item['contentcount'] = '0';
					}
				}
				
			
				// Get the body of the first item with the section or 
				// subsection. This can then be printed as an excerpt
				// on the daily list pages.
				
				if ((isset($amount['excerpt']) && $amount['excerpt'] == true) && 		
					($item['htype'] == '10' || 
					$item['htype'] == '11')
					) {
					if ($item['htype'] == '10') {
						$where = "hansard.section_id = '" . mysql_escape_string($item['epobject_id']) . "' 
							AND hansard.subsection_id = '" . mysql_escape_string($item['epobject_id']) . "'";				
					} elseif ($item['htype'] == '11') {
						$where = "hansard.subsection_id = '" . mysql_escape_string($item['epobject_id']) . "'";					
					}

					$r = $this->db->query("SELECT epobject.body 
									FROM 	hansard,
											epobject
									WHERE	$where
									AND		hansard.epobject_id = epobject.epobject_id
									ORDER BY hansard.hpos ASC
									LIMIT	1");

					if ($r->rows() > 0) {
						$item['excerpt'] = $r->field(0, 'body');
					}
				}
				
				
				// We generate two permalinks for each item:
				// 'listurl' is the URL of the item in the full list view.
				// 'commentsurl' is the URL of the item on its own page, with comments.


				// All the things we need to work out a listurl!
				$item_data = array (
					'major'			=> $this->major,
					'htype' 		=> $item['htype'],
					'gid' 			=> $item['gid'],
					'section_id'	=> $item['section_id'],
					'subsection_id'	=> $item['subsection_id']
				);
				

				$item['listurl'] = $this->_get_listurl($item_data);
				
				
				// Create a URL for where we can see all the comments for this item.
				if (isset($this->commentspage)) {
					$COMMENTSURL = new URL($this->commentspage);
					$getvar = $hansardmajors[$this->major]['gidvar'];
					if ($getvar == 'gid') {
						$COMMENTSURL->remove(array('id'));
					}
					$COMMENTSURL->insert(array ($getvar=>$item['gid']) );
					$item['commentsurl'] = $COMMENTSURL->generate();	
				}					
					
				
				// Get the user/anon votes items that have them.
				if ((isset($amount['votes']) && $amount['votes'] == true) && 
					$item['htype'] == '12' || $item['htype'] == '62') {
					// Debate speech or written answers (not questions).
				
					$item['votes'] = $this->_get_votes( $item['epobject_id'] );
				}


				// Get the speaker for this item, if applicable.
				if ( (isset($amount['speaker']) && $amount['speaker'] == true) &&
					$item['speaker_id'] != '') {
					
					$item['speaker'] = $this->_get_speaker($item['speaker_id'], $item['hdate']);
				}
				
				
				// Get comment count and (if any) most recent comment for each item.
				if (isset($amount['comment']) && $amount['comment'] == true) {	
				
					// All the things we need to get the comment data.
					$item_data = array (
						'htype' => $item['htype'],
						'epobject_id' => $item['epobject_id']
					);
					
					$commentdata = $this->_get_comment($item_data);
					$item['totalcomments'] = $commentdata['totalcomments'];
					$item['comment'] = $commentdata['comment'];
				}
				
				
				// Add this item on to the array of items we're returning.
				$data[$n] = $item;
			}
		}
		
		return $data;
	}
	
	
	function _get_votes ($epobject_id) {
		// Called from _get_hansard_data().
		// Separated out here just for clarity.
		// Returns an array of user and anon yes/no votes for an epobject.
		
		$votes = array();
		
		// YES user votes.
		$q = $this->db->query("SELECT COUNT(vote) as totalvotes
						FROM	uservotes
						WHERE	epobject_id = '" . mysql_escape_string($epobject_id) . "'
						AND 	vote = '1'
						GROUP BY epobject_id");
		
		if ($q->rows() > 0) {
			$votes['user']['yes'] = $q->field(0, 'totalvotes');
		} else {
			$votes['user']['yes'] = '0';
		}

		// NO user votes.
		$q = $this->db->query("SELECT COUNT(vote) as totalvotes
						FROM	uservotes
						WHERE	epobject_id = '" . mysql_escape_string($epobject_id) . "'
						AND 	vote = '0'
						GROUP BY epobject_id");

		if ($q->rows() > 0) {
			$votes['user']['no'] = $q->field(0, 'totalvotes');
		} else {
			$votes['user']['no'] = '0';
		}


		// Get the anon votes for each item.
		
		$q = $this->db->query("SELECT yes_votes,
								no_votes
						FROM	anonvotes
						WHERE	epobject_id = '" . mysql_escape_string($epobject_id) . "'");
		
		if ($q->rows() > 0) {
			$votes['anon']['yes'] = $q->field(0, 'yes_votes');
			$votes['anon']['no'] = $q->field(0, 'no_votes');
		} else {
			$votes['anon']['yes'] = '0';
			$votes['anon']['no'] = '0';
		}	
		
		return $votes;
	}
	
	
	function _get_listurl ($id_data, $url_args=array(), $encode='html') {
		global $hansardmajors;
		// Generates an item's listurl - this is the 'contextual' url
		// for an item, in the full list view with an anchor (if appropriate).
		
		// $id_data is like this:
		//		$id_data = array (
		//		'major' 		=> 1,
		//		'htype' 		=> 12,
		//		'gid' 			=> 2003-10-30.421.4h2,
		//		'section_id'	=> 345,
		//		'subsection_id'	=> 346
		// );
		
		// $url_args is an array of other key/value pairs to be appended in the GET string.
		if ($id_data['major'])
			$LISTURL = new URL($hansardmajors[$id_data['major']]['page_all']);
		else
			$LISTURL = new URL('wrans');
		
		$fragment = '';

		if ($id_data['htype'] == '11' || 
			$id_data['htype'] == '10'
			) {
			// This is a section or subsection.
			// We just use the gid of this item.
			
			$LISTURL->insert( array( 'id' => $id_data['gid'] ) );

		} else {
			// A debate speech or question/answer, etc.
			// We need to get the gid of the parent (sub)section for this item.
			// We use this with the gid of the item itself as an #anchor.
			
			$parent_epobject_id = $id_data['subsection_id'];

			
			// Find the gid of this item's (sub)section.
			$parent_gid = '';
			
			if (isset($this->epobjectid_to_gid[ $parent_epobject_id ])) {
				// We've previously cached the gid for this epobject_id, so use that.
				
				$parent_gid = $this->epobjectid_to_gid[ $parent_epobject_id ];
			
			} else {
				// We haven't cached the gid, so fetch from db.
				
				$r = $this->db->query("SELECT gid
								FROM 	hansard
								WHERE	epobject_id = '" . mysql_escape_string($parent_epobject_id) . "'
								");
								
				if ($r->rows() > 0) {
					// Remove the "uk.org.publicwhip/blah/" from the gid:
					// (In includes/utility.php)
					$parent_gid = fix_gid_from_db( $r->field(0, 'gid') );
					
					// Cache it for if we need it again:
					$this->epobjectid_to_gid[ $parent_epobject_id ] = $parent_gid;
				}
			}
			
			if ($parent_gid != '') {
				// We have a gid so add to the URL.
				$LISTURL->insert( array( 'id' => $parent_gid ) );
				// Use a truncated form of this item's gid for the anchor.
				
				$fragment = '#g' . gid_to_anchor($id_data['gid']);
			}
		}
				
		if (count($url_args) > 0) {
			$LISTURL->insert( $url_args);
		}
		
		return $LISTURL->generate($encode) . $fragment;
	}


	function _get_speaker ($speaker_id, $hdate) {
		// Pass it the id of a speaker. If $this->speakers doesn't
		// already contain data about the speaker, it's fetched from the DB
		// and put in $this->speakers.
		
		// So we don't have to keep fetching the same speaker info about chatterboxes.
		
		if ($speaker_id != 0) {
		
			if (!isset( $this->speakers[ $speaker_id ] )) {
				// Speaker isn't cached, so fetch the data.

				$q = $this->db->query("SELECT title, first_name,
										last_name,
										house,
										constituency,
										party,
                                        person_id
								FROM 	member
								WHERE	member_id = '" . mysql_escape_string($speaker_id) . "'
								");
								
				if ($q->rows() > 0) {
					// *SHOULD* only get one row back here...
					$house = $q->field(0, 'house');
					if ($house==1) {
						$URL = new URL('mp');
					} elseif ($house==2) {
						$URL = new URL('peer');
					}
					$URL->insert( array ('m' => $speaker_id) );
					$speaker = array (
						'member_id'		=> $speaker_id,
						'title'			=> $q->field(0, 'title'),
						"first_name"	=> $q->field(0, "first_name"),
						"last_name"		=> $q->field(0, "last_name"),
						'house'			=> $q->field(0, 'house'),
						"constituency"	=> $q->field(0, "constituency"),
						"party"			=> $q->field(0, "party"),
						"person_id"		=> $q->field(0, "person_id"),
						"url"			=> $URL->generate(),
					);

					global $parties;
					// Manual fix for Speakers.
					if (isset($parties[$speaker['party']])) {
						$speaker['party'] = $parties[$speaker['party']];
					}

					$q = $this->db->query("SELECT dept, position FROM moffice WHERE person=$speaker[person_id]
								AND to_date>='$hdate' AND from_date<='$hdate'");
					if ($q->rows() > 0) {
						for ($row=0; $row<$q->rows(); $row++) {
							$dept = $q->field($row, 'dept');
							$pos = $q->field($row, 'position');
							if ($pos && $pos != 'Chairman') {
								$speaker['office'][] = array(
									'dept' => $dept,
									'position' => $pos,
									'pretty' => prettify_office($pos, $dept)
								);
							}
						}
					}
					$this->speakers[ $speaker_id ] = $speaker;
					
					return $speaker;
				} else {
					return array();
				}
			} else {
				// Already cached, so just return that.
				return $this->speakers[ $speaker_id ];
			}
		} else {
			return array();
		}
	}
	
	
	
	function _get_comment ($item_data) {
		// Pass it some variables belonging to an item and the function
		// returns an array containing:
		// 1) A count of the comments within this item.
		// 2) The details of the most recent comment posted to this item.
		
		// Sections/subsections have (1) (the sum of the comments
		// of all contained items), but not (2).
		
		// What we return.
		$totalcomments = $this->_get_comment_count_for_epobject($item_data);
		$comment = array();
		
		if ($item_data['htype'] == '12' || $item_data['htype'] == '13') {		
			
			// Things which can have comments posted directly to them.

			if ($totalcomments > 0) {
				
				// Get the first comment.
				
				// Not doing this for Wrans sections because we don't
				// need it anywhere. Arbitrary but it'll save us MySQL time!
				
				$q = $this->db->query("SELECT c.comment_id,
									c.user_id,
									c.body,
									c.posted,
									u.firstname,
									u.lastname
							FROM	comments c, users u
							WHERE	c.epobject_id = '" . mysql_escape_string($item_data['epobject_id']) . "'
							AND		c.user_id = u.user_id
							AND		c.visible = 1
							ORDER BY c.posted ASC
							LIMIT	1
							");
				
				// Add this comment to the data structure.
				$comment = array (
					'comment_id' => $q->field(0, 'comment_id'),
					'user_id'	=> $q->field(0, 'user_id'),
					'body'		=> $q->field(0, 'body'),
					'posted'	=> $q->field(0, 'posted'),
					'username'	=> $q->field(0, 'firstname') .' '. $q->field(0, 'lastname')
				);
			}
			
		} 	
		
		// We don't currently allow people to post comments to a section
		// or subsection itself, only the items within them. So 
		// we don't get the most recent comment. Because there isn't one.
		
		$return = array (
			'totalcomments' => $totalcomments,
			'comment' => $comment
		);
		
		return $return;
	}
	
	
	function _get_comment_count_for_epobject ($item_data) {
		global $hansardmajors;
		// What it says on the tin.
		// $item_data must have 'htype' and 'epobject_id' elements. TODO: Check for major==4

		if (($hansardmajors[$this->major]['type']=='debate') &&
			($item_data['htype'] == '10' || $item_data['htype'] == '11')
			) {
			// We'll be getting a count of the comments on all items 
			// within this (sub)section.
			$from = "comments, hansard";
			$where = "comments.epobject_id = hansard.epobject_id
					AND subsection_id = '" . $item_data['epobject_id'] . "'";

			if ($item_data['htype'] == '10') {
				// Section - get a count of comments within this section that 
				// don't have a subsection heading.
				$where .= " AND section_id = '" . $item_data['epobject_id'] . "'";
			} 
		
		} else {
			// Just getting a count of the comments on this item.
			$from = "comments";
			$where = "epobject_id = '" . mysql_escape_string($item_data['epobject_id']) . "'";
		}

		$q = $this->db->query("SELECT COUNT(*) AS count
						FROM 	$from
						WHERE	$where
						AND		visible = 1
		
						");
		
		return $q->field(0, 'count');
	}
	
	
	
	function _get_trackback_data ($itemdata) {
		// Returns a array of data we need to create the Trackback Auto-discovery
		// RDF on a page.
		
		// We're assuming that we're only using this on a page where there's only 
		// one 'thing' to be trackbacked to. ie, we don't add #anchor data onto the
		// end of the URL so we can include this RDF in full lists of debate speeches.
		
		$trackback = array();
		
		$title = '';
		
		if (isset($itemdata['speaker']) && isset($itemdata['speaker']['first_name'])) {
			// This is probably a debate speech.
			$title .= $itemdata['speaker']['first_name'] . ' ' . $itemdata['speaker']['last_name'] . ': ';
		}
		
		$trackback['title'] = $title . trim_characters($itemdata['body'], 0, 200);
		
		// We're just saying this was in GMT...
		$trackback['date'] = $itemdata['hdate'] . 'T' . $itemdata['htime'] . '+00:00';

		// The URL of this particular item.
		// For (sub)sections we link to their listing page.
		// For everything else, to their individual pages.
		if ($itemdata['htype'] == '10' ||
			$itemdata['htype'] == '11'
			) {
			$url = $itemdata['listurl'];
		} else {
			$url = $itemdata['commentsurl'];
		}
		$trackback['itemurl'] = 'http://' . DOMAIN . $url;
		
		// Getting the URL the user needs to ping for this item.
		$URL = new URL('trackback');
		$URL->insert(array('e'=>$itemdata['epobject_id']));

		$trackback['pingurl'] = 'http://' . DOMAIN . $URL->generate('html');
		
	
		return $trackback;
	
	}


	function _get_data_by_gid ($args) {
	
		// We need to get the data for this gid.
		// Then depending on what htype it is, we get the data for other items too.
		global $DATA, $this_page, $hansardmajors;

		debug (get_class($this), "getting data by gid");

		// Where we'll put all the data we want to render.
		$data = array ();

		// Get the information about the item this URL refers to.
		$itemdata = $this->_get_item($args);

	        // If part of a Written Answer (just question or just answer), select the whole thing
	        if (isset($itemdata['major']) && $hansardmajors[$itemdata['major']]['type']=='other' and ($itemdata['htype'] == '12' or $itemdata['htype'] == '13')) {
	            // find the gid of the subheading which holds this part
	            $input = array (
	                'amount' => array('gid' => true),
	                'where' => array ( 
	                    'epobject_id=' => $itemdata['subsection_id'],
	                ),
	            );
	            $parent = $this->_get_hansard_data($input);
	            // display that item, i.e. the whole of the Written Answer
	            debug (get_class($this), "instead of " . $args['gid'] . " selecting subheading gid " . $parent[0]['gid'] . " to get whole wrans");
	            $args['gid'] = $parent[0]['gid'];
	            $itemdata = $this->_get_item($args);
		    $itemdata['redirected_gid'] = $args['gid'];
	        }

		# If a WMS main heading, go to next gid
		if (isset($itemdata['major']) && $itemdata['major']==4 && $itemdata['htype'] == '10') {
			$input = array (
				'amount' => array('gid' => true),
				'where' => array(
					'section_id=' => $itemdata['epobject_id'],
				),
				'order' => 'hpos ASC',
				'limit' => 1
			);
			$next = $this->_get_hansard_data($input);
			debug (get_class($this), 'instead of ' . $args['gid'] . ' moving to ' . $next[0]['gid']);
			$args['gid'] = $next[0]['gid'];
			$itemdata = $this->_get_item($args);
			$itemdata['redirected_gid'] = $args['gid'];
		}

		if ($itemdata) {

			// So we know something about this item from outside.
			// So we can associate trackbacks and things with it.
			if (isset($itemdata['htype'])) {
				$this->htype = $itemdata['htype'];
			}
			if (isset($itemdata['epobject_id'])) {
				$this->epobject_id = $itemdata['epobject_id'];
			}
			if (isset($itemdata['gid'])) {
				$this->gid = $itemdata['gid'];
			}

			// We'll use these for page headings/titles:
			$data['info']['date'] = $itemdata['hdate'];
			$data['info']['text'] = $itemdata['body'];
			$data['info']['major'] = $this->major;
			if (isset($itemdata['redirected_gid'])) {
				$data['info']['redirected_gid'] = $itemdata['redirected_gid'];
			}
			
			// If we have a member id we'll pass it on to the template so it
			// can highlight all their speeches.
			if (isset($args['member_id'])) {
				$data['info']['member_id'] = $args['member_id'];
			}
			if (isset($args['person_id'])) {
				$data['info']['person_id'] = $args['person_id'];
			}
			
			if (isset($args['s']) && $args['s'] != '') {
				// We have some search term words that we could highlight
				// when rendering.
				$data['info']['searchstring'] = $args['s'];
			}
			
			// Shall we turn glossarising on?
			if (isset($args['glossarise']) && $args['glossarise'] == 1) {
				// We have some search term words that we could highlight
				// when rendering.
				$data['info']['glossarise'] = $args['glossarise'];
			}
											
			// Get the section and subsection headings for this item.
			$sectionrow = $this->_get_section($itemdata);
			$subsectionrow = $this->_get_subsection($itemdata);

			// Get the nextprev links for this item, to link to next/prev pages.
			// Duh.
			if ($itemdata['htype'] == '10') {
				$nextprev = $this->_get_nextprev_items( $sectionrow );
			
			} elseif ($itemdata['htype'] == '11') {
				$nextprev = $this->_get_nextprev_items( $subsectionrow );
			
			} else {
				// Ordinary lowly item.
				$nextprev = $this->_get_nextprev_items( $itemdata );
				
				if (isset($subsectionrow['gid'])) {
					$nextprev['up']['url'] 		= $subsectionrow['listurl'];
					$nextprev['up']['title'] 	= $subsectionrow['body'];
				} else {
					$nextprev['up']['url'] 		= $sectionrow['listurl'];
					$nextprev['up']['title'] 	= $sectionrow['body'];	
				}
				$nextprev['up']['body']		= 'See the whole debate';
			}
			
			
			// We can then access this from $PAGE and the templates.
			$DATA->set_page_metadata($this_page, 'nextprev', $nextprev);
			
			
			// Now get all the non-heading rows.

			// What data do we want for each item?
			$amount = array (
				'body' => true,
				'speaker' => true,
				'comment' => true,
				'votes' => true
			);

			if ($itemdata['htype'] == '10') {
				// This item is a section, so we're displaying all the items within 
				// it that aren't within a subsection.
				
				$sectionrow['trackback'] = $this->_get_trackback_data($sectionrow);

				$input = array (
				 	'amount' => $amount,
				 	'where' => array ( 
						'section_id=' => $itemdata['epobject_id'],
						'subsection_id=' => $itemdata['epobject_id']
					),
					'order' => 'hpos ASC'
				);
				
				$data['rows'] = $this->_get_hansard_data($input);
				if (!count($data['rows']) || (count($data['rows'])==1 && strstr($data['rows'][0]['body'], 'was asked'))) {
			
					$input = array (
						'amount' => array (
							'body' => true,
							'comment' => true,
							'excerpt' => true
						),
						'where' => array (
							'section_id='	=> $sectionrow['epobject_id'],
							'htype='		=> '11',
							'major='		=> $this->major
						),
						'order' => 'hpos'
					);
					$data['subrows'] = $this->_get_hansard_data($input);
					if (count($data['subrows']) == 1) {
						return array('info' => array('redirected_gid' => $data['subrows'][0]['gid']));
					}
				}
			} elseif ($itemdata['htype'] == '11') {
				// This item is a subsection, so we're displaying everything within it.
				
				$subsectionrow['trackback'] = $this->_get_trackback_data($subsectionrow);
				
				$input = array (
					'amount' => $amount,
					'where' => array ( 
						'subsection_id=' => $itemdata['epobject_id']
					),
					'order' => 'hpos ASC'
				);
				
				$data['rows'] = $this->_get_hansard_data($input);
			
			
			} elseif ($itemdata['htype'] == '12' || $itemdata['htype'] == '13') {
				// Debate speech or procedural, so we're just displaying this one item.
				
				$itemdata['trackback'] = $this->_get_trackback_data($itemdata);

				$data['rows'][] = $itemdata;	
							
			}
			
			// Put the section and subsection at the top of the rows array.
			if (count($subsectionrow) > 0 &&
				$subsectionrow['gid'] != $sectionrow['gid']) {
				// If we're looking at a section, there may not be a subsection.
				// And if the subsectionrow and sectionrow aren't the same.
				array_unshift ($data['rows'], $subsectionrow);
			}
			
			array_unshift ($data['rows'], $sectionrow);	
			
		}

		return $data;

	}

	function _get_data_by_column($args) {
		global $DATA, $this_page;

		debug (get_class($this), "getting data by column");

		$input = array( 'amount' => array('body'=>true, 'comment'=>true),
		'where' => array( 'hdate='=>$args['date'], 'major=' => $this->major, 'gid LIKE ' =>'%.'.$args['column'].'.%' ),
		'order' => 'hpos'
		);
		$data = $this->_get_hansard_data($input);
		#		$data = array();

		#		$itemdata = $this->_get_item($args);

		#		if ($itemdata) {
			#	$data['info']['date'] = $itemdata['hdate'];
			#			$data['info']['text'] = $itemdata['body'];
			#			$data['info']['major'] = $this->major;
			#		}
		return $data;
	}

}

class WMSLIST extends WRANSLIST {
	var $major = 4;
	var $listpage = 'wms';
	var $commentspage = 'wms';
	function wmslist () {
		$this->db = new ParlDB;
		$this->gidprefix .= 'wms/';
	}
	function _get_data_by_recent_wms($args = array()) {
		return $this->_get_data_by_recent_wrans($args);
	}		
}

class WHALLLIST extends DEBATELIST {
	var $major = 2;
	var $listpage = 'whalls';
	var $commentspage = 'whall';
	function whalllist () {
		$this->db = new ParlDB;
		$this->gidprefix .= 'westminhall/';
	}
}

class LORDSDEBATELIST extends DEBATELIST {
	var $major = 101;
	var $listpage = 'lordsdebates';
	var $commentspage = 'lordsdebate';
	function lordsdebatelist() {
		$this->db = new ParlDB;
		$this->gidprefix .= 'lords/';
	}
}

class DEBATELIST extends HANSARDLIST {
	var $major = 1;
	
	// The page names we want to link to for item permalinks.
	// If you change listpage, you'll have to change it in _get_listurl too I'm afraid.
	var $listpage = 'debates';
	var $commentspage = 'debate';
	
	function debatelist () {
		$this->db = new ParlDB;
		$this->gidprefix .= 'debate/';
	}

	function _get_data_by_recent_mostvotes ($args) {
		// Get the most highly voted recent speeches.
		// $args may have 'days'=>7 and/or 'num'=>5
		// or something like that.
		
		// The most voted on things during how many recent days?
		if (isset($args['days']) && is_numeric($args['days'])) {
			$days = $args['days'];
		} else {
			$days = 7;
		}
		
		// How many results?
		if (isset($args['num']) && is_numeric($args['num'])) {
			$items_to_list = $args['num'];
		} else {
			$items_to_list = 5;
		}
		
		$q = $this->db->query("SELECT subsection_id,
								section_id,
								htype,
								gid,
								major,
								hdate,
								speaker_id,
								epobject.body,
								SUM(uservotes.vote) + anonvotes.yes_votes AS total_vote
						FROM	hansard,
								epobject
								LEFT OUTER JOIN uservotes ON epobject.epobject_id = uservotes.epobject_id
								LEFT OUTER JOIN anonvotes ON epobject.epobject_id = anonvotes.epobject_id
						WHERE		major = '" . $this->major . "'
						AND		hansard.epobject_id = epobject.epobject_id
						AND		hdate >= DATE_SUB(CURDATE(), INTERVAL $days DAY)
						GROUP BY epobject.epobject_id
						HAVING 	total_vote > 0
						ORDER BY total_vote DESC
						LIMIT	$items_to_list
						");
		
		// What we return.
		$data = array ();
		$speeches = array();		
	
		if ($q->rows() > 0) {
		
			for ($n=0; $n<$q->rows(); $n++) {
			
				$speech = array (
					'subsection_id'	=> $q->field($n, 'subsection_id'),
					'section_id'	=> $q->field($n, 'section_id'),
					'htype'			=> $q->field($n, 'htype'),
					'major'			=> $q->field($n, 'major'),
					'hdate'			=> $q->field($n, 'hdate'),
					'body'			=> $q->field($n, 'body'),
					'votes'			=> $q->field($n, 'total_vote')
				);
				
				// Remove the "uk.org.publicwhip/blah/" from the gid:
				// (In includes/utility.php)
				$speech['gid'] = fix_gid_from_db( $q->field($n, 'gid') );
			
				$speech['listurl'] = $this->_get_listurl($speech);
				
				$speech['speaker'] = $this->_get_speaker($q->field($n, 'speaker_id'), $q->field($n, 'hdate') );
				
				$speeches[] = $speech;
			}
		}
		
		if (count($speeches) > 0) {
			// Get the subsection texts.
			
			for ($n=0; $n<count($speeches); $n++) {
				//if ($this->major == 1) {
					// Debate.
					$parent = $this->_get_subsection ($speeches[$n]);

				//} else if ($this->major == 3) {
					// Wrans.
				//	$parent = $this->_get_section ($speeches[$n]);
				//}
				// Add the parent's body on...
				//if (isset($parent['body'])) {
					$speeches[$n]['parent']['body'] = $parent['body'];
				//} else {
				//	$parent = $this->_get_section ($speeches[$n]);
				//	$speeches[$n]['parent']['body'] = $parent['body'];
				//}
				
			}
			
			$data['rows'] = $speeches;		
		
		} else {
			$data['rows'] = array ();
		}

		$data['info']['days'] = $days;
		
		return $data;
	}
	

	function total_speeches () {
		
		$q = $this->db->query("SELECT COUNT(*) AS count FROM hansard WHERE major='" . $this->major . "' AND htype = 12");
		
		return $q->field(0, 'count');
	}


	function biggest_debates($args=array()) {
		// So we can just get the data back for special formatting
		// on the front page, without doing the whole display() thing.
		return $this->_get_data_by_biggest_debates($args);
	}

	
	function _get_data_by_biggest_debates($args=array()) {
		// Returns an array of the debates with most speeches in from 
		// a set number of recent days (that's recent days starting from the
		// most recent day that had any debates on).
		
		// $args['days'] is the number of days back to look for biggest debates.
		// (1 by default)
		// $args['num'] is the number of links to return (1 by default).
			
		$data = array();
		
		// Get the most recent day on which we have a debate.
		$recentday = $this->most_recent_day();
		if (!count($recentday))
			return array();
		
		if (!isset($args['days']) || !is_numeric($args['days'])) {
			$args['days'] = 1;
		}
		if (!isset($args['num']) || !is_numeric($args['num'])) {
			$args['num'] = 1;
		}
		
		if ($args['num'] == 1) {
			$datewhere = "h.hdate = '" . mysql_escape_string($recentday['hdate']) . "'";
		} else {
			$firstdate = gmdate('Y-m-d', $recentday['timestamp'] - (86400 * $args['days']));
			$datewhere = "h.hdate >= '" . mysql_escape_string($firstdate) . "'
						AND		h.hdate <= '" . mysql_escape_string($recentday['hdate']) . "'";
		}
			
		
		$q = $this->db->query("SELECT COUNT(*) AS count, 
								body, 
								h.hdate,
								sech.htype,
								sech.gid,
								sech.subsection_id,
								sech.section_id,
								sech.epobject_id
						FROM 	hansard h, epobject e, hansard sech 
						WHERE 	h.major = '" . $this->major . "' 
						AND 	$datewhere
						AND  	h.subsection_id = e.epobject_id 
						AND 	sech.epobject_id = h.subsection_id 
						GROUP BY h.subsection_id 
						ORDER BY count DESC 
						LIMIT 	" . mysql_escape_string($args['num']) . "
						");
		

		for ($row=0; $row<$q->rows; $row++) {
			
			// This array just used for getting further data about this debate.
			$item_data = array (
				'major'			=> $this->major,
				'gid'			=> fix_gid_from_db( $q->field($row, 'gid') ),
				'htype'			=> $q->field($row, 'htype'),
				'section_id'	=> $q->field($row, 'section_id'),
				'subsection_id'	=> $q->field($row, 'subsection_id'),
				'epobject_id'	=> $q->field($row, 'epobject_id')
			);
			
			$list_url 		= $this->_get_listurl( $item_data );
			$totalcomments	= $this->_get_comment_count_for_epobject( $item_data );
			
			$contentcount	= $q->field($row, 'count');
			$body 			= $q->field($row, 'body');
			$hdate			= $q->field($row, 'hdate');
			
		
			// This array will be added to $data, which is what gets returned.
			$debate = array (
				'contentcount'	=> $contentcount,
				'body'			=> $body,
				'hdate'			=> $hdate,
				'list_url'		=> $list_url,
				'totalcomments'	=> $totalcomments
			);
		
			// If this is a subsection, we're going to prepend the title
			// of the parent section, so let's get that.
			if ($item_data['htype'] == 11) {
				
				$r = $this->db->query("SELECT body
								FROM	epobject
								WHERE	epobject_id = '" . mysql_escape_string($item_data['section_id']) . "'
								");
				$debate['parent']['body'] = $r->field(0, 'body');
			}
			
			$data[] = $debate;
		}
		
		$data = array (
			'info' => array(),
			'data' => $data
		);
		
		return $data;
	
	}

}


class WRANSLIST extends HANSARDLIST {

	var $major = 3;
	
	// The page names we want to link to for item permalinks.
	// If you change listpage, you'll have to change it in _get_listurl too I'm afraid.
	var $listpage = 'wrans';
	var $commentspage = 'wrans'; // We don't have a separate page for wrans comments.

	function wranslist () {
		$this->db = new ParlDB;
		$this->gidprefix .= 'wrans/';
	}

	function total_questions () {
		$q = $this->db->query("SELECT COUNT(*) AS count FROM hansard WHERE major='" . $this->major . "' AND minor = 1");
		return $q->field(0, 'count');
	}

	function _get_data_by_mp($args = array()) {
		global $PAGE;
		$data = array();
		if (!isset($args['person_id']) || !is_numeric($args['person_id'])) {
			$PAGE->error_message ("Sorry, we need a valid person ID.");
			return $data;
		}
		$page = $args['page'] ? $args['page'] : 1;
		$offset = ($page - 1) * 20;
		$limit = 20;
		$person_id = $args['person_id'];
#		$q = $this->db->query("SELECT COUNT(gid) AS count FROM hansard h, member m
#					WHERE major = 3 AND htype = 12 AND minor = 1
#						AND h.speaker_id = m.member_id
#						AND person_id = $person_id");
#		$total_results = $q->field(0, 'count');
		$total_results = 0;
		$q = $this->db->query("SELECT e.body, es.body AS section_body, ess.body AS subsection_body,
						h.hdate, h.htype, h.gid, h.subsection_id, h.section_id, h.epobject_id
					FROM hansard h, epobject e, epobject es, epobject ess, member m
					WHERE h.htype = 12 AND major = 3 AND minor = 1
						AND h.epobject_id = e.epobject_id
						AND h.section_id = es.epobject_id
						AND h.subsection_id = ess.epobject_id
						AND h.speaker_id = m.member_id
						AND person_id = $person_id
					ORDER BY hdate DESC
					LIMIT $offset, $limit");
		for ($row = 0; $row < $q->rows; $row++) {
			$subsection_id = $q->field($row, 'subsection_id');
			$section_body = $q->field($row, 'section_body');
			$subsection_body = $q->field($row, 'subsection_body');
			$r = $this->db->query("SELECT e.body
							FROM	hansard h, epobject e
							WHERE	h.epobject_id = e.epobject_id
							AND	minor = 2
							AND		h.subsection_id = '" . $q->field($row, 'subsection_id') . "'
							");
			$answer = $r->field(0, 'body');
			$data[] = array(
				'hdate' => $q->field($row, 'hdate'),
				'section_body' => $section_body,
				'subsection_body' => $subsection_body,
				'question' => $q->field($row, 'body'),
				'answer' => $answer,
				'gid' => $q->field($row, 'gid')
			);
		}
		$info = array(
			'page' => $page,
			'results_per_page' => $limit,
			'total_results' => $total_results
		);
		return array('data'=>$data, 'info'=>$info);
	}

	function _get_data_by_recent_wrans ($args=array()) {
		// $args['days'] is the number of days back to look for biggest debates.
		// (1 by default)
		// $args['num'] is the number of links to return (1 by default).

		$data = array();

		// Get the most recent day on which we have wrans.
		$recentday = $this->most_recent_day();
		if (!count($recentday))
			return array();
		
		if (!isset($args['days']) || !is_numeric($args['days'])) {
			$args['days'] = 1;
		}
		if (!isset($args['num']) || !is_numeric($args['num'])) {
			$args['num'] = 1;
		}

		if ($args['num'] == 1) {
			$datewhere = "h.hdate = '" . mysql_escape_string($recentday['hdate']) . "'";
		} else {
			$firstdate = gmdate('Y-m-d', $recentday['timestamp'] - (86400 * $args['days']));
			$datewhere = "h.hdate >= '" . mysql_escape_string($firstdate) . "'
						AND		h.hdate <= '" . mysql_escape_string($recentday['hdate']) . "'";
		}
	
	
		// Get a random selection of subsections in wrans.
		$q = $this->db->query("SELECT e.body,
								h.hdate,
								h.htype,
								h.gid,
								h.subsection_id,
								h.section_id,
								h.epobject_id
						FROM	hansard h, epobject e
						WHERE	h.major = '" . $this->major . "'
						AND		htype = '11'
						AND		section_id != 0
						AND		subsection_id = 0
						AND		$datewhere
						AND		h.epobject_id = e.epobject_id
						ORDER BY RAND()
						LIMIT 	" . mysql_escape_string($args['num']) . "
						");
						
		for ($row=0; $row<$q->rows; $row++) {
			// This array just used for getting further data about this debate.
			$item_data = array (
				'major'			=> $this->major,
				'gid'			=> fix_gid_from_db( $q->field($row, 'gid') ),
				'htype'			=> $q->field($row, 'htype'),
				'section_id'	=> $q->field($row, 'section_id'),
				'subsection_id'	=> $q->field($row, 'subsection_id'),
				'epobject_id'	=> $q->field($row, 'epobject_id')
			);
			
			$list_url 		= $this->_get_listurl( $item_data );
			$totalcomments	= $this->_get_comment_count_for_epobject( $item_data );	
			
			$body 			= $q->field($row, 'body');
			$hdate			= $q->field($row, 'hdate');	

			// Get the parent section for this item.
			$r = $this->db->query("SELECT e.body
							FROM	hansard h, epobject e
							WHERE	h.epobject_id = e.epobject_id
							AND		h.epobject_id = '" . $q->field($row, 'section_id') . "'
							");
			$parentbody = $r->field(0, 'body');
			
			// Get the question for this item.
			$r = $this->db->query("SELECT e.body,
									h.speaker_id, h.hdate
							FROM	hansard h, epobject e
							WHERE	h.epobject_id = e.epobject_id
							AND 	h.subsection_id = '" . $q->field($row, 'epobject_id') . "'
							ORDER BY hpos
							LIMIT 1
							");
			$childbody = $r->field(0, 'body');
			$speaker = $this->_get_speaker($r->field(0, 'speaker_id'), $r->field(0, 'hdate') );
			
			$data[] = array (
				'body'			=> $body,
				'hdate'			=> $hdate,
				'list_url'		=> $list_url,
				'totalcomments'	=> $totalcomments,
				'child'			=> array (
					'body'		=> $childbody,
					'speaker'	=> $speaker
				),
				'parent'		=> array (
					'body'		=> $parentbody
				)
			);
			
		
		}	

		$data = array (
			'info' => array(),
			'data' => $data
		);
		
		return $data;
	
	}
	
}

?>
