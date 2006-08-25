<?php
// For displaying the main Hansard content listings (by gid), 
// and individual Hansard items (the comments are handled separately
// by COMMENTLIST and the comments.php template).

// Remember, we are currently within the DEBATELIST or WRANSLISTS class,
// in the render() function.

// The array $data will be packed full of luverly stuff about hansard objects.
// See the bottom of this document for information about its structure and contents...

global $PAGE, $this_page, $DATA, $GLOSSARY, $hansardmajors;

include_once INCLUDESPATH."easyparliament/searchengine.php";
include_once INCLUDESPATH."easyparliament/member.php";


twfy_debug("TEMPLATE", "hansard_gid.php");

// Will set the page headings and start the page HTML if it hasn't 
// already been started.
if (!isset($data['info'])) {
	// No data! We'll be exiting here! but send a 404 so that spiders stop indexing the page.
	header("HTTP/1.0 404 Not Found");
	# Bots have some fiddled with wrans, so we get errors here sometimes that don't
	# signify a real problem.
	#trigger_error("Not enough data to display anything.", E_USER_ERROR);
	exit;
}

$PAGE->page_start();

$PAGE->stripe_start('head-1');

$sidebar = $hansardmajors[$data['info']['major']]['sidebar_short'];

$PAGE->stripe_end(array(
	array (
		'type' =>'include',
		'content' => $sidebar
	)
));

if ($data['info']['date'] == date('Y-m-d')) { ?>
<div style="padding: 4px; margin: 1em; color: #000000; background-color: #ffeeee; border: solid 2px #ff0000;">
Warning: Showing data from the current day is <strong>experimental</strong> and may not work correctly.
</div>
<?
}

