<?php

include_once INCLUDESPATH."easyparliament/searchengine.php";
include_once INCLUDESPATH."easyparliament/searchlog.php";
include_once INCLUDESPATH."easyparliament/calendar.php";

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

class RedirectException extends \Exception { }

class HANSARDLIST {
    // This will be used to cache information about speakers on this page
    // so we don't have to keep fetching the same data from the DB.
    public $speakers = array ();
    /*
    $this->speakers[ $person_id ] = array (
        "name" => $name,
        "constituency"	=> $constituency,
        "party"			=> $party,
        "person_id"	    => $person_id,
        "url"			=> "/member/?p=$person_id"
    );
    */

    // This will be used to cache mappings from epobject_id to gid,
    // so we don't have to continually fetch the same data in get_hansard_data().
    public $epobjectid_to_gid = array ();
    /*
    $this->epobjectid_to_gid[ $epobject_id ] => $gid;
    */

    # Similarly, cache bill lookups
    public $bill_lookup = array();

    // This is so we can tell what type of thing we're displaying from outside
    // the object. eg, so we know if we should be able to post comments to the
    // item. It will have a value set if we are displaying by 'gid' (not 'date').
    // Use htype() to access it.
    public $htype;


    // Reset to the relevant major ID in DEBATELIST or WRANSLIST
    public $major;


    // When we view a particular item, we set these to the epobject_id and gid
    // of the item so we can attach Trackbacks etc to it from outside.
    public $epobject_id;
    public $gid;


    // This will be set if $this->most_recent_day() is called. Just so we
    // don't need to call it and it's lengthy query again.
    public $most_recent_day;

    // This prefix is used to pick out unique things by type
    public $gidprefix;

    // These are used to specify the pages for each subclass
    public $listpage;
    public $commentspage;

    # Only used by StandingCommittee subclass
    public $bill_title;
    public $url;

    public $db;

    public function __construct() {
        $this->db = new ParlDB;
    }



    public function display ($view, $args=array(), $format='html') {

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

        if ($view == 'search' && (!defined('FRONT_END_SEARCH') || !FRONT_END_SEARCH))
            return false;

        $validviews = array ('calendar', 'date', 'gid', 'person', 'search', 'search_min', 'search_video', 'recent', 'recent_mostvotes', 'biggest_debates', 'recent_wrans', 'recent_wms', 'column', 'mp', 'bill', 'session', 'recent_debates', 'recent_pbc_debates', 'featured_gid');
        if (in_array($view, $validviews)) {

            // What function do we call for this view?
            $function = '_get_data_by_'.$view;
            // Get all the data that's to be rendered.
            $data = $this->$function($args);

        } else {
            // Don't have a valid $view.
            $PAGE->error_message ("You haven't specified a view type.");
            return false;
        }

        // Set the values of this page's headings depending on the data we've fetched.
        if (isset($PAGE) && isset($data['info'])) {
            $PAGE->set_hansard_headings($data['info']);
        }

        // Glossary $view_override (to avoid too much code duplication...)
        if (isset($args['view_override'])) {
            $view = $args['view_override'];
        }

        $return = $this->render($view, $data, $format);

        return $return;
    }



    public function render($view, $data, $format='html') {
        // Once we have the data that's to be rendered,
        // include the template.

        // No format, so don't use the template sets.
        if ($format == 'none') {
            return $data;
        }

        include (INCLUDESPATH."easyparliament/templates/$format/hansard_$view" . ".php");
        return true;

    }


    public function total_items() {
        // Returns number of items in debates or wrans, depending on which class this is,
        // DEBATELIST or WRANSLIST.

        $q = $this->db->query("SELECT COUNT(*) AS count FROM hansard WHERE major = :major", array(':major' => $this->major));

        return $q->field(0, 'count');
    }



