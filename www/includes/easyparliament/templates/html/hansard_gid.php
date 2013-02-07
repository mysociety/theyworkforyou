<?php
// For displaying the main Hansard content listings (by gid), 
// and individual Hansard items (the comments are handled separately
// by COMMENTLIST and the comments.php template).

// Remember, we are currently within the DEBATELIST or WRANSLISTS class,
// in the render() function.

// The array $data will be packed full of luverly stuff about hansard objects.
// See the bottom of this document for information about its structure and contents...

global $PAGE, $this_page, $GLOSSARY, $hansardmajors, $DATA, $THEUSER;

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
$PAGE->supress_heading = true;
/*
$PAGE->stripe_start('head-1', '');

$sidebar = $hansardmajors[$data['info']['major']]['sidebar_short'];

$PAGE->stripe_end(array(
	array (
		'type' =>'include',
		'noplatypus' => true,
		'content' => $sidebar
	)
));
*/
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

	// Before we print the body text we need to insert glossary links
	// and highlight search string words.
	
	$bodies = array();
	foreach ($data['rows'] as $row) {
		$bodies[] = $row['body'];
	}
	if (isset($data['info']['glossarise']) && $data['info']['glossarise']) {
		// And glossary phrases
		twfy_debug_timestamp('Before glossarise');
		$bodies = $GLOSSARY->glossarise($bodies, $data['info']['glossarise']);
		twfy_debug_timestamp('After glossarise');
	}
	if ($SEARCHENGINE) {
		// We have some search terms to highlight.
		twfy_debug_timestamp('Before highlight');
		$bodies = $SEARCHENGINE->highlight($bodies);
		twfy_debug_timestamp('After highlight');
	}
	if (isset($data['info']['glossarise']) && ($data['info']['glossarise'] == 1)) {
		// Now we replace the title attributes for the glossarised links
		// to avoid words being highlighted within them.
		twfy_debug_timestamp('Before glossarise_titletags');
		$bodies = $GLOSSARY->glossarise_titletags($bodies, 1);
		twfy_debug_timestamp('After glossarise_titletags');
	}

	$speeches = 0;
	for ($i=0; $i<count($data['rows']); $i++) {
		if ($data['rows'][$i]['htype'] == 12)
			$data['rows'][$i]['body'] = $bodies[$i];
		if ($data['rows'][$i]['htype'] == 12 || $data['rows'][$i]['htype'] == 13)
			$speeches++;

	}

	// Stores the current time of items, so we can tell when an item appears
	// at a new time.
	$timetracker = 0;

	$stripecount = 0; // Used to generate stripes.

	$first_speech_displayed = 0; // We want to know when to insert the video
	$first_video_displayed = 0; // or the advert to do the video
	
	// We're going to be just cycling through each row of data for this page.
	// When we get the first section, we put its text in $section_title.
	// When we get the first subsection, we put its text in $subsection_title.
	// When we get the first item that is neither section or subsection, we
	// print these titles.
	$section = array('title' => '&nbsp;');
	$subsection_title = '&nbsp;';

	// So we don't keep on printing the titles!
	$titles_displayed = false;
	foreach ($data['rows'] as $row) {
		if (count($row) == 0) {
			// Oops, there's nothing in this row. A check just in case.
			continue;
		}

		// DISPLAY SECTION AND SUBSECTION HEADINGS.
		if (!$titles_displayed && $row['htype'] != '10' && $row['htype'] != '11') {
			// Output the titles we've got so far.

			$PAGE->stripe_start('head-1');
			
			//get section text (e.g. house of commons debate)
		    $parent_page = $DATA->page_metadata($this_page, 'parent');			
			$section_text = $DATA->page_metadata($parent_page, 'title');		    
			
			//work out the time the debate started
            $has_start_time = false;
            if (substr($row['htime'],0,5) != $timetracker && $row['htime'] != "00:00:00") {
                $has_start_time = true;
        		$timetracker = substr($row['htime'],0,5); // Set the timetracker to the current time
            }

            if ($subsection_title != '' && $subsection_title != "&nbsp;") {
                print "<h1>$subsection_title<br><span>$section[title]</span>";
            } else {
                print "<h1>$section[title]";
            }
            if ($has_start_time) {
               print "<br><span class='datetime'>" . format_time($row['htime'], TIMEFORMAT) . '</span>';
            }
            print '</h1>';

#			$body = technorati_pretty();
#			if ($body) {
#				print '<div class="blockbody">' . $body . '</div>';
#			}

            // Stripe end
			$PAGE->stripe_end(array(
				array (
					'noplatypus' => true,
					'type' => 'nextprev'
				)
			));

			$titles_displayed = true;
		}
		
		// NOW, depending on the contents of this row, we do something different...
		if ($row['htype'] == '10') {
			$section['title'] = $row['body'];
			$section['hpos'] = $row['hpos'];
			twfy_debug("DATAMODEL" , "epobjectid " . htmlentities($row['epobject_id']));
		} elseif ($row['htype'] == '11') {
			$subsection_title = $row['body'];
			$section['hpos'] = $row['hpos'];
		} elseif ($row['htype'] == '13') {
			// DEBATE PROCEDURAL.
			
			$stripecount++;
			$style = $stripecount % 2 == 0 ? '1' : '2';	
			
			if (!isset($section['first_gid'])) $section['first_gid'] = $row['gid'];
	
			$video_content = '';
			if ($first_video_displayed == 0 && $row['video_status']&4 && !($row['video_status']&8)) {
				$video_content = video_sidebar($row, $section, $speeches, $data['info']['major']);
				$first_video_displayed = true;
			}
			if ($video_content == '' && $first_speech_displayed == 0 && $row['video_status']&1 && !($row['video_status']&12)) {
				$video_content = video_advert($row, $data['info']['major']);
				$first_speech_displayed = true;
			}

			$id = '';
			if ($this_page != 'debate') $id = 'g' . gid_to_anchor($row['gid']);
			$PAGE->stripe_start('procedural-'.$style, $id);
			if ($id) echo '<a name="', $id, '"></a>';
			
			echo $row['body'];
			
			context_link($row);
			$action_links = array( 'Link to this: <a href="' . $row['commentsurl'] . '" class="link">Individually</a> | <a href="' . $row['listurl'] . '">In context</a>' );
			$sidebarhtml = generate_commentteaser(&$row, $data['info']['major'], $action_links);
			
			$PAGE->stripe_end(array(
				array (
					'type' => 'html',
					'content' => $video_content
				),
				array (
					'type' => 'html',
					'content' => $sidebarhtml
				)
			));

	

		} elseif ( $row['htype'] == '12') {
			// A STANDARD SPEECH OR WRANS TEXT.
		
			$stripecount++;
			$style = $stripecount % 2 == 0 ? '1' : '2';
			
			if (!isset($section['first_gid'])) $section['first_gid'] = $row['gid'];
	
			$video_content = '';
			if ($first_video_displayed == 0 && $row['video_status']&4 && !($row['video_status']&8)) {
				$video_content = video_sidebar($row, $section, $speeches, $data['info']['major']);
				$first_video_displayed = true;
			}
			if ($video_content == '' && $first_speech_displayed == 0 && $row['video_status']&1 && !($row['video_status']&12)) {
				$video_content = video_advert($row, $data['info']['major']);
				$first_speech_displayed = true;
			}
	
			// If this item is at a new time, then print the time.
			if (substr($row['htime'],0,5) != $timetracker && $row['htime'] != "00:00:00" && $stripecount != 1) {
                $style = $stripecount++;
				$PAGE->stripe_start('time');

				echo "\t\t\t\t" . '<abbr class="datetime" title="' . $row['hdate'] . $row['htime'] . '">' . format_time($row['htime'], TIMEFORMAT) . "</abbr>\n";

				// Set the timetracker to the current time
				$timetracker = substr($row['htime'],0,5);

				$stripecount++;
				$style = $stripecount % 2 == 0 ? '1' : '2';
				$PAGE->stripe_end();				
			}


			if (isset($row['speaker']) && ( (isset($row['speaker']['member_id']) && isset($data['info']['member_id']) && $row['speaker']['member_id'] == $data['info']['member_id']) || (isset($row['speaker']['person_id']) && isset($data['info']['person_id']) && $row['speaker']['person_id'] == $data['info']['person_id']) ) ) {
				$style .= '-on';
			}
			
			// gid_to_anchor() is in utility.php
			$id = '';
			if ($this_page != 'debate') $id = 'g' . gid_to_anchor($row['gid']);

			$PAGE->stripe_start($style, $id, "speech ");
			if ($id) echo '<a name="', $id, '"></a>';

            // Used for action links (link, source, watch etc)
            $action_links = array();
                
			if (isset($row['speaker']) && count($row['speaker']) > 0) {
			    
			  // We have a speaker to print.
				$speaker = $row['speaker'];
				$speakername = ucfirst(member_full_name($speaker['house'], $speaker['title'], $speaker['first_name'], $speaker['last_name'], $speaker['constituency']));

				//speaker photo
				$missing_photo_type = 'general';
				if($data['info']['major'] == 101){
				    $missing_photo_type = "lord";
			    }
				list($image,$sz) = find_rep_image($speaker['person_id'], true, $missing_photo_type);				

				echo '<a class="speakerimage" id="speakerimage_' . $row['epobject_id'] . '" href="', $speaker['url'], '" title="See more information about ', $speakername, '" onmouseover="showPersonLinks(' . $row['epobject_id'] . ')" >';
				echo '<span><img src="', $image, '" alt="Photo of ', $speakername, '"';
				/*
				if (get_http_var('partycolours')) {
					echo ' style="border: 3px solid ', party_to_colour($speaker['party']), ';"';
				}
				*/
				echo '></span></a>';

				//speaker links
				echo '<div class="personinfo" id="personinfo_' . $row['epobject_id']  . '"><ul>';
				echo '<li><a href="/alert/?only=1&pid=' . $speaker['person_id'] . '">Email me when ' . $speakername .  ' speaks</a></li>';
				if($data['info']['major'] == 101 || $data['info']['major'] == 1){
				    echo '<li><a href="' . $speaker['url'] . '#votingrecord">View voting record</a></li>';												    
			    }
				echo '<li><a href="' . $speaker['url'] . '#hansard">Most recent appearances</a></li>';				
				echo '<li><a href="' . $speaker['url'] . '#numbers">Numerology</a></li>';								
				echo '<li><strong><a href="' . $speaker['url'] . '">Full profile ...</a></strong></li>';												
				echo '</ul></div>';
									
				// print the speaker name and details
				echo '<p class="speaker ' . strtolower($speaker['party']) . '"><a href="', $speaker['url'], '" title="See more information about ', $speakername, '">';

				echo '<strong>', $speakername, '</strong></a> <small>';
				$desc = '';
				if (isset($speaker['office'])) {
					$desc = $speaker['office'][0]['pretty'];
					#if (strpos($desc, 'PPS')!==false) $desc .= '; ';
					$desc .= '; ';
				}
				
				# Try always showing constituency/party too
				#if (!$desc || strpos($desc, 'PPS')!==false) {
					if ($speaker['house'] == 1 && $speaker['party'] != 'Speaker' && $speaker['party'] != 'Deputy Speaker' && $speaker['constituency']) {
						$desc .= $speaker['constituency'];
						if ($speaker['party']) $desc .= ', ';
					}
					if (get_http_var('wordcolours')) {
						$desc .= '<span style="color: '.party_to_colour($speaker['party']).'">';
					}
					$desc .= htmlentities($speaker['party']);
					if (get_http_var('wordcolours')) {
						$desc .= '</span>';
					}
				#}
				
				//print $desc
				if ($desc) print "($desc)";

				// show the question number (Scottish Written Answers only)
				if ($data['info']['major'] == 8 && preg_match('#\d{4}-\d\d-\d\d\.(.*?)\.q#', $row['gid'], $m)) {
					# Scottish Wrans only
					print " | Question $m[1]";
				}
				
				echo "</small>";
			}

            // link
			if ($hansardmajors[$data['info']['major']]['type']=='debate' && $this_page == $hansardmajors[$data['info']['major']]['page_all']) {
                $action_links["link"] = 'Link to this: <a href="' . $row['commentsurl'] . '" class="link">Individually</a> | <a href="' . $row['listurl'] . '">In context</a>';
			}
				
			//source
			if (isset($row['source_url']) && $row['source_url'] != '') {
				$source_title = '';
				$major = $data['info']['major'];
				if ($major==1 || $major==2 || (($major==3 || $major==4) && isset($row['speaker']['house'])) || $major==101 || $major==6) {
					$source_title = 'Citation: ';
					if ($major==1 || $major==2) {
						$source_title .= 'HC';
					} elseif ($major==3 || $major==4) {
						if ($row['speaker']['house']==1) {
						    $source_title .= 'HC';
						} else {
						    $source_title .= 'HL';
						}
					} elseif ($major==6) {
                        $source_title .= $section['title'];
					} else {
						$source_title .= 'HL';
					}
					$source_title .= ' Deb, ' . format_date($data['info']['date'], LONGDATEFORMAT) . ', c' . $row['colnum'];
					if ($major==2) {
						$source_title .= 'WH';
					} elseif ($major==3) {
						$source_title .= 'W';
					} elseif ($major==4) {
						$source_title .= 'WS';
					} 
				}

                $text = "Hansard source";
				if ($hansardmajors[$data['info']['major']]['location']=='Scotland'){        
				    $text = 'Official Report source';
				}
                $action_links["source"] = '<a href="' . $row['source_url'] . '" class="source">' . $text . "</a>";
                if ($source_title) {
                    $action_links['source'] .= " ($source_title)";
                }
			}

            //video
            if ($data['info']['major'] == 1 && $this_page != 'debate') { # Commons debates only
				if ($row['video_status']&4) {
    				$action_links["video"] = '<a href="' . $row['commentsurl'] . '" class="watch" onclick="return moveVideo(\'debate/' . $row['gid'] . '\');">Watch this</a>';
				} elseif (!$video_content && $row['video_status']&1 && !($row['video_status']&8)) {
					$gid_type = $data['info']['major'] == 1 ? 'debate' : 'lords';
				    $action_links["video"] = '<a href="/video/?from=debate&amp;gid=' . $gid_type . '/' . $row['gid'] . '" class="timestamp">Video match this</a>';
				}
			}

			$body = $row['body'];

			# XXX: Need to cope with links within links a better way/ higher level :-/
			$body = preg_replace('#<phrase class="honfriend" id="uk.org.publicwhip/member/(\d+)" name="([^"]*?)">(.*?\s*\((.*?)\))</phrase>#e', '\'<a href="/mp/?m=$1" title="Our page on $2 - \\\'\' . preg_replace("#</?(a|span)[^>]*>#", "", \'$3\') . \'\\\'">\' . preg_replace("#</?a[^>]*>#", "", \'$4\') . \'</a>\'', $body);

			if ($hansardmajors[$data['info']['major']]['location'] == 'Scotland') {
				$body = preg_replace('# (S\d[O0WF]-\d+)[, ]#', ' <a href="/spwrans/?spid=$1">$1</a> ', $body);
				$body = preg_replace('#<citation id="uk\.org\.publicwhip/(.*?)/(.*?)">\[(.*?)\]</citation>#e',
					"'[<a href=\"/' . ('$1'=='spor'?'sp/?g':('$1'=='spwa'?'spwrans/?':'debate/?')) . 'id=$2' . '\">$3</a>]'",
					$body);
				$body = str_replace('href="../../../', 'href="http://www.scottish.parliament.uk/', $body);
			}

			$body = preg_replace('#<phrase class="offrep" id="(.*?)/(\d+)-(\d+)-(\d+)\.(.*?)">(.*?)</phrase>#e', '\'<a href="/search/?pop=1&s=date:$2$3$4+column:$5+section:$1">\' . str_replace("Official Report", "Hansard", \'$6\') . \'</a>\'', $body);
			#$body = preg_replace('#<phrase class="offrep" id="((.*?)/(\d+)-(\d+)-(\d+)\.(.*?))">(.*?)</phrase>#e', "\"<a href='/search/?pop=1&amp;s=date:$3$4$5+column:$6+section:$2&amp;match=$1'>\" . str_replace('Official Report', 'Hansard', '$7') . '</a>'", $body);

			$body = preg_replace('#\[Official Report, (.*?)[,;] (.*?) (\d+MC)\.\]#', '<big>[This section has been corrected on $1, column $3 &mdash; read correction]</big>', $body);
			$body = preg_replace('#(<p[^>]*class="[^"]*?)("[^>]*)pwmotiontext="moved"#', '$1 moved$2', $body);
			$body = str_replace('pwmotiontext="moved"', 'class="moved"', $body);
			$body = str_replace('<a href="h', '<a rel="nofollow" href="h', $body); # As even sites in Hansard lapse and become spam-sites

            preg_match_all('#<p[^>]* pwmotiontext="yes">.*?</p>#s', $body, $m);
            foreach ($m as $rrr) {
                $begtomove = preg_replace('#(That this House |; )(\w+)#', '\1<br><strong>\2</strong>', $rrr);
                $body = str_replace($rrr, $begtomove, $body);
            }

			echo str_replace(array('<br/>', '</p><p'), array('</p> <p>', '</p> <p'), $body); # NN4 font size bug
			
			context_link($row);

			$sidebarhtml = '';
			
			if (isset($row['votes']) && (!strstr($row['gid'], 'q'))) {
				$sidebarhtml .= generate_votes ( $row['votes'], $row['major'], $row['epobject_id'], $row['gid'] );
			}

			if ($hansardmajors[$data['info']['major']]['type']=='debate' && $this_page == $hansardmajors[$data['info']['major']]['page_all']) {
                // Build the 'Add an annotation' link.
                if (!$THEUSER->isloggedin()) {
                    $URL = new URL('userprompt');
                    $URL->insert(array('ret'=>$row['commentsurl']));
                    $commentsurl = $URL->generate();
                } else {
                    $commentsurl = $row['commentsurl'];
                }
                
                $action_links['add'] = '<a class="annotate" href="' . $commentsurl . '#addcomment">Add an annotation</a> <small>(e.g. more info, blog post or wikipedia article)</small>';
            }
            
# Do the logic for this in the function; plus why shouldn't
# you be able to comment on speeches with unknown speakers?
#			if (($hansardmajors[$data['info']['major']]['type'] == 'debate') && isset($row['speaker']) && count($row['speaker']) > 0) {
			$sidebarhtml .= generate_commentteaser(&$row, $data['info']['major'], $action_links);
#			}
			
			if (isset($row['mentions'])) {
				$sidebarhtml .= get_question_mentions_html($row['mentions']);
			}

			$PAGE->stripe_end(array(
				array (
					'type' => 'html',
					'content' => $video_content
				),
				array (
					'type' => 'html',
					'content' => $sidebarhtml
				),
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
		if($subsection_title != '' && $subsection_title != "&nbsp;") {
			print "<h1>$subsection_title<br><span>$section[title]</span></h1>";
		} else {
			print "<h1>$section[title]</h1>";
		}
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
					$plural = $row['totalcomments'] == 1 ? 'annotation' : 'annotations';
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
				print "<br>\n\t\t\t\t\t<span class=\"excerpt-debates\">" . trim_characters($row['excerpt'], 0, 200) . "</span>";
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



if ($this_page == 'debates' || $this_page == 'whall' || $this_page == 'lordsdebates' || $this_page == 'nidebates') {
	// Previous / Index / Next links, if any.
	
	$PAGE->stripe_start('foot');
	echo '&nbsp;<br>';
//	$PAGE->nextprevlinks();
	?>&nbsp;<br>&nbsp;<?php
	$PAGE->stripe_end(array(
		array (
			'type' => 'nextprev'
		)
	));
}


function context_link (&$row) {
	global $this_page, $hansardmajors;
	
	if ($hansardmajors[$row['major']]['type'] == 'debate' && $hansardmajors[$row['major']]['page']==$this_page) {
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
function generate_commentteaser (&$row, $major, $action_links) {
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
	
	global $this_page, $hansardmajors;
	
	$html = '';
	
        //Action links
    if (isset($action_links)) {
        $html .= '<ul>';
            foreach ($action_links as $action_link) {
                $html .= "<li>$action_link</li>\n";
            }
        $html .= '</ul>';        
    }

	if ($hansardmajors[$major]['type'] == 'debate' && $hansardmajors[$major]['page_all']==$this_page) {

		//Add existing annotations
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
					$plural = $morecount == 1 ? 'annotation' : 'annotations';
					$linktext = "Read $morecount more $plural";
				}
				
			} else {
				// This comment needs trimming.
				$commentbody = trim_characters($comment['body'], 0, $targetsize, 1000);
				if ($row['totalcomments'] > 1) {
					$morecount = $row['totalcomments'] - 1;
					$plural = $morecount == 1 ? 'annotation' : 'annotations';
					$linktext = "Continue reading (and $morecount more $plural)";
				} else {
					$linktext = 'Continue reading';
				}
			}

			$html .= '<blockquote><p>' . prepare_comment_for_display($commentbody) . '</p><cite>Submitted by ' . htmlentities($comment['username']) . '</cite></small></blockquote>' ;
			
			if (isset($linktext)) {
				$html .= ' <a class="morecomments" href="' . $row['commentsurl'] . '#c' . $comment['comment_id'] . '" title="See any annotations posted about this">' . $linktext . '</a>';
			}
			
		}


	}
	$html = "\t\t\t\t" . '<div class="comment-teaser">' . $html . "</div>\n";
	
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

	if (($major == 3 || $major == 8) && ($votelinks_so_far > 0 || preg_match('#r#', $gid) ) ) { # XXX
		// Wrans.
		$yesvotes = $votes['user']['yes'] + $votes['anon']['yes'];
		$novotes = $votes['user']['no'] + $votes['anon']['no'];
		
		$yesplural = $yesvotes == 1 ? 'person thinks' : 'people think';
		$noplural = $novotes == 1 ? 'person thinks' : 'people think';
		
		$html .= '<h4>Does this answer the above question?</h4>';
		
		$html .= '<div class="blockbody"><span class="wransvote"><a rel="nofollow" href="' . $VOTEURL->generate() . '" title="Rate this as answering the question">Yes!</a> ' . $yesvotes . ' ' . $yesplural . ' so!<br>';

		$VOTEURL->insert(array('v'=>'0'));
		
		$html .= '<a rel="nofollow" href="' . $VOTEURL->generate() . '" title="Rate this as NOT answering the question">No!</a> ' . $novotes . ' ' . $noplural . ' not!</span>';

        $html .= "<p>Would you like to <strong>ask a question like this yourself</strong>? Use our <a href=\"http://www.whatdotheyknow.com\">Freedom of Information site</a>.</p></div>";

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
	$html = "\t\t\t\t<div class=\"block question\">$html</div>\n";
	return $html;
	
}

function get_question_mentions_html($row_data) {
	if( count($row_data) == 0 ) {
		return '';
	}
	$result = '<ul class="question-mentions">';
	$nrows = count($row_data);
	$last_date = NULL;
	$first_difference_output = TRUE;
	// Keep the references until after the history that's in a timeline:
	$references = array();
	for( $i = 0; $i < $nrows; $i++ ) {
		$row = $row_data[$i];
		if( ! $row["date"] ) {
			// If this mention isn't associated with a date, the difference won't be interesting.
			$last_date = NULL;
		}
		$description = '';
		if ($last_date && ($last_date != $row["date"])) {
			// Calculate how long the gap was in days:
			$daysdiff = (integer)((strtotime($row["date"]) - strtotime($last_date)) / 86400);
			$daysstring = ($daysdiff == 1) ? "day" : "days";
			$further = "";
			if( $first_difference_output ) {
				$first_difference_output = FALSE;
			} else {
				$further = " a further";
			}
			$description = "\n<span class=\"question-mention-gap\">After$further $daysdiff $daysstring,</span> ";
		}
		$reference = FALSE;
		$inner = "BUG: Unknown mention type $row[type]";
		$date = format_date($row['date'], SHORTDATEFORMAT);
		switch ($row["type"]) {
			case 1:
				$inner = "Mentioned in <a href=\"$row[url]\">today's business on $date</a>";
				break;
			case 2:
				$inner = "Mentioned in <a href=\"$row[url]\">tabled oral questions on $date</a>";
				break;
			case 3:
				$inner = "Mentioned in <a href=\"$row[url]\">tabled written questions on $date</a>";
				break;
			case 4:
				if( preg_match('/^uk.org.publicwhip\/spq\/(.*)$/',$row['gid'],$m) ) {
					$URL = new URL("spwrans");
					$URL->insert( array('spid' => $m[1]) );
					$relative_url = $URL->generate("none");
					$inner = "Given a <a href=\"$relative_url\">written answer on $date</a>";
				}
				break;
			case 5:
				$inner = "Given a holding answer on $date";
				break;
			case 6:
				if( preg_match('/^uk.org.publicwhip\/spor\/(.*)$/',$row['mentioned_gid'],$m) ) {
					$URL = new URL("spdebates");
					$URL->insert( array('id' => $m[1]) );
					$relative_url = $URL->generate("none");
					$inner = "<a href=\"$relative_url\">Asked in parliament on $date</a>";
				}
				break;
			case 7:
				if( preg_match('/^uk.org.publicwhip\/spq\/(.*)$/',$row['mentioned_gid'],$m) ) {
					$referencing_spid = $m[1];
					$URL = new URL("spwrans");
					$URL->insert( array('spid' => $referencing_spid) );
					$relative_url = $URL->generate("none");
					$inner = "Referenced in <a href=\"$relative_url\">question $referencing_spid</a>";
					$reference = TRUE;
				}
				break;
		}
		if( $reference ) {
			$references[] = "\n<li>$inner.";
		} else {
			$result .= "\n<li>$description$inner.</span>";
			$last_date = $row["date"];
		}
	}
	foreach ($references as $reference_span) {
		$result .= $reference_span;
	}
	$result .= '</ul>';
	return $result;
} 

function video_sidebar($row, $section, $count, $major) {
	include_once INCLUDESPATH . 'easyparliament/video.php';
	$db = new ParlDB;
	if ($major == 1) {
		$gid_type = 'debate';
	} elseif ($major == 101) {
		$gid_type = 'lords';
	}
	$vq = $db->query("select id,adate,atime from video_timestamps where gid='uk.org.publicwhip/$gid_type/$row[gid]' and (user_id!=-1 or user_id is null) and deleted=0 order by (user_id is null) limit 1");
	$ts_id = $vq->field(0, 'id'); if (!$ts_id) $ts_id='*';
	$adate = $vq->field(0, 'adate');
	$time = $vq->field(0, 'atime');
	$videodb = video_db_connect();
	if (!$videodb) return '';
	$video = video_from_timestamp($videodb, $adate, $time);
	$start = $video['offset'];
	$out = '';
	if ($count > 1) {
		$out .= '<div id="video_wrap"><div style="position:relative">';
		if ($row['gid'] != $section['first_gid']) {
			$out .= '<p style="margin:0">This video starts around ' . ($row['hpos']-$section['hpos']) . ' speeches in (<a href="#g' . gid_to_anchor($row['gid']) . '">move there in text</a>)</p>';
		}
	}
	$out .= video_object($video['id'], $start, "$gid_type/$row[gid]");
	$flashvars = 'gid=' . "$gid_type/$row[gid]" . '&amp;file=' . $video['id'] . '&amp;start=' . $start;
	$out .= "<br><b>Add this video to another site:</b><br><input readonly onclick='this.focus();this.select();' type='text' name='embed' size='40' value=\"<embed src='http://www.theyworkforyou.com/video/parlvid.swf' width='320' height='230' allowfullscreen='true' allowscriptaccess='always' flashvars='$flashvars'></embed>\"><br><small>(copy and paste the above)</small>";
	$out .= "<p style='margin-bottom:0'>Is this not the right video? <a href='mailto:" . str_replace('@', '&#64;', CONTACTEMAIL) . "?subject=Incorrect%20video,%20id%20$row[gid];$video[id];$ts_id'>Let us know</a></p>";
	if ($count > 1) {
		$out .= '<p style="position:absolute;bottom:0;right:0;margin:0"><a href="" onclick="return showVideo();">Hide</a></p>';
		$out .= '</div></div>';
		$out .= '<div id="video_show" style="display:none;position:fixed;bottom:5px;right:5px;border:solid 1px #666666; background-color: #eeeeee; padding: 4px;">
<p style="margin:0"><a href="" onclick="return hideVideo();">Show video</a></p></div>';
	}
	return $out;
}

function video_advert($row, $major) {
	if ($major == 1) {
		$gid_type = 'debate';
	} elseif ($major == 101) {
		$gid_type = 'lords';
	}
	return '
<div style="border:solid 1px #9999ff; background-color: #ccccff; padding: 4px; text-align: center;
background-image: url(\'/images/video-x-generic.png\'); background-repeat: no-repeat; padding-left: 40px;
background-position: 0 2px; margin-bottom: 1em;">
Help us <a href="/video/?from=debate&amp;gid=' . $gid_type . '/' . $row['gid'] . '">match the video for this speech</a>
to get the right video playing here
</div>
';
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