if (isset ($data['rows'])) {
    // For highlighting
    $SEARCHENGINE = null;
    if (isset($data['info']['searchstring']) && $data['info']['searchstring'] != '') {
        $SEARCHENGINE = new SEARCHENGINE($data['info']['searchstring']);
    }

	// Stores the current time of items, so we can tell when an item appears
	// at a new time.
	$timetracker = 0;

	$stripecount = 0; // Used to generate stripes.
	
	// We're going to be just cycling through each row of data for this page.
	// When we get the first section, we put its text in $section_title.
	// When we get the first subsection, we put its text in $subsection_title.
	// When we get the first item that is neither section or subsection, we
	// print these titles.
	$section_title = '&nbsp;';
	$subsection_title = '&nbsp;';

	// So we don't keep on printing the titles!
	$titles_displayed = false;
	for ($i=0; $i<count($data['rows']); $i++) {
		$row = $data['rows'][$i];
		if (count($row) == 0) {
			// Oops, there's nothing in this row. A check just in case.
			continue;
		}

		// DISPLAY SECTION AND SUBSECTION HEADINGS.
		if (!$titles_displayed && $row['htype'] != '10' && $row['htype'] != '11') {
			// Output the titles we've got so far.
			
			$PAGE->stripe_start('head-2');
			?>
				<h4><?php echo $section_title; ?></h4>
				<h5><?php echo $subsection_title; ?></h5>
<?php
#			$body = technorati_pretty();
#			if ($body) {
#				print '<div class="blockbody">' . $body . '</div>';
#			}
			$PAGE->stripe_end(array(
				array (
					'type' => 'nextprev'
				)
			));
			
			$titles_displayed = true;
		}
		
		// NOW, depending on the contents of this row, we do something different...
		if ($row['htype'] == '10') {
			$section_title = $row['body'];
			twfy_debug("DATAMODEL" , "epobjectid " . htmlentities($row['epobject_id']));
		} elseif ($row['htype'] == '11') {
			$subsection_title = $row['body'];
		} elseif ($row['htype'] == '13') {
			// DEBATE PROCEDURAL.
			
			$stripecount++;
			$style = $stripecount % 2 == 0 ? '1' : '2';	
			
			$PAGE->stripe_start('procedural-'.$style);
			
			echo $row['body'];
			
			context_link($row);
			
			$sidebarhtml = generate_commentteaser(&$row, $data['info']['major']);
			
			$PAGE->stripe_end(array(
				array (
					'type' => 'html',
					'content' => $sidebarhtml
				)
			));

	

		} elseif ( $row['htype'] == '12') {
			// A STANDARD SPEECH OR WRANS TEXT.
		
			$stripecount++;
			$style = $stripecount % 2 == 0 ? '1' : '2';
			
			
			// If this item is at a new time, then print the time.
			if ($row['htime'] != $timetracker && $row['htime'] != "00:00:00") {
				
				$PAGE->stripe_start('time-'.$style);
				
				echo "\t\t\t\t<p>" . format_time($row['htime'], TIMEFORMAT) . "</p>\n";
				
				$PAGE->stripe_end();

				// Set the timetracker to the current time
				$timetracker = $row['htime'];

				$stripecount++;
				$style = $stripecount % 2 == 0 ? '1' : '2';
			}
	

			if (isset($row['speaker']) && ( (isset($row['speaker']['member_id']) && isset($data['info']['member_id']) && $row['speaker']['member_id'] == $data['info']['member_id']) || (isset($row['speaker']['person_id']) && isset($data['info']['person_id']) && $row['speaker']['person_id'] == $data['info']['person_id']) ) ) {
				$style .= '-on';
			}
			
			// gid_to_anchor() is in utility.php
			$id = 'g' . gid_to_anchor($row['gid']);
			
			$PAGE->stripe_start($style, $id);	
			
			?>
				<a name="<?php echo $id; ?>"></a>
<?php

			// Before we print the body text we need to insert glossary links
			// and highlight search string words.
			
			// This doesn't quite work yet, as clashes with
			// highlighting of constituency name. Also the link
			// text comes out a bit long?
			// $row['body'] = preg_replace('#<phrase class="honfriend" id="uk.org.publicwhip/member/(\d+)" name="(.*?)">(.*)</phrase>#', '<a href="/mp/?m=$1" title="Our page on $2">$3</a>', $row['body']);
			
			if (isset($data['info']['glossarise']) && ($data['info']['glossarise'] == 1)) {
				// And glossary phrases
				$row['body'] = $GLOSSARY->glossarise($row['body'], 1);
			}
			if ($SEARCHENGINE) {
				// We have some search terms to highlight.
				$row['body'] = $SEARCHENGINE->highlight($row['body']);
			}
			if (isset($data['info']['glossarise']) && ($data['info']['glossarise'] == 1)) {
				// Now we replace the title attributes for the glossarised links
				// to avoid words being highlighted within them.
				$row['body'] = $GLOSSARY->glossarise_titletags($row['body'], 1);
			}
			
			if (isset($row['speaker']) && count($row['speaker']) > 0) {
			  // We have a speaker to print.
			  
				$speaker = $row['speaker'];
				$speakername = ucfirst(member_full_name($speaker['house'], $speaker['title'], $speaker['first_name'], $speaker['last_name'], $speaker['constituency']));
				?>
				<p class="speaker"><a href="<?php echo $speaker['url']; ?>" title="See more information about <?php echo $speakername; ?>">
<? if (is_file(BASEDIR . IMAGEPATH . 'mps/' . $speaker['person_id'] . '.jpeg')) { ?>
                <img src="<?php echo IMAGEPATH . 'mps/' . $speaker['person_id']; ?>.jpeg" class="portrait" alt="Photo of <?php echo $speakername; ?>" 
				<?php if (get_http_var('partycolours')) { ?>
                style="border: 3px solid <?php echo party_to_colour($speaker['party']) ?>;"
                <?php } ?>
		/><?php } elseif (is_file(BASEDIR . IMAGEPATH . 'mps/' . $speaker['person_id'] . '.jpg')) { ?>
		<img src="<?php echo IMAGEPATH . 'mps/' . $speaker['person_id']; ?>.jpg" class="portrait" alt="Photo of <?php echo $speakername; ?>" /><? } ?>
                <strong><?php echo $speakername; ?></strong></a> <small><?php 
				$desc = '';
				if (isset($speaker['office'])) {
					$desc = $speaker['office'][0]['pretty'];
					if (strpos($desc, 'PPS')!==false) $desc .= ', ';
				}
				if (!$desc || strpos($desc, 'PPS')!==false) {
					if ($speaker['house'] == 1 && $speaker['party'] != 'Speaker' && $speaker['party'] != 'Deputy Speaker' && $speaker['constituency']) {
						$desc .= $speaker['constituency'] . ', ';
					}
					if (get_http_var('wordcolours')) {
						$desc .= '<span style="color: '.party_to_colour($speaker['party']).'">';
					}
					$desc .= htmlentities($speaker['party']);
					if (get_http_var('wordcolours')) {
						$desc .= '</span>';
					}
				}
				if ($desc) print "($desc)";

				if ($hansardmajors[$data['info']['major']]['type']=='debate' && $this_page == $hansardmajors[$data['info']['major']]['page_all']) {
					?> <a href="<?php echo $row['commentsurl']; ?>" title="Copy this URL to link directly to this piece of text" class="permalink">Link to this</a> | <?php
				}
				if (isset($row['source_url']) && $row['source_url'] != '') {
					echo ' <a href="' . $row['source_url'] . '" title="The source of this piece of text">Hansard source</a>';
				}

				echo "</small></p>\n";
				
			}

			$body = $row['body'];
#			$body = preg_replace('#<phrase class="offrep" id="([^"]*?)/([^"]*?)">#', '<a href="/$1/?id=$2.0">', $body);
#			$body = str_replace('</phrase>', '</a>', $body);
			$body = preg_replace('#(<p[^>]*class=".*?)("[^>]*)pwmotiontext="moved"#', '$1 moved$2', $body);
			$body = str_replace('pwmotiontext="moved"', 'class="moved"', $body);
			echo str_replace('</p><p','</p> <p',$body);
			
			context_link($row);
			
			$sidebarhtml = '';
			$extrahtml = '';
			
			if (isset($row['votes']) && (!strstr($row['gid'], 'q'))) {
				$sidebarhtml .= generate_votes ( $row['votes'], $row['major'], $row['epobject_id'], $row['gid'] );
			}

# Do the logic for this in the function; plus why shouldn't
# you be able to comment on speeches with unknown speakers?
#			if (($hansardmajors[$data['info']['major']]['type'] == 'debate') && isset($row['speaker']) && count($row['speaker']) > 0) {
			$sidebarhtml .= generate_commentteaser(&$row, $data['info']['major']);
#			}
			

			$PAGE->stripe_end(array(
				array (
					'type' => 'html',
					'content' => $sidebarhtml
				),
				array (
					'type' => 'extrahtml',
					'content' => $extrahtml
				)
			));
			
				
		} // End htype 12.
		

		// TRACKBACK AUTO DISCOVERY 
/*		if (isset($row['trackback']) && count($row['trackback']) > 0) {

			$PAGE->trackback_rss($row['trackback']);
		} */
		ob_flush(); //flush the output buffer		
	
	} // End cycling through rows.
	
	if (!$titles_displayed) {
		$PAGE->stripe_start('head-2');
		?>
				<h4><?php echo $section_title; ?></h4>
				<h5><?php echo $subsection_title; ?></h5>
<?php
		$PAGE->stripe_end(array(
			array (
				'type' => 'nextprev'
			)
		));
		$titles_displayed = true;
	}

	if (isset($data['subrows'])) {
		$PAGE->stripe_start();
		print '<ul>';
		foreach ($data['subrows'] as $row) {
			print '<li>';
			if (isset($row['contentcount']) && $row['contentcount'] > 0) {
				$has_content = true;
			} elseif ($row['htype'] == '11' && $hansardmajors[$row['major']]['type'] == 'other') {
				$has_content = true;
			} else {
				$has_content = false;
			}
			if ($has_content) {
				print '<a href="' . $row['listurl'] . '"><strong>' . $row['body'] . '</strong></a> ';
				// For the "x speeches, x comments" text.
				$moreinfo = array();
				if ($hansardmajors[$row['major']]['type'] != 'other') {
					// All wrans have 2 speeches, so no need for this.
					// All WMS have 1 speech
					$plural = $row['contentcount'] == 1 ? 'speech' : 'speeches';
					$moreinfo[] = $row['contentcount'] . " $plural";
				}
				if ($row['totalcomments'] > 0) {
					$plural = $row['totalcomments'] == 1 ? 'comment' : 'comments';
					$moreinfo[] = $row['totalcomments'] . " $plural";
				}
				if (count($moreinfo) > 0) {
					print "<small>(" . implode (', ', $moreinfo) . ") </small>";
				}	
			} else {
				// Nothing in this item, so no link.	
				print '<strong>' . $row['body'] . '</strong>';
			}
			if (isset($row['excerpt'])) {
				print "<br />\n\t\t\t\t\t<span class=\"excerpt-debates\">" . trim_characters($row['excerpt'], 0, 200) . "</span>";
			}
		}
		print '</ul>';
		$PAGE->stripe_end();
	}
}  else {
	?>
<p>No data to display.</p>

<?php
   }