    public function most_recent_day() {
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
                        WHERE	major = :major
                        ", array(':major' => $this->major));
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


    public function htype() {
        return $this->htype;
    }

    public function epobject_id() {
        return $this->epobject_id;
    }

    public function gid() {
        return $this->gid;
    }


    public function _get_section($itemdata) {
        // Pass it an array of data about an item and it will return an
        // array of data about the item's section heading.

        twfy_debug (get_class($this), "getting an item's section");

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



    public function _get_subsection($itemdata) {
        // Pass it an array of data about an item and it will return an
        // array of data about the item's subsection heading.

        twfy_debug (get_class($this), "getting an item's subsection");

        // What we return.
        $subsectiondata = array ();

        if ($itemdata['htype'] == '12' || $itemdata['htype'] == '13') {
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



    public function _get_nextprev_items($itemdata) {
        global $hansardmajors;

        // Pass it an array of item info, of a section/subsection, and this will return
        // data for the next/prev items.

        twfy_debug (get_class($this), "getting next/prev items");

        // What we return.
        $nextprevdata = array();

        $prev_item_id = false;
        $next_item_id = false;

        if ($itemdata['htype'] == '10' || $itemdata['htype'] == '11') {
            // Debate subsection or section - get the next one.
            if ($hansardmajors[$itemdata['major']]['type'] == 'other' && $hansardmajors[$itemdata['major']]['location'] == 'UK') {
                $where = 'htype = 11';
            } else {
                $where = "(htype = 10 OR htype = 11)";
            }
        } else {
            // Anything else in debates - get the next element that isn't
            // a subsection or section, and is within THIS subsection.
            $where = "subsection_id = '" . $itemdata['subsection_id'] . "' AND (htype != 10 AND htype != 11)";
        }

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

        // Now we're going to get the data for the next and prev items
        // that we will use to make the links on the page.

        // Previous item.
        if ($prev_item_id) {
            // We have a previous one to link to.
            $wherearr = array();
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
                        $title = $prevdata[0]['speaker']['name'];
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
            $wherearr = array();
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
                        $title = $nextdata[0]['speaker']['name'];
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

        if ($this->major == 6) {
            $URL = new URL('pbc_bill');
            $URL->remove(array('bill'));
            $nextprevdata['up'] = array(
                'body'	=> _htmlspecialchars($this->bill_title),
                'title'	=> '',
                'url'	=> $URL->generate() . $this->url,
            );
        } elseif ($itemdata['htype'] == '10' || $itemdata['htype'] == '11') {
            $URL = new URL($this->listpage);
            // Create URL for this (sub)section's date.
            $URL->insert(array('d' => $itemdata['hdate']));
            $URL->remove(array('id'));
            $things = $hansardmajors[$itemdata['major']]['title'];
            $nextprevdata['up'] = array(
                'body'	=> "All $things on " . format_date($itemdata['hdate'], SHORTDATEFORMAT),
                'title'	=> '',
                'url' 	=> $URL->generate()
            );
        } else {
            // We'll be setting $nextprevdata['up'] within $this->get_data_by_gid()
            // because we need to know the name and url of the parent item, which
            // we don't have here. Life sucks.
        }

        return $nextprevdata;
    }


    public function _get_nextprev_dates($date) {
        global $hansardmajors;
        // Pass it a yyyy-mm-dd date and it'll return an array
        // containing the next/prev dates that contain items from
        // $this->major of hansard object.

        twfy_debug (get_class($this), "getting next/prev dates");

        // What we return.
        $nextprevdata = array ();

        $URL = new URL($this->listpage);

        $looper = array ("next", "prev");

        foreach ($looper as $n => $nextorprev) {

            $URL->reset();

            $params = array(':major' => $this->major,
                            ':date' => $date);
            if ($nextorprev == 'next') {
                $q = $this->db->query("SELECT MIN(hdate) AS hdate
                            FROM 	hansard
                            WHERE 	major = :major
                            AND		hdate > :date", $params);
            } else {
                $q = $this->db->query("SELECT MAX(hdate) AS hdate
                            FROM 	hansard
                            WHERE 	major = :major
                            AND		hdate < :date", $params);
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



    public function _validate_date($args) {
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
            $PAGE->error_message ("Sorry, '" . _htmlentities($date) . "' isn't of the right format (YYYY-MM-DD).");
            return false;
        }

        list(, $year, $month, $day) = $matches;

        if (!checkdate($month, $day, $year)) {
            $PAGE->error_message ("Sorry, '" . _htmlentities($date) . "' isn't a valid date.");
            return false;
        }

        $day = substr("0$day", -2);
        $month = substr("0$month", -2);
        $date = "$year-$month-$day";

        // Valid date!
        return $date;
    }



    public function _get_item($args) {
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

        twfy_debug (get_class($this), "looking for redirected gid");
        $gid = $this->gidprefix . $args['gid'];
        $q = $this->db->query ("SELECT gid_to FROM gidredirect WHERE gid_from = :gid", array(':gid' => $gid));
        if ($q->rows() == 0) {
            $itemdata = $this->_get_hansard_data($input);
        } else {
            do {
                $gid = $q->field(0, 'gid_to');
                $q = $this->db->query("SELECT gid_to FROM gidredirect WHERE gid_from = :gid", array(':gid' => $gid));
            } while ($q->rows() > 0);
            twfy_debug (get_class($this), "found redirected gid $gid" );
            $input['where'] = array('gid=' => $gid);
            $itemdata = $this->_get_hansard_data($input);
            if (count($itemdata) > 0 ) {
                throw new RedirectException(fix_gid_from_db($gid));
            }
        }

        if (count($itemdata) > 0) {
            $itemdata = $itemdata[0];
        }

        if (count($itemdata) == 0) {
            /* Deal with old links to some Lords pages. Somewhere. I can't remember where */
            $this->check_gid_change($args['gid'], 'a', '');

            if (substr($args['gid'], -1) == 'L') {
                $letts = array('a','b','c','d','e');
                for ($i=0; $i<4; $i++) {
                    $this->check_gid_change($args['gid'], $letts[$i], $letts[$i+1]);
                }
            }

            /* A lot of written answers were moved from 10th to 11th May and 11th May to 12th May.
               Deal with the bots who have stored links to those now non-existant written answers. */
            /* 2007-05-31: And then they were moved BACK in the volume edition, ARGH */
            $this->check_gid_change($args['gid'], '2006-05-10a', '2006-05-10c');
            $this->check_gid_change($args['gid'], '2006-05-10a', '2006-05-11d');
            $this->check_gid_change($args['gid'], '2006-05-11b', '2006-05-11d');
            $this->check_gid_change($args['gid'], '2006-05-11b', '2006-05-12c');
            $this->check_gid_change($args['gid'], '2006-05-11c', '2006-05-10c');
            $this->check_gid_change($args['gid'], '2006-05-12b', '2006-05-11d');

            $this->check_gid_change($args['gid'], '2007-01-08', '2007-01-05');
            $this->check_gid_change($args['gid'], '2007-02-19', '2007-02-16');

            /* More movearounds... */
            $this->check_gid_change($args['gid'], '2005-10-10d', '2005-09-12a');
            $this->check_gid_change($args['gid'], '2005-10-14a', '2005-10-13b');
            $this->check_gid_change($args['gid'], '2005-10-18b', '2005-10-10e');
            $this->check_gid_change($args['gid'], '2005-11-17b', '2005-11-15c');

            $this->check_gid_change($args['gid'], '2007-01-08a', '2007-01-08e');

            /* Right back when Lords began, we sent out email alerts when they weren't on the site. So this was to work that. */
            #$lord_gid_like = 'uk.org.publicwhip/lords/' . $args['gid'] . '%';
            #$q = $this->db->query('SELECT source_url FROM hansard WHERE gid LIKE :lord_gid_like', array(':lord_gid_like' => $lord_gid_like));
            #$u = '';
            #if ($q->rows()) {
            #	$u = $q->field(0, 'source_url');
            #	$u = '<br><a href="'. $u . '">' . $u . '</a>';
            #}
            $PAGE->error_message ("Sorry, there is no Hansard object with a gid of '" . _htmlentities($args['gid']) . "'.");
            return false;
        }

        return $itemdata;

    }

    private function check_gid_change($gid, $from, $to) {
        $input = array (
            'amount' => array (
                'body' => true,
                'speaker' => true,
                'comment' => true,
                'votes' => true
            )
        );
        if (strstr($gid, $from)) {
            $check_gid = str_replace($from, $to, $gid);
            $input['where'] = array('gid=' => $this->gidprefix . $check_gid);
            $itemdata = $this->_get_hansard_data($input);
            if (count($itemdata) > 0) {
                throw new RedirectException($check_gid);
            }
        }
    }


    public function _get_data_by_date($args) {
        // For displaying the section and subsection headings as
        // links for an entire day of debates/wrans.

        global $DATA, $this_page;

        twfy_debug (get_class($this), "getting data by date");

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

                $num_sections = count($sections);
                for ($n=0; $n<$num_sections; $n++) {
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


    public function _get_data_by_recent($args) {
        // Like _get_data_by_id() and _get_data_by_date()
        // this returns a $data array suitable for sending to a template.
        // It lists recent dates with debates/wrans on them, with links.

        $params = array();

        if (isset($args['days']) && is_numeric($args['days'])) {
            $limit = 'LIMIT :limit';
            $params[':limit'] = $args['days'];
        } else {
            $limit = '';
        }

        if ($this->major != '') {
            // We must be in DEBATELIST or WRANSLIST.

            $major = 'WHERE major = :major';
            $params[':major'] = $this->major;
        } else {
            $major = '';
        }

        $data = array ();

        $q = $this->db->query ("SELECT DISTINCT(hdate)
                        FROM 	hansard
                        $major
                        ORDER BY hdate DESC
                        $limit
                        ", $params);

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

    # Display a person's most recent debates.
    # Only used by MP RSS generator now, MP pages use Xapian search
    # XXX: Abolish this entirely?

    public function _get_data_by_person($args) {
        global $PAGE, $hansardmajors;
        $items_to_list = isset($args['max']) ? $args['max'] : 20;

        // Where we'll put all the data we want to render.
        $data = array();

        if (!isset($args['person_id'])) {
            $PAGE->error_message ("Sorry, we need a valid person ID.");
            return $data;
        }

        $params = array();

        $where = 'hansard.person_id = :person_id';
        $params[':person_id'] = trim($args['person_id']);

        if (isset($this->major)) {
            $majorwhere = "AND hansard.major = :hansard_major ";
            $params[':hansard_major'] = $this->major;
        } else {
            // We're getting results for all debates/wrans/etc.
            $majorwhere = '';
        }

        $q = $this->db->query("SELECT hansard.subsection_id, hansard.section_id,
                    hansard.htype, hansard.gid, hansard.major, hansard.minor,
                    hansard.hdate, hansard.htime, hansard.person_id,
                    epobject.body, epobject_section.body AS body_section,
                    epobject_subsection.body AS body_subsection,
                                    hansard_subsection.gid AS gid_subsection
                FROM hansard
                JOIN epobject
                    ON hansard.epobject_id = epobject.epobject_id
                JOIN epobject AS epobject_section
                                    ON hansard.section_id = epobject_section.epobject_id
                JOIN epobject AS epobject_subsection
                                    ON hansard.subsection_id = epobject_subsection.epobject_id
                JOIN hansard AS hansard_subsection
                                    ON hansard.subsection_id = hansard_subsection.epobject_id
                        WHERE	$where $majorwhere
                        ORDER BY hansard.hdate DESC, hansard.hpos DESC
                        LIMIT	$items_to_list
                        ", $params);


        $speeches = array();
        if ($q->rows() > 0) {
            for ($n=0; $n<$q->rows(); $n++) {
                $speech = array (
                    'subsection_id'	=> $q->field($n, 'subsection_id'),
                    'section_id'	=> $q->field($n, 'section_id'),
                    'htype'		=> $q->field($n, 'htype'),
                    'major'		=> $q->field($n, 'major'),
                    'minor'		=> $q->field($n, 'minor'),
                    'hdate'		=> $q->field($n, 'hdate'),
                    'htime'		=> $q->field($n, 'htime'),
                    'person_id'	=> $q->field($n, 'person_id'),
                    'body'		=> $q->field($n, 'body'),
                    'body_section'  => $q->field($n, 'body_section'),
                    'body_subsection' => $q->field($n, 'body_subsection'),
                    'gid'		=> fix_gid_from_db($q->field($n, 'gid')),
                );
                // Cache parent id to speed up _get_listurl
                $this->epobjectid_to_gid[$q->field($n, 'subsection_id') ] = fix_gid_from_db( $q->field($n, 'gid_subsection') );

                $url_args = array ('p'=>$q->field($n, 'person_id'));
                $speech['listurl'] = $this->_get_listurl($speech, $url_args);
                $speeches[] = $speech;
            }
        }

        if (count($speeches) > 0) {
            // Get the subsection texts.
            $num_speeches = count($speeches);
            for ($n=0; $n<$num_speeches; $n++) {
                $thing = $hansardmajors[$speeches[$n]['major']]['title'];
                // Add the parent's body on...
                $speeches[$n]['parent']['body'] = $speeches[$n]['body_section'] . ' | ' . $thing;
                if ($speeches[$n]['subsection_id'] != $speeches[$n]['section_id']) {
                    $speeches[$n]['parent']['body'] = $speeches[$n]['body_subsection'] .
                        ' | ' . $speeches[$n]['parent']['body'];
                }
            }
            $data['rows'] = $speeches;
        } else {
            $data['rows'] = array();
        }
        return $data;
    }


    public function _get_data_by_search_min($args) {
        return $this->_get_data_by_search($args);
    }
    public function _get_data_by_search_video($args) {
        return $this->_get_data_by_search($args);
    }
    public function _get_data_by_search($args) {

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
            if ( isset($args['exceptions']) ) {
                throw new \Exception('No search string provided.');
            } else {
                $PAGE->error_message("No search string");
                return false;
            }
        }

        // What we'll return.
        $data = array ();

        $data['info']['s'] = $args['s'];

        // Allows us to specify how many results we want
        // Mainly for glossary term adding
        if (isset($args['num']) && $args['num']) {
            $results_per_page = $args['num']+0;
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

        // For Xapian's equivalent of an SQL LIMIT clause.
        $first_result = ($page-1) * $results_per_page;
        $data['info']['first_result'] = $first_result + 1; // Take account of LIMIT's 0 base.

        // Get the gids from Xapian
        $sort_order = 'date';
        if (isset($args['o'])) {
            if ($args['o']=='d') $sort_order = 'newest';
            if ($args['o']=='o') $sort_order = 'oldest';
            elseif ($args['o']=='c') $sort_order = 'created';
            elseif ($args['o']=='r') $sort_order = 'relevance';
        }

        $data['searchdescription'] = $SEARCHENGINE->query_description_long();
        $count = $SEARCHENGINE->run_count($first_result, $results_per_page, $sort_order);
        $data['info']['total_results'] = $count;
        $data['info']['spelling_correction'] = $SEARCHENGINE->get_spelling_correction();

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

        // Cycle through each result, munge the data, get more, and put it all in $data.
        $num_gids = count($gids);
        for ($n=0; $n<$num_gids; $n++) {
            $gid = $gids[$n];
            $relevancy = $relevances[$n];
            $collapsed = $SEARCHENGINE->collapsed[$n];
            if ($sort_order=='created') {
                #$created = substr($createds[$n], 0, strpos($createds[$n], ':'));
                if ($createds[$n]<$args['threshold']) {
                    $data['info']['total_results'] = $n;
                    break;
                }
            }

            if (strstr($gid, 'calendar')) {
                $id = fix_gid_from_db($gid);

                $q = $this->db->query("SELECT *, event_date as hdate, pos as hpos
                    FROM future
                    LEFT JOIN future_people ON id=calendar_id AND witness=0
                    WHERE id = $id AND deleted=0");
                if ($q->rows() == 0) continue;

                $itemdata = $q->row(0);

                # Ignore past events in places that we cover (we'll have the data from Hansard)
                if ($itemdata['event_date'] < date('Y-m-d') &&
                    in_array($itemdata['chamber'], array(
                        'Commons: Main Chamber', 'Lords: Main Chamber',
                        'Commons: Westminster Hall',
                    )))
                        continue;

                list($cal_item, $cal_meta) = calendar_meta($itemdata);
                $body = $this->prepare_search_result_for_display($cal_item) . '.';
                if (!empty($cal_meta)) {
                    $body .= ' <span class="future_meta">' . join('; ', $cal_meta) . '</span>';
                }
                if ($itemdata['witnesses']) {
                    $body .= '<br><small>Witnesses: '
                        . $this->prepare_search_result_for_display($itemdata['witnesses'])
                        . '</small>';
                }

                if ($itemdata['event_date'] >= date('Y-m-d')) {
                    $title = 'Upcoming Business';
                } else {
                    $title = 'Previous Business';
                }
                $itemdata['gid']            = $id;
                $itemdata['relevance']      = $relevancy;
                $itemdata['parent']['body'] = $title . ' &#8211; ' . $itemdata['chamber'];
                $itemdata['extract']        = $body;
                $itemdata['listurl']        = '/calendar/?d=' . $itemdata['event_date'] . '#cal' . $itemdata['id'];
                $itemdata['major']          = 'F';

            } else {

                // Get the data for the gid from the database
                $q = $this->db->query("SELECT hansard.gid, hansard.hdate,
                    hansard.htime, hansard.section_id, hansard.subsection_id,
                    hansard.htype, hansard.major, hansard.minor,
                    hansard.person_id, hansard.hpos, hansard.video_status,
                    epobject.epobject_id, epobject.body
                FROM hansard, epobject
                WHERE hansard.gid = :gid
                    AND hansard.epobject_id = epobject.epobject_id"
                , array(':gid' => $gid));

                if ($q->rows() > 1) {
                    if ( $isset($args['exceptions']) ) {
                        throw new \Exception("Got more than one row getting data for $gid.");
                    } else {
                        $PAGE->error_message("Got more than one row getting data for $gid");
                    }
                }
                if ($q->rows() == 0) {
                    # This error message is totally spurious, so don't show it
                    # $PAGE->error_message("Unexpected missing gid $gid while searching");
                    continue;
                }

                $itemdata = $q->row(0);
                $itemdata['collapsed']  = $collapsed;
                $itemdata['gid']        = fix_gid_from_db( $q->field(0, 'gid') );
                $itemdata['relevance']  = $relevancy;
                $itemdata['extract']    = $this->prepare_search_result_for_display($q->field(0, 'body'));

                //////////////////////////
                // 2. Create the URL to link to this bit of text.

                $id_data = array (
                    'major'            => $itemdata['major'],
                    'minor'            => $itemdata['minor'],
                    'htype'         => $itemdata['htype'],
                    'gid'             => $itemdata['gid'],
                    'section_id'    => $itemdata['section_id'],
                    'subsection_id'    => $itemdata['subsection_id']
                );

                // We append the query onto the end of the URL as variable 's'
                // so we can highlight them on the debate/wrans list page.
                $url_args = array ('s' => $searchstring);

                $itemdata['listurl'] = $this->_get_listurl($id_data, $url_args, $encode);

                //////////////////////////
                // 3. Get the speaker for this item, if applicable.
                if ($itemdata['person_id'] != 0) {
                    $itemdata['speaker'] = $this->_get_speaker($itemdata['person_id'], $itemdata['hdate'], $itemdata['htime'], $itemdata['major']);
                }

                //////////////////////////
                // 4. Get data about the parent (sub)section.
                if ($itemdata['major'] && $hansardmajors[$itemdata['major']]['type'] == 'debate') {
                    // Debate
                    if ($itemdata['htype'] != 10) {
                        $section = $this->_get_section($itemdata);
                        $itemdata['parent']['body'] = $section['body'];
#                        $itemdata['parent']['listurl'] = $section['listurl'];
                        if ($itemdata['section_id'] != $itemdata['subsection_id']) {
                            $subsection = $this->_get_subsection($itemdata);
                            $itemdata['parent']['body'] .= ': ' . $subsection['body'];
#                            $itemdata['parent']['listurl'] = $subsection['listurl'];
                        }
                        if ($itemdata['major'] == 5) {
                            $itemdata['parent']['body'] = 'Northern Ireland Assembly: ' . $itemdata['parent']['body'];
                        } elseif ($itemdata['major'] == 6) {
                            $itemdata['parent']['body'] = 'Public Bill Committee: ' . $itemdata['parent']['body'];
                        } elseif ($itemdata['major'] == 7) {
                            $itemdata['parent']['body'] = 'Scottish Parliament: ' . $itemdata['parent']['body'];
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
                    $body = $hansardmajors[$itemdata['major']]['title'] . ' &#8212; ';
                    if (isset($section['body'])) $body .= $section['body'];
                    if (isset($subsection['body'])) $body .= ': ' . $subsection['body'];
                    if (isset($subsection['listurl'])) $listurl = $subsection['listurl'];
                    else $listurl = '';
                    $itemdata['parent'] = array (
                        'body' => $body,
                        'listurl' => $listurl
                    );
                    if ($itemdata['htype'] == 11) {
                        # Search result was a subsection heading; fetch the first entry
                        # from the wrans/wms to show under the heading
                        $input = array (
                            'amount' => array(
                                'body' => true,
                                'speaker' => true
                            ),
                            'where' => array(
                                'hansard.subsection_id=' => $itemdata['epobject_id']
                            ),
                            'order' => 'hpos ASC',
                            'limit' => 1
                        );
                        $ddata = $this->_get_hansard_data($input);
                        if (count($ddata)) {
                            $itemdata['body'] = $ddata[0]['body'];
                            $itemdata['extract'] = $this->prepare_search_result_for_display($ddata[0]['body']);
                            $itemdata['person_id'] = $ddata[0]['person_id'];
                            if ($itemdata['person_id']) {
                                $itemdata['speaker'] = $this->_get_speaker($itemdata['person_id'], $itemdata['hdate'], $itemdata['htime'], $itemdata['major']);
                            }
                        }
                    } elseif ($itemdata['htype'] == 10) {
                        $itemdata['body'] = '';
                        $itemdata['extract'] = '';
                    }
                }

            } // End of handling non-calendar search result

            $rows[] = $itemdata;
        }

        $data['rows'] = $rows;
        return $data;
    }

    public function prepare_search_result_for_display($body) {
        global $SEARCHENGINE;
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

        return $extract;
    }

    public function _get_data_by_calendar($args) {
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

        if ($action == 'recentmonths' || $action == 'recentyear') {

            // We're either getting the most recent $args['months'] data
            // Or the most recent year's data.
            // (Not necessarily recent to *now* but compared to the most
            // recent date for which we have relevant hansard data.)
            // 'recentyear' will include all the months that haven't happened yet.

            // Find the most recent date we have data for.
            $q = $this->db->query("SELECT MAX(hdate) AS hdate
                            FROM	hansard
                            WHERE	major = :major",
                array(':major' => $this->major));

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

            $params = array(
                ':firstdate' => $firstyear . '-' . $firstmonth . '-01',
                ':finaldate' => $finalyear . '-' . $finalmonth . '-31');

            // Check there are some dates for this year/month.
            $q = $this->db->query("SELECT epobject_id
                            FROM	hansard
                            WHERE	hdate >= :firstdate
                            AND 	hdate <= :finaldate
                            LIMIT 	1
                            ", $params);

            if ($q->rows() == 0) {
                // No data in db, so return empty array!
                return $data;
            }

        }

        // OK, Now we have $firstyear, $firstmonth, $finalyear, $finalmonth set up.

        // Get the data...

        $where = '';
        $params = array();

        if ($finalyear > $firstyear || $finalmonth >= $firstmonth) {
            $params[':finaldate'] = $finalyear . '-' . $finalmonth . '-31';
            $where = 'AND hdate <= :finaldate';
        }

        $params[':major'] = $this->major;
        $params[':firstdate'] = $firstyear . '-' . $firstmonth . '-01';
        $q =  $this->db->query("SELECT 	DISTINCT(hdate) AS hdate
                        FROM		hansard
                        WHERE		major = :major
                        AND			hdate >= :firstdate
                        $where
                        ORDER BY	hdate ASC
                        ", $params);

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

        if (substr($this_page, -4) == 'year') {
            // Only need next/prev on these pages.
            // Not sure this is the best place for this, but...

            $nextprev = array();

            if ($action == 'recentyear') {
                // Assuming there will be a previous year!

                $YEARURL->insert(array('y'=> $firstyear-1));

                $nextprev['prev'] = array (
                    'body' => 'Previous year',
                    'title' => $firstyear - 1,
                    'url' => $YEARURL->generate()
                );

            } else { // action is 'year'.

                $nextprev['prev'] = array ('body' => 'Previous year');
                $nextprev['next'] = array ('body' => 'Next year');

                $q = $this->db->query("SELECT DATE_FORMAT(hdate, '%Y') AS year
                            FROM hansard WHERE major = :major
                            AND year(hdate) < :firstyear
                            ORDER BY hdate DESC
                            LIMIT 1", array(
                                ':major' => $this->major,
                                ':firstyear' => $firstyear
                            ));

                $prevyear = $q->field(0, 'year');
                $q = $this->db->query("SELECT DATE_FORMAT(hdate, '%Y') AS year
                            FROM hansard WHERE major = :major
                            AND year(hdate) > :finalyear
                            ORDER BY hdate
                            LIMIT 1", array(
                                ':major' => $this->major,
                                ':finalyear' => $finalyear
                            ));
                $nextyear = $q->field(0, 'year');

                if ($action == 'year' && $prevyear) {
                    $YEARURL->insert(array('y'=>$prevyear));
                    $nextprev['prev']['title'] = $prevyear;
                    $nextprev['prev']['url'] = $YEARURL->generate();
                }
                if ($nextyear) {
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

    public function _get_mentions($spid) {
        $result = array();
        $q = $this->db->query("select gid, type, date, url, mentioned_gid
            from mentions where gid like 'uk.org.publicwhip/spq/$spid'
            order by date, type");
        $nrows = $q->rows();
        for ($i=0; $i < $nrows; $i++) {
            $result[$i] = $q->row($i);
        }
        return $result;
    }

    protected function _get_hansard_data($input) {
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


        // The fields to fetch from db. 'table' => array ('field1', 'field2').
        $fieldsarr = array (
            'hansard' => array ('epobject_id', 'htype', 'gid', 'hpos', 'section_id', 'subsection_id', 'hdate', 'htime', 'source_url', 'major', 'minor', 'video_status', 'colnum')
        );

        $params = array();

        if (isset($amount['speaker']) && $amount['speaker'] == true) {
            $fieldsarr['hansard'][] = 'person_id';
        }

        if ((isset($amount['body']) && $amount['body'] == true) ||
            (isset($amount['comment']) && $amount['comment'] == true)
            ) {
            $fieldsarr['epobject'] = array ('body');
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
        $i = 0;
        foreach ($wherearr as $key => $val) {
            $params[":where$i"] = $val;
            $wherearr2[] = "$key :where$i";
            $i++;
        }
        $where = implode (" AND ", $wherearr2);


        if ($order != '') {
            # You can't use parameters for order by clauses
            $order_by_clause = "ORDER BY $order";
        } else {
            $order_by_clause = '';
        }

        if ($limit != '') {
            $params[':limit'] = $limit;
            $limit = "LIMIT :limit";
        } else {
            $limit = '';
        }

        // Finally, do the query!
        $q = $this->db->query ("SELECT $fields
                        FROM 	hansard
                        $join
                        WHERE $where
                        $order_by_clause
                        $limit
                        ", $params);

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

                // Add mentions if (a) it's a question in the written
                // answer section or (b) it's in the official reports
                // and the body text ends in a bracketed SPID.
                if (($this->major && $hansardmajors[$this->major]['page']=='spwrans') && ($item['htype'] == '12' && $item['minor'] == '1')) {
                    // Get out the SPID:
                    if ( preg_match('#\d{4}-\d\d-\d\d\.(.*?)\.q#', $item['gid'], $m) ) {
                        $item['mentions'] = $this->_get_mentions($m[1]);
                    }
                }

                // The second case (b):
                if (($this->major && $hansardmajors[$this->major]['page']=='spdebates') && isset($item['body'])) {
                    $stripped_body = preg_replace('/<[^>]+>/ms','',$item['body']);
                    if ( preg_match('/\((S\d+\w+-\d+)\)/ms',$stripped_body,$m) ) {
                        $item['mentions'] = $this->_get_mentions($m[1]);
                    }
                }

                if ($item['epobject_id'] == 15674958 || $item['epobject_id'] == 15674959) {
                    global $DATA, $this_page;
                    $DATA->set_page_metadata($this_page, 'robots', 'noindex');
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
                                    AND htype = 12
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
                    $params = array(':epobject_id' => $item['epobject_id']);
                    if ($item['htype'] == '10') {
                        $where = 'hansard.section_id = :epobject_id
                            AND hansard.subsection_id = :epobject_id';
                    } elseif ($item['htype'] == '11') {
                        $where = 'hansard.subsection_id = :epobject_id';
                    }

                    $r = $this->db->query("SELECT epobject.body
                                    FROM 	hansard,
                                            epobject
                                    WHERE	$where
                                    AND		hansard.epobject_id = epobject.epobject_id
                                    ORDER BY hansard.hpos ASC
                                    LIMIT	1", $params);

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
                    'minor' 		=> $item['minor'],
                    'htype' 		=> $item['htype'],
                    'gid' 			=> $item['gid'],
                    'section_id'	=> $item['section_id'],
                    'subsection_id'	=> $item['subsection_id']
                );


                $item['listurl'] = $this->_get_listurl($item_data);


                // Create a URL for where we can see all the comments for this item.
                if (isset($this->commentspage)) {
                    $COMMENTSURL = new URL($this->commentspage);
                    if ($this->major == 6) {
                        # Another hack...
                        $COMMENTSURL->remove(array('id'));
                        $id = preg_replace('#^.*?_.*?_#', '', $item['gid']);
                        $fragment = $this->url . $id;
                        $item['commentsurl'] = $COMMENTSURL->generate() . $fragment;
                    } else {
                        $COMMENTSURL->insert(array('id' => $item['gid']));
                        $item['commentsurl'] = $COMMENTSURL->generate();
                    }
                }

                // Get the user/anon votes items that have them.
                if (($this->major == 3 || $this->major == 8) && (isset($amount['votes']) && $amount['votes'] == true) &&
                    $item['htype'] == '12') {
                    // Debate speech or written answers (not questions).

                    $item['votes'] = $this->_get_votes( $item['epobject_id'] );
                }

                // Get the speaker for this item, if applicable.
                if ( (isset($amount['speaker']) && $amount['speaker'] == true) &&
                    $item['person_id'] != '') {

                    $item['speaker'] = $this->_get_speaker($item['person_id'], $item['hdate'], $item['htime'], $item['major']);
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


    public function _get_votes($epobject_id) {
        // Called from _get_hansard_data().
        // Separated out here just for clarity.
        // Returns an array of user and anon yes/no votes for an epobject.

        $votes = array();

        // YES user votes.
        $q = $this->db->query("SELECT COUNT(vote) as totalvotes
                        FROM	uservotes
                        WHERE	epobject_id = :epobject_id
                        AND 	vote = '1'
                        GROUP BY epobject_id", array(':epobject_id' => $epobject_id));

        if ($q->rows() > 0) {
            $votes['user']['yes'] = $q->field(0, 'totalvotes');
        } else {
            $votes['user']['yes'] = '0';
        }

        // NO user votes.
        $q = $this->db->query("SELECT COUNT(vote) as totalvotes
                        FROM	uservotes
                        WHERE	epobject_id = :epobject_id
                        AND 	vote = '0'
                        GROUP BY epobject_id", array(':epobject_id' => $epobject_id));

        if ($q->rows() > 0) {
            $votes['user']['no'] = $q->field(0, 'totalvotes');
        } else {
            $votes['user']['no'] = '0';
        }


        // Get the anon votes for each item.

        $q = $this->db->query("SELECT yes_votes,
                                no_votes
                        FROM	anonvotes
                        WHERE	epobject_id = :epobject_id",
            array(':epobject_id' => $epobject_id));

        if ($q->rows() > 0) {
            $votes['anon']['yes'] = $q->field(0, 'yes_votes');
            $votes['anon']['no'] = $q->field(0, 'no_votes');
        } else {
            $votes['anon']['yes'] = '0';
            $votes['anon']['no'] = '0';
        }

        return $votes;
    }


    public function _get_listurl ($id_data, $url_args=array(), $encode='html') {
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

        if ($id_data['htype'] == '11' || $id_data['htype'] == '10') {
            if ($this->major == 6) {
                $id = preg_replace('#^.*?_.*?_#', '', $id_data['gid']);
                global $DATA;
                $DATA->set_page_metadata('pbc_clause', 'url', 'pbc/' . $this->url . $id);
                $LISTURL->remove(array('id'));
            } else {
                $LISTURL->insert( array( 'id' => $id_data['gid'] ) );
            }
        } else {
            // A debate speech or question/answer, etc.
            // We need to get the gid of the parent (sub)section for this item.
            // We use this with the gid of the item itself as an #anchor.

            $parent_epobject_id = $id_data['subsection_id'];
            $minor = $id_data['minor'];

            // Find the gid of this item's (sub)section.
            $parent_gid = '';

            if (isset($this->epobjectid_to_gid[ $parent_epobject_id ])) {
                // We've previously cached the gid for this epobject_id, so use that.

                $parent_gid = $this->epobjectid_to_gid[ $parent_epobject_id ];

            } else {
                // We haven't cached the gid, so fetch from db.

                $r = $this->db->query("SELECT gid
                                FROM 	hansard
                                WHERE	epobject_id = :epobject_id",
                    array(':epobject_id' => $parent_epobject_id));

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
                if ($id_data['major'] == 6) {
                    if (isset($this->bill_lookup[$minor])) {
                        list($title, $session) = $this->bill_lookup[$minor];
                    } else {
                        $qq = $this->db->query('select title, session from bills where id='.$minor);
                        $title = $qq->field(0, 'title');
                        $session = $qq->field(0, 'session');
                        $this->bill_lookup[$minor] = array($title, $session);
                    }
                    $url = "$session/" . urlencode(str_replace(' ','_',$title));
                    $parent_gid = preg_replace('#^.*?_.*?_#', '', $parent_gid);
                    global $DATA;
                    $DATA->set_page_metadata('pbc_clause', 'url', "pbc/$url/$parent_gid");
                    $LISTURL->remove(array('id'));
                } else {
                    $LISTURL->insert( array( 'id' => $parent_gid ) );
                }
                // Use a truncated form of this item's gid for the anchor.
                $fragment = '#g' . gid_to_anchor($id_data['gid']);
            }
        }

        if (count($url_args) > 0) {
            $LISTURL->insert( $url_args);
        }

        return $LISTURL->generate($encode) . $fragment;
    }

    private $major_to_house = array(
        1 => array(1),
        2 => array(1),
        3 => array(1, 2),
        4 => array(1, 2),
        5 => array(3),
        6 => array(1),
        7 => array(4),
        8 => array(4),
        101 => array(2),
    );

    public function _get_speaker($person_id, $hdate, $htime, $major) {
        if ($person_id == 0) {
            return array();
        }

        # Special exemption below for when NI Speaker becomes Speaker
        # mid-debate, so we don't want to cache
        if (isset($this->speakers["$person_id-$hdate"]) && !($person_id == 13831 && $hdate == '2015-01-12')) {
            return $this->speakers["$person_id-$hdate"];
        }

        # Special exemptions for people 'speaking' after they have died
        # Note identical code to this in search/index.pl
        if ($person_id == 10170 && $hdate == '2014-09-08') {
            $hdate = '2014-09-07';
        }
        if ($person_id == 11068 && substr($hdate, 0, 7) == '2008-09') {
            $hdate = '2008-08-13';
        }

        $q = $this->db->query("SELECT title, given_name, family_name, lordofname,
                                house,
                                constituency,
                                party,
                                member_id
                        FROM    member m, person_names p
                        WHERE	m.person_id = :person_id
                            AND entered_house <= :hdate AND :hdate <= left_house
                            AND p.person_id = m.person_id AND p.type = 'name'
                            AND p.start_date <= :hdate AND :hdate <= p.end_date
                        ORDER BY entered_house",
            array(':person_id' => $person_id, ':hdate' => $hdate));
        $member = $this->_get_speaker_alone($q->data, $person_id, $hdate, $htime, $major);

        $URL = $this->_get_speaker_url($member['house']);
        $URL->insert( array ('p' => $person_id) );

        $name = member_full_name($member['house'], $member['title'], $member['given_name'], $member['family_name'], $member['lordofname']);
        $speaker = array (
            'member_id' => $member['member_id'],
            "name" => $name,
            'house' => $member['house'],
            "constituency" => $member["constituency"],
            "party" => $member["party"],
            "person_id" => $person_id,
            "url" => $URL->generate(),
        );

        global $parties;
        // Manual fix for Speakers.
        if (isset($parties[$speaker['party']])) {
            $speaker['party'] = $parties[$speaker['party']];
        }

        $speaker['office'] = $this->_get_speaker_offices($speaker, $hdate);
        $this->speakers["$person_id-$hdate"] = $speaker;
        return $speaker;
    }

    private function _get_speaker_alone($members, $person_id, $hdate, $htime, $major) {
        if (count($members) > 1) {
            $members = array_filter($members, function($m) use ($major) {
                return in_array($m['house'], $this->major_to_house[$major]);
            });
            # Of course, remember PHP treats lists as dictionaries
            $members = array_values($members);
        }
        # Note identical code to this in search/index.pl
        if (count($members) > 1) {
            # Couple of special cases for the election of the NI Speaker
            if ($person_id == 13799 && $hdate == '2007-05-08') {
                $members = array($members[$htime < '11:00' ? 0 : 1]);
            } elseif ($person_id == 13831 && $hdate == '2015-01-12') {
                $members = array($members[$htime < '13:00' ? 0 : 1]);
            }
        }
        if (count($members) != 1) {
            throw new \Exception('Wanted one result, but got ' . count($members) . " for $person_id, $hdate, $major.");
        }

        return $members[0];
    }

    private function _get_speaker_url($house) {
        $URL = new URL('mp'); # Default, house=1
        if ($house==2) {
            $URL = new URL('peer');
        } elseif ($house==3) {
            $URL = new URL('mla');
        } elseif ($house==4) {
            $URL = new URL('msp');
        } elseif ($house==0) {
            $URL = new URL('royal');
        }
        return $URL;
    }

    private function _get_speaker_offices($speaker, $hdate) {
        $offices = array();
        $q = $this->db->query("SELECT dept, position, source FROM moffice
            WHERE person=:person_id
            AND from_date <= :hdate and :hdate <= to_date",
            array(':person_id' => $speaker['person_id'], ':hdate' => $hdate));
        $rows = $q->rows();
        if (!$rows) {
            return $offices;
        }
        for ($row=0; $row<$rows; $row++) {
            $dept = $q->field($row, 'dept');
            $pos = $q->field($row, 'position');
            $source = $q->field($row, 'source');
            if ($source == 'chgpages/libdem' && $hdate > '2009-01-15') {
                continue;
            }
            if (!$pos || $pos == 'Chairman' || $pos == 'Member') {
                continue;
            }
            $offices[] = array(
                'dept' => $dept,
                'position' => $pos,
                'source' => $source,
                'pretty' => prettify_office($pos, $dept)
            );
        }
        return $offices;
    }

    public function _get_comment($item_data) {
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
                            WHERE	c.epobject_id = :epobject_id
                            AND		c.user_id = u.user_id
                            AND		c.visible = 1
                            ORDER BY c.posted ASC
                            LIMIT	1",
                    array(':epobject_id' => $item_data['epobject_id']));

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


    public function _get_comment_count_for_epobject($item_data) {
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
                    AND subsection_id = :epobject_id";

            if ($item_data['htype'] == '10') {
                // Section - get a count of comments within this section that
                // don't have a subsection heading.
                $where .= " AND section_id = :epobject_id";
            }

        } else {
            // Just getting a count of the comments on this item.
            $from = "comments";
            $where = 'epobject_id = :epobject_id';
        }

        $q = $this->db->query("SELECT COUNT(*) AS count
                        FROM 	$from
                        WHERE	$where
                        AND		visible = 1",
            array(':epobject_id' => $item_data['epobject_id']));

        return $q->field(0, 'count');
    }


/*
    public function _get_trackback_data($itemdata) {
        // Returns a array of data we need to create the Trackback Auto-discovery
        // RDF on a page.

        // We're assuming that we're only using this on a page where there's only
        // one 'thing' to be trackbacked to. ie, we don't add #anchor data onto the
        // end of the URL so we can include this RDF in full lists of debate speeches.

        $trackback = array();

        $title = '';

        if (isset($itemdata['speaker']) && isset($itemdata['speaker']['name'])) {
            // This is probably a debate speech.
            $title .= $itemdata['speaker']['name'] . ': ';
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
*/

    public function _get_data_by_gid($args) {

        // We need to get the data for this gid.
        // Then depending on what htype it is, we get the data for other items too.
        global $DATA, $this_page, $hansardmajors;

        twfy_debug (get_class($this), "getting data by gid");

        // Get the information about the item this URL refers to.
        $itemdata = $this->_get_item($args);
        if (!$itemdata) {
            return array();
        }

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
            twfy_debug (get_class($this), "instead of " . $args['gid'] . " selecting subheading gid " . $parent[0]['gid'] . " to get whole wrans");
            $args['gid'] = $parent[0]['gid'];
            $this->_get_item($args);
            throw new RedirectException($args['gid']);
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
            if (!empty($next)) {
                twfy_debug (get_class($this), 'instead of ' . $args['gid'] . ' moving to ' . $next[0]['gid']);
                $args['gid'] = $next[0]['gid'];
                $this->_get_item($args);
                throw new RedirectException($args['gid']);
            }
        }

        // Where we'll put all the data we want to render.
        $data = array();

        if (isset($itemdata['htype'])) {
            $this->htype = $itemdata['htype'];
            if ($this->htype >= 12) {
                $this_page = $this->commentspage;
            } else {
                $this_page = $this->listpage;
            }
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

        // Get the section and subsection headings for this item.
        $sectionrow = $this->_get_section($itemdata);
        $subsectionrow = $this->_get_subsection($itemdata);

        // Get the nextprev links for this item, to link to next/prev pages.
        // Duh.
        if ($itemdata['htype'] == '10') {
            $nextprev = $this->_get_nextprev_items( $sectionrow );
            $data['info']['text_heading'] = $itemdata['body'];

        } elseif ($itemdata['htype'] == '11') {
            $nextprev = $this->_get_nextprev_items( $subsectionrow );
            $data['info']['text_heading'] = $itemdata['body'];

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

            # $sectionrow['trackback'] = $this->_get_trackback_data($sectionrow);

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
                # If there's only one subheading, and nothing in the heading, redirect to it immediaetly
                if (count($data['subrows']) == 1) {
                    throw new RedirectException($data['subrows'][0]['gid']);
                }
            }
        } elseif ($itemdata['htype'] == '11') {
            // This item is a subsection, so we're displaying everything within it.

            # $subsectionrow['trackback'] = $this->_get_trackback_data($subsectionrow);

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

            # $itemdata['trackback'] = $this->_get_trackback_data($itemdata);

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

        return $data;

    }

    public function _get_data_by_column($args) {
        global $this_page;

        twfy_debug (get_class($this), "getting data by column");

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
    public $major = 4;
    public $listpage = 'wms';
    public $commentspage = 'wms';
    public $gidprefix = 'uk.org.publicwhip/wms/';

    public function _get_data_by_recent_wms($args = array()) {
        return $this->_get_data_by_recent_wrans($args);
    }
}

class WHALLLIST extends DEBATELIST {
    public $major = 2;
    public $listpage = 'whalls';
    public $commentspage = 'whall';
    public $gidprefix = 'uk.org.publicwhip/westminhall/';
}

class NILIST extends DEBATELIST {
    public $major = 5;
    public $listpage = 'nidebates';
    public $commentspage = 'nidebate';
    public $gidprefix = 'uk.org.publicwhip/ni/';
}

class SPLIST extends DEBATELIST {
    public $major = 7;
    public $listpage = 'spdebates';
    public $commentspage = 'spdebate';
    public $gidprefix = 'uk.org.publicwhip/spor/';
}

class SPWRANSLIST extends WRANSLIST {
    public $major = 8;
    public $listpage = 'spwrans';
    public $commentspage = 'spwrans';
    public $gidprefix = 'uk.org.publicwhip/spwa/';

    public function get_gid_from_spid($spid) {
        // Fix the common errors of S.0 instead of S.O and leading
        // zeros in the numbers:
        $fixed_spid = preg_replace('/(S[0-9]+)0-([0-9]+)/','${1}O-${2}',$spid);
        $fixed_spid = preg_replace('/(S[0-9]+\w+)-0*([0-9]+)/','${1}-${2}',$fixed_spid);
        $q = $this->db->query(
            "select mentioned_gid from mentions where gid = :gid_from_spid and (type = 4 or type = 6)",
            array(':gid_from_spid' => 'uk.org.publicwhip/spq/' . $fixed_spid)
        );
        $gid = $q->field(0, 'mentioned_gid');
        if ($gid) return $gid;
        return null;
    }
    public function old_get_gid_from_spid($spid) {
        $q = $this->db->query(
            "select gid from hansard where gid like :gid_like",
            array(':gid_like' => 'uk.org.publicwhip/spwa/%.' . $spid . '.h')
        );
        $gid = $q->field(0, 'gid');
        if ($gid) return str_replace('uk.org.publicwhip/spwa/', '', $gid);
        return null;
    }
}

class LORDSDEBATELIST extends DEBATELIST {
    public $major = 101;
    public $listpage = 'lordsdebates';
    public $commentspage = 'lordsdebate';
    public $gidprefix = 'uk.org.publicwhip/lords/';
}

class DEBATELIST extends HANSARDLIST {
    public $major = 1;
    public $listpage = 'debates';
    public $commentspage = 'debate';
    public $gidprefix = 'uk.org.publicwhip/debate/';

    public function _get_data_by_recent_mostvotes($args) {
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
                                major, minor,
                                hdate, htime,
                                person_id,
                                epobject.body,
                                SUM(uservotes.vote) + anonvotes.yes_votes AS total_vote
                        FROM	hansard,
                                epobject
                                LEFT OUTER JOIN uservotes ON epobject.epobject_id = uservotes.epobject_id
                                LEFT OUTER JOIN anonvotes ON epobject.epobject_id = anonvotes.epobject_id
                        WHERE		major = :major
                        AND		hansard.epobject_id = epobject.epobject_id
                        AND		hdate >= DATE_SUB(CURDATE(), INTERVAL $days DAY)
                        GROUP BY epobject.epobject_id
                        HAVING 	total_vote > 0
                        ORDER BY total_vote DESC
                        LIMIT	$items_to_list
                        ", array(':major' => $this->major));

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
                    'minor'			=> $q->field($n, 'minor'),
                    'hdate'			=> $q->field($n, 'hdate'),
                    'body'			=> $q->field($n, 'body'),
                    'votes'			=> $q->field($n, 'total_vote')
                );

                // Remove the "uk.org.publicwhip/blah/" from the gid:
                // (In includes/utility.php)
                $speech['gid'] = fix_gid_from_db( $q->field($n, 'gid') );

                $speech['listurl'] = $this->_get_listurl($speech);

                $speech['speaker'] = $this->_get_speaker($q->field($n, 'person_id'), $q->field($n, 'hdate'), $q->field($n, 'htime'), $this->major );

                $speeches[] = $speech;
            }
        }

        if (count($speeches) > 0) {
            // Get the subsection texts.

            $num_speeches = count($speeches);
            for ($n=0; $n<$num_speeches; $n++) {
                //if ($this->major == 1) {
                    // Debate.
                    $parent = $this->_get_subsection ($speeches[$n]);

                //} elseif ($this->major == 3) {
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


    public function total_speeches() {

        $q = $this->db->query("SELECT COUNT(*) AS count FROM hansard WHERE major = :major AND htype = 12", array(':major' => $this->major));

        return $q->field(0, 'count');
    }


    public function biggest_debates($args=array()) {
        // So we can just get the data back for special formatting
        // on the front page, without doing the whole display() thing.
        return $this->_get_data_by_biggest_debates($args);
    }

    public function _get_data_by_featured_gid($args=array()) {
        $params = array();
        $data = array();

        $params[':gid'] = $args['gid'];
        $params[':major'] = $this->major;

        $query = "SELECT
                    body,
                    title,
                    h.hdate,
                    h.htime,
                    h.htype,
                    h.minor,
                    h.gid,
                    h.person_id,
                    h.subsection_id,
                    h.section_id,
                    h.epobject_id
            FROM    hansard h, epobject e
            WHERE   h.major = :major
            AND     h.gid = :gid
            AND     h.epobject_id = e.epobject_id";

        $q = $this->db->query($query, $params);

        if ( $q->rows ) {

            // This array just used for getting further data about this debate.
            $item_data = array (
                'major'         => $this->major,
                'minor'         => $q->field(0, 'minor'),
                'gid'           => fix_gid_from_db( $q->field(0, 'gid') ),
                'htype'         => $q->field(0, 'htype'),
                'section_id'    => $q->field(0, 'section_id'),
                'subsection_id' => $q->field(0, 'subsection_id'),
                'epobject_id'   => $q->field(0, 'epobject_id')
            );

            $list_url      = $this->_get_listurl( $item_data );
            $totalcomments = $this->_get_comment_count_for_epobject( $item_data );

            $body          = $q->field(0, 'body');
            $hdate         = $q->field(0, 'hdate');
            $htime         = $q->field(0, 'htime');

            // If this is a subsection, we're going to prepend the title
            // of the parent section, so let's get that.
            $parentbody = '';
            if ($item_data['htype'] == 11 || $item_data['htype'] == 12) {
                $r = $this->db->query("SELECT sec.body as sec_body, sec.title as sec_title,
                                              sub.body as sub_body, sub.title as sub_title
                                FROM    epobject sec, epobject sub
                                WHERE   sec.epobject_id = :section_id
                                AND     sub.epobject_id = :subsection_id",
                                array(
                                    ':section_id' => $item_data['section_id'],
                                    ':subsection_id' => $item_data['subsection_id'],
                                )
                            );
                $section_body  = $r->field(0, 'sec_body');
                $subsection_body = $r->field(0, 'sub_body');
                if ( $section_body && $subsection_body ) {
                    $parentbody = "$section_body : $subsection_body";
                } else {
                    $parentbody = "$section_body$subsection_body";
                }
            } else if ( $item_data['htype'] == 10 ) {
                $parentbody = $body;
            }

            // Get the question for this item.
            if ( $item_data['htype'] == 12 ) {
                $childbody = $body;
                $speaker = $this->_get_speaker($q->field(0, 'person_id'), $q->field(0, 'hdate'), $q->field(0, 'htime'), $this->major );
            } else {
                $r = $this->db->query("SELECT e.body, e.title,
                                        h.person_id, h.hdate, h.htime
                                FROM    hansard h, epobject e
                                WHERE   h.epobject_id = e.epobject_id
                                AND     h.subsection_id = :object_id
                                ORDER BY hpos
                                LIMIT 1
                                ",
                                array( ':object_id' => $item_data['epobject_id'] )
                );
                $childbody = $r->field(0, 'body');
                $speaker = $this->_get_speaker($r->field(0, 'person_id'), $r->field(0, 'hdate'), $r->field(0, 'htime'), $this->major );
            }

            global $hansardmajors;
            $more_url = new \URL( $hansardmajors[$this->major]['page_all'] );
            $details = array(
                'body'          => $body,
                'hdate'         => $hdate,
                'htime'         => $htime,
                'list_url'      => $list_url,
                'totalcomments' => $totalcomments,
                'child'         => array(
                    'body'      => $childbody,
                    'speaker'   => $speaker
                ),
                'parent'        => array(
                    'body'      => $parentbody
                ),
                'desc' => $hansardmajors[$this->major]['title'],
                'more_url' => $more_url->generate()
            );

            $data = array (
                'gid' => $args['gid'],
                'major' => $this->major,
                'info' => array(),
                'data' => $details,
            );
        }

        return $data;

    }
    public function _get_data_by_recent_debates($args=array()) {
        // Returns an array of some random recent debates from a set number of
        // recent days (that's recent days starting from the most recent day
        // that had any debates on).

        // $args['days'] is the number of days back to look for biggest debates (1 by default).
        // $args['num'] is the number of links to return (1 by default).

        $data = array();

        $params = array();

        // Get the most recent day on which we have a debate.
        $recentday = $this->most_recent_day();
        if (!count($recentday)) return $data;

        if (!isset($args['days']) || !is_numeric($args['days'])) {
            $args['days'] = 1;
        }
        if (!isset($args['num']) || !is_numeric($args['num'])) {
            $args['num'] = 1;
        }

        if ($args['num'] == 1) {
            $datewhere = "h.hdate = :hdate";
            $params[':hdate'] = $recentday['hdate'];
        } else {
            $firstdate = gmdate('Y-m-d', $recentday['timestamp'] - (86400 * $args['days']));
            $datewhere = "h.hdate >= :firstdate
                        AND h.hdate <= :hdate";
            $params[':firstdate'] = $firstdate;
            $params[':hdate'] = $recentday['hdate'];
        }

        $params[':limit'] = $args['num'];
        $params[':major'] = $this->major;

        $query = "SELECT COUNT(*) AS count,
                    body,
                    h.hdate,
                    sech.htype,
                    sech.htime,
                    sech.gid,
                    sech.subsection_id,
                    sech.section_id,
                    sech.epobject_id
            FROM    hansard h, epobject e, hansard sech
            WHERE   h.major = :major
            AND     $datewhere
            AND     h.subsection_id = e.epobject_id
            AND     sech.epobject_id = h.subsection_id
            GROUP BY h.subsection_id
            HAVING  count >= 5
            ORDER BY RAND()
            LIMIT   :limit";

        $q = $this->db->query($query, $params);

        for ($row=0; $row<$q->rows; $row++) {

            // This array just used for getting further data about this debate.
            $item_data = array (
                'major'         => $this->major,
                'gid'           => fix_gid_from_db( $q->field($row, 'gid') ),
                'htype'         => $q->field($row, 'htype'),
                'section_id'    => $q->field($row, 'section_id'),
                'subsection_id' => $q->field($row, 'subsection_id'),
                'epobject_id'   => $q->field($row, 'epobject_id')
            );

            $list_url      = $this->_get_listurl( $item_data );
            $totalcomments = $this->_get_comment_count_for_epobject( $item_data );

            $contentcount  = $q->field($row, 'count');
            $body          = $q->field($row, 'body');
            $hdate         = $q->field($row, 'hdate');
            $htime         = $q->field($row, 'htime');

            // If this is a subsection, we're going to prepend the title
            // of the parent section, so let's get that.
            $parentbody = '';
            if ($item_data['htype'] == 11) {
                $r = $this->db->query("SELECT body
                                FROM    epobject
                                WHERE   epobject_id = :epobject_id",
                    array(':epobject_id' => $item_data['section_id']));
                $parentbody = $r->field(0, 'body');
            }

            // Get the question for this item.
            $r = $this->db->query("SELECT e.body,
                                    h.person_id, h.hdate, h.htime
                            FROM    hansard h, epobject e
                            WHERE   h.epobject_id = e.epobject_id
                            AND     h.subsection_id = '" . $item_data['epobject_id'] . "'
                            ORDER BY hpos
                            LIMIT 1
                            ");
            $childbody = $r->field(0, 'body');
            $speaker = $this->_get_speaker($r->field(0, 'person_id'), $r->field(0, 'hdate'), $r->field(0, 'htime'), $this->major );

            $data[] = array(
                'contentcount'  => $contentcount,
                'body'          => $body,
                'hdate'         => $hdate,
                'htime'         => $htime,
                'list_url'      => $list_url,
                'totalcomments' => $totalcomments,
                'child'         => array(
                    'body'      => $childbody,
                    'speaker'   => $speaker
                ),
                'parent'        => array(
                    'body'      => $parentbody
                )
            );

        }

        $data = array (
            'info' => array(),
            'data' => $data
        );

        return $data;

    }

    public function _get_data_by_biggest_debates($args=array()) {
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

        $params = array(':recentdate' => $recentday['hdate']);
        if ($args['num'] == 1) {
            $datewhere = "h.hdate = :recentdate";
        } else {
            $params[':firstdate'] = gmdate('Y-m-d', $recentday['timestamp'] - (86400 * $args['days']));
            $datewhere = "h.hdate >= :firstdate AND	h.hdate <= :recentdate";
        }

        $params[':limit'] = $args['num'];
        $params[':major'] = $this->major;

        $q = $this->db->query("SELECT COUNT(*) AS count,
                                body,
                                h.hdate,
                                sech.htype,
                                sech.gid,
                                sech.subsection_id,
                                sech.section_id,
                                sech.epobject_id
                        FROM 	hansard h, epobject e, hansard sech
                        WHERE 	h.major = :major
                        AND 	$datewhere
                        AND  	h.subsection_id = e.epobject_id
                        AND 	sech.epobject_id = h.subsection_id
                        GROUP BY h.subsection_id
                        ORDER BY count DESC
                        LIMIT :limit", $params);


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
                                WHERE	epobject_id = :epobject_id",
                    array(':epobject_id' => $item_data['section_id']));
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
    public $major = 3;
    public $listpage = 'wrans';
    public $commentspage = 'wrans'; // We don't have a separate page for wrans comments.
    public $gidprefix = 'uk.org.publicwhip/wrans/';

    public function total_questions() {
        $q = $this->db->query("SELECT COUNT(*) AS count FROM hansard WHERE major = :major AND minor = 1", array(':major' => $this->major));
        return $q->field(0, 'count');
    }

    public function _get_data_by_recent_wrans ($args=array()) {
        global $hansardmajors;

        // $args['days'] is the number of days back to look for biggest debates.
        // (1 by default)
        // $args['num'] is the number of links to return (1 by default).

        $data = array();

        $params = array();

        // Get the most recent day on which we have wrans.
        $recentday = $this->most_recent_day();
        if (!count($recentday))
            return $data;

        if (!isset($args['days']) || !is_numeric($args['days'])) {
            $args['days'] = 1;
        }
        if (!isset($args['num']) || !is_numeric($args['num'])) {
            $args['num'] = 1;
        }

        if ($args['num'] == 1) {
            $datewhere = "h.hdate = :datewhere";
            $params[':datewhere'] = $recentday['hdate'];
        } else {
            $firstdate = gmdate('Y-m-d', $recentday['timestamp'] - (86400 * $args['days']));
            $datewhere = "h.hdate >= :firstdate AND h.hdate <= :hdate";
            $params[':firstdate'] = $firstdate;
            $params[':hdate'] = $recentday['hdate'];
        }


        // Get a random selection of subsections in wrans.
        if ($hansardmajors[$this->major]['location'] == 'Scotland') {
            $htype = 'htype = 10 and section_id = 0';
        } else {
            $htype = 'htype = 11 and section_id != 0';
        }

        $params[':limit'] = $args['num'];
        $params[':major'] = $this->major;

        $query = "SELECT e.body,
                    h.hdate,
                    h.htype,
                    h.gid,
                    h.subsection_id,
                    h.section_id,
                    h.epobject_id
            FROM    hansard h, epobject e
            WHERE   h.major = :major
            AND     $htype
            AND     subsection_id = 0
            AND     $datewhere
            AND     h.epobject_id = e.epobject_id
            ORDER BY RAND()
            LIMIT   :limit";

        $q = $this->db->query($query, $params);

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
            $parentbody = '';
            if ($q->field($row, 'section_id')) {
                $r = $this->db->query("SELECT e.body
                            FROM	hansard h, epobject e
                            WHERE	h.epobject_id = e.epobject_id
                            AND		h.epobject_id = '" . $q->field($row, 'section_id') . "'
                            ");
                $parentbody = $r->field(0, 'body');
            }

            // Get the question for this item.
            $r = $this->db->query("SELECT e.body,
                                    h.person_id, h.hdate, h.htime
                            FROM	hansard h, epobject e
                            WHERE	h.epobject_id = e.epobject_id
                            AND 	h.subsection_id = '" . $q->field($row, 'epobject_id') . "'
                            ORDER BY hpos
                            LIMIT 1
                            ");
            $childbody = $r->field(0, 'body');
            $speaker = $this->_get_speaker($r->field(0, 'person_id'), $r->field(0, 'hdate'), $r->field(0, 'htime'), $this->major );

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

class StandingCommittee extends DEBATELIST {
    public $major = 6;
    public $listpage = 'pbc_clause';
    public $commentspage = 'pbc_speech';
    public $gidprefix = 'uk.org.publicwhip/standing/';

    public function __construct($session='', $title='') {
        parent::__construct();
        $this->bill_title = $title;
        $title = str_replace(' ', '_', $title);
        $this->url = urlencode($session) . '/' . urlencode($title) . '/';
    }

    public function _get_committee($bill_id) {
        include_once INCLUDESPATH."easyparliament/member.php";
        $q = $this->db->query(
            'select count(*) as c from hansard
                where major=6 and minor=:bill_id and htype=10',
            array(':bill_id' => $bill_id)
        );
        $sittings = $q->field(0, 'c');
        $q = $this->db->query(
            'select person_id,sum(attending) as attending, sum(chairman) as chairman
                from pbc_members
                where bill_id = :bill_id group by person_id',
            array(':bill_id' => $bill_id));
        $comm = array('sittings'=>$sittings);
        for ($i=0; $i<$q->rows(); $i++) {
            $person_id = $q->field($i, 'person_id');
            $mp = new MEMBER(array('person_id'=>$person_id));
            $attending = $q->field($i, 'attending');
            $chairman = $q->field($i, 'chairman');
            $arr = array(
                'name' => $mp->full_name(),
                'attending' => $attending,
            );
            if ($chairman) {
                $comm['chairmen'][$person_id] = $arr;
            } else {
                $comm['members'][$person_id] = $arr;
            }
        }
        return $comm;
    }

    public function _get_data_by_bill($args) {
        global $DATA, $this_page;
        $data = array();
        $input = array (
            'amount' => array (
                'body' => true,
                'comment' => true,
                'excerpt' => true
            ),
            'where' => array (
                'htype=' => '10',
                'major=' => $this->major,
                'minor=' => $args['id'],
            ),
            'order' => 'hdate,hpos'
        );
        $sections = $this->_get_hansard_data($input);
        if (count($sections) > 0) {
            $data['rows'] = array();
            $num_sections = count($sections);
            for ($n=0; $n<$num_sections; $n++) {
                $sectionrow = $sections[$n];
                list($sitting, $part) = $this->_get_sitting($sectionrow['gid']);
                $sectionrow['sitting'] = $sitting;
                $sectionrow['part'] = $part;
                $input = array (
                    'amount' => array (
                        'body' => true,
                        'comment' => true,
                        'excerpt' => true
                    ),
                    'where' => array (
                        'section_id='	=> $sectionrow['epobject_id'],
                        'htype='	=> '11',
                        'major='	=> $this->major
                    ),
                    'order' => 'hpos'
                );
                $rows = $this->_get_hansard_data($input);
                array_unshift ($rows, $sectionrow);
                $data['rows'] = array_merge ($data['rows'], $rows);
            }
        }
        $data['info']['bill'] = $args['title'];
        $data['info']['major'] = $this->major;
        $data['info']['committee'] = $this->_get_committee($args['id']);
        $DATA->set_page_metadata($this_page, 'title', $args['title']);
        return $data;
    }

    public function _get_data_by_session($args) {
        global $DATA, $this_page;
        $session = $args['session'];
        $q = $this->db->query(
            'select id, title from bills where session = :session order by title',
            array(':session' => $session)
        );
        $bills = array();
        for ($i=0; $i<$q->rows(); $i++) {
            $bills[$q->field($i, 'id')] = $q->field($i, 'title');
        }
        if (!count($bills)) {
            return array();
        }
        $q = $this->db->query('select minor,count(*) as c from hansard where major=6 and htype=12
            and minor in (' . join(',', array_keys($bills)) . ')
            group by minor');
        $counts = array();
        # $comments = array();
        for ($i=0; $i<$q->rows(); $i++) {
            $minor = $q->field($i, 'minor');
            $counts[$minor] = $q->field($i, 'c');
            # $comments[$minor] = 0;
        }
        /*
        $q = $this->db->query('select minor,epobject_id from hansard where major=6 and htype=10
            and minor in (' . join(',', array_keys($bills)) . ')');
        for ($i=0; $i<$q->rows(); $i++) {
            $comments[$q->field($i, 'minor')] += $this->_get_comment_count_for_epobject(array(
                'epobject_id' => $q->field($i, 'epobject_id'),
                'htype' => 10,
            ));
        }
        */
        $data = array();
        foreach ($bills as $id => $title) {
            $data[] = array(
                'title' => $title,
                'url' => "/pbc/" . urlencode($session) . '/' . urlencode(str_replace(' ', '_', $title)) . '/',
                'contentcount' => isset($counts[$id]) ? $counts[$id] : '???',
                # 'totalcomments' => isset($comments[$id]) ? $comments[$id] : '???',
            );
        }

        $YEARURL = new URL('pbc_session');
        $nextprev = array();
        $nextprev['prev'] = array ('body' => 'Previous session', 'title'=>'');
        $nextprev['next'] = array ('body' => 'Next session', 'title'=>'');
        $q = $this->db->query(
            "SELECT session FROM bills WHERE session < :session ORDER BY session DESC LIMIT 1",
            array(':session' => $session)
        );
        $prevyear = $q->field(0, 'session');
        $q = $this->db->query(
            "SELECT session FROM bills WHERE session > :session ORDER BY session ASC LIMIT 1",
            array(':session' => $session)
        );
        $nextyear = $q->field(0, 'session');
        if ($prevyear) {
            $nextprev['prev']['url'] = $YEARURL->generate() . $prevyear . '/';
        }
        if ($nextyear) {
            $nextprev['next']['url'] = $YEARURL->generate() . $nextyear . '/';
        }
        $DATA->set_page_metadata($this_page, 'nextprev', $nextprev);

        return $data;
    }

    public function _get_data_by_recent_pbc_debates($args) {
        if (!isset($args['num'])) $args['num'] = 20;
        $q = $this->db->query('select gid, minor, hdate from hansard
            where htype=10 and major=6
            order by hdate desc limit ' . $args['num']);
        $data = array();
        for ($i=0; $i<$q->rows(); $i++) {
            $minor = $q->field($i, 'minor');
            $gid = $q->field($i, 'gid');
            $hdate = format_date($q->field($i, 'hdate'), LONGDATEFORMAT);
            $qq = $this->db->query('select title, session from bills where id='.$minor);
            $title = $qq->field(0, 'title');
            $session = $qq->field(0, 'session');
            list($sitting, $part) = $this->_get_sitting($gid);
            $sitting_txt = make_ranking($sitting) . ' sitting';
            if ($part>0) $sitting .= ", part $part";
            $data[$hdate][] = array(
                'bill'=> $title,
                'sitting' => $sitting_txt,
                'url' => "/pbc/$session/" . urlencode(str_replace(' ','_',$title)) . '/#sitting' . $sitting,
            );
        }
        return $data;
    }

    # Given a GID, parse out the sitting number and optional part from it
    public function _get_sitting($gid) {
        if (preg_match('#_(\d\d)-(\d)_#', $gid, $m))
            return array($m[1]+0, $m[2]);
        return array(0, 0);
    }
}