if ($this_page == 'debates' || $this_page == 'whall' || $this_page == 'lordsdebates') {
	// Previous / Index / Next links, if any.
	
	$PAGE->stripe_start('foot');
	?>&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;<?php
	$PAGE->stripe_end(array(
		array (
			'type' => 'nextprev'
		)
	));
}


function context_link (&$row) {
	global $this_page;
	
	if ($this_page == 'debate') {
		if ($row['htype'] == '12') {
			$thing = 'speech';
		} elseif ($row['htype'] == '13') {
			$thing = 'item';
		} else {
			$thing = 'item';
		}
		?>
				<p><small><strong><a href="<?php echo $row['listurl']; ?>" class="permalink" title="See this <?php echo $thing; ?> within the entire debate">See this <?php echo $thing; ?> in context</a></strong></small></p>
<?php
	}
}



//$totalcomments, $comment, $commenturl
function generate_commentteaser (&$row, $major) {
	// Returns HTML for the one fragment of comment and link for the sidebar.
	// $totalcomments is the number of comments this item has on it.
	// $comment is an array like:
	/* $comment = array (
		'comment_id' => 23,
		'user_id'	=> 34,
		'body'		=> 'Blah blah...',
		'posted'	=> '2004-02-24 23:45:30',
		'username'	=> 'phil'
		)
	*/
	// $url is the URL of the item's page, which contains comments.
	
	global $this_page, $THEUSER, $hansardmajors;
	
	$html = '';
	
	if ($hansardmajors[$major]['type'] == 'debate' && $hansardmajors[$major]['page_all']==$this_page) {

		if ($row['totalcomments'] > 0) {
			$comment = $row['comment'];
			
			// If the comment is longer than the speech body, we want to trim it
			// to be the same length so they fit next to each other.
			// But the comment typeface is smaller, so we scale things slightly too...
			$targetsize = round(strlen($row['body']) * 0.6);
			
			if ($targetsize > strlen($comment['body'])) {
				// This comment will fit in its entirety.
				$commentbody = $comment['body'];

				if ($row['totalcomments'] > 1) {
					$morecount = $row['totalcomments'] - 1;
					$plural = $morecount == 1 ? 'comment' : 'comments';
					$linktext = "Read $morecount more $plural";
				}
				
			} else {
				// This comment needs trimming.
				$commentbody = htmlentities(trim_characters($comment['body'], 0, $targetsize));
				if ($row['totalcomments'] > 1) {
					$morecount = $row['totalcomments'] - 1;
					$plural = $morecount == 1 ? 'comment' : 'comments';
					$linktext = "Continue reading (and $morecount more $plural)";
				} else {
					$linktext = 'Continue reading';
				}
			}
		
			$html = '<em>' . htmlentities($comment['username']) . '</em>: ' . prepare_comment_for_display($commentbody);
			
			if (isset($linktext)) {
				$html .= ' <a href="' . $row['commentsurl'] . '#c' . $comment['comment_id'] . '" title="See any comments posted about this">' . $linktext . '</a>';
			}
			
			$html .= '<br /><br />';
		}

		// 'Add a comment' link.
		if (!$THEUSER->isloggedin()) {
			$URL = new URL('userprompt');
			$URL->insert(array('ret'=>$row['commentsurl']));
			$commentsurl = $URL->generate();
		} else {
			$commentsurl = $row['commentsurl'];
		}
		
		$html .= '<a href="' . $commentsurl . '#addcomment" title="Comment on this"><strong>Add your comment</strong></a>';

		$html = "\t\t\t\t" . '<p class="comment-teaser">' . $html . "</p>\n";
	}
	
	return $html;
}

$votelinks_so_far = 0;
function generate_votes ($votes, $major, $id, $gid) {
	/*
	Returns HTML for the 'Interesting?' links (debates) or the 'Does this answer 
	the question?' links (wrans) in the sidebar.
	
	We have yes/no, even for debates, for which we only allow people to say 'yes'.
	
	$votes = => array (
		'user'	=> array (
			'yes'	=> '21',
			'no'	=> '3'
		),
		'anon'	=> array (
			'yes'	=> '132',
			'no'	=> '30'
		)
	)
	
	$major is the htype of this item (eg, 12 or 13 for debates, 61 or 62 for wrans).
	$id is an epobject_id.
	*/
			
	global $this_page, $votelinks_so_far;
	
	// What we return.
	$html = '';
	
	$URL = new URL($this_page);
	$returl = $URL->generate();
	
	$VOTEURL = new URL('epvote');
	$VOTEURL->insert(array('v'=>'1', 'id'=>$id, 'ret'=>$returl));

	if ($major == 3 && ($votelinks_so_far > 0 || preg_match('#r#', $gid) ) ) { # XXX
		// Wrans.
		$yesvotes = $votes['user']['yes'] + $votes['anon']['yes'];
		$novotes = $votes['user']['no'] + $votes['anon']['no'];
		
		$yesplural = $yesvotes == 1 ? 'person thinks' : 'people think';
		$noplural = $novotes == 1 ? 'person thinks' : 'people think';
		
		$html .= '<strong>Does this answer the above question?</strong><br />';
		
		$html .= '<span class="wransvote"><a href="' . $VOTEURL->generate() . '" title="Rate this as answering the question">Yes!</a> ' . $yesvotes . ' ' . $yesplural . ' so!<br />';

		$VOTEURL->insert(array('v'=>'0'));
		
		$html .= '<a href="' . $VOTEURL->generate() . '" title="Rate this as NOT answering the question">No!</a> ' . $novotes . ' ' . $noplural . ' not!</span>';

	} elseif ($major == 1) {
		// Debates.
		
		/*
		We aren't putting Interesting? buttons in for now...
		
		$VOTEURL->insert(array('v'=>'1'));
		$totalvotes = $votes['user']['yes'] + $votes['anon']['yes'];
		$plural = $totalvotes == 1 ? 'person thinks' : 'people think';
		
		$html .= '<a href="' . $VOTEURL->generate() . '" title="Rate this as being interesting">Interesting?</a> ' . $totalvotes . ' ' . $plural . ' so!';
		*/

	}

	$votelinks_so_far++;
	$html = "\t\t\t\t<p class=\"vote\">$html</p>\n";
	return $html;
	
}

/*

Structure of the $data array.

(Notes for the diagram below...)
The 'info' section is metadata about the results set as a whole.

'rows' is an array of items to display, each of which has a set of Hansard object data and more. The item could be a section heading, subsection, speech, written question, procedural, etc, etc.


In the diagram below, 'HansardObjectData' indicates a standard set of key/value
pairs along the lines of:
	'epobject_id'	=> '31502',
	'gid'			=> '2003-12-31.475.3',
	'hdate'			=> '2003-12-31',
	'htype'			=> '12',
	'body'			=> 'A lot of text here...',
	'listurl'		=> '/debates/?id=2003-12-31.475.0#g2003-12-31.475.3',
	'commentsurl'	=> '/debate/?id=2003-12-31.475.3',
	'speaker_id'	=> '931',
	'speaker'		=> array (
		'member_id'		=> '931',
		'first_name'	=> 'Peter',
		'last_name'		=> 'Hain',
		'constituency'	=> 'Neath',
		'party'			=> 'Lab',
		'url'			=> '/member/?id=931'
	),
	'totalcomments'	=> 5,
	'comment'		=> array (
		'user_id'		=> '45',
		'body'			=> 'Comment text here...',
		'posted'		=> '2003-12-31 23:00:00',
		'username'		=> 'William Thornton',
	),
	'votes'	=> array (
		'user'	=> array (
			'yes'	=> '21',
			'no'	=> '3'
		),
		'anon'	=> array (
			'yes'	=> '132',
			'no'	=> '30'
		)
	),
	'trackback'		=> array (
		'itemurl'		=> 'http://www.easyparliament.com/debate/?id=2003-12-31.475.3',
		'pingurl'		=> 'http://www.easyparliament.com/trackback?g=debate_2003-02-28.475.3',
		'title'			=> 'Title of this item or page',
		'date'			=> '2003-12-31T23:00:00+00:00'
	)
	etc.

Note: There are two URLs. 
	'listurl' is a link to the item in context, in the list view. 
	'commentsurl' is the page where we can see this item and all its comments.

Note: The 'trackback' array won't always be there - only if we think we're going to
	be using it for Auto Discovery on this page.
	
Note: Speaker's only there if there is a speaker for this item.


$data = array (
	
	'info' => array (
		'date'	=> '2003-12-31',
		'text'	=> 'A brief bit of text for a title...',
		'searchwords' => array ('fox', 'hunting')
	),

	'rows' => array (
		0 => array ( HansardObjectData ),
		1 => array ( HansardObjectData ), etc...		
	)
);


*/
?>
