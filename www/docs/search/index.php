<?php
# vim:sw=4:ts=4:et:nowrap

include_once "../../includes/easyparliament/init.php";
include_once INCLUDESPATH."easyparliament/member.php";
include_once INCLUDESPATH."easyparliament/glossary.php";

// From http://cvs.sourceforge.net/viewcvs.py/publicwhip/publicwhip/website/
include_once INCLUDESPATH."postcode.inc";

if (get_http_var('s') != '' || get_http_var('pid') != '') {

    if (get_http_var('pid') == 16407) {
        header('Location: /search/?pid=10133');
        exit;
    }

	// We're searching for something.
	
	$this_page = 'search';	

	$searchstring = trim(get_http_var('s'));
	$searchstring = filter_user_input($searchstring, 'strict');

    $time = parse_date($searchstring);
    if ($time['iso']) {
        header('Location: /hansard/?d=' . $time['iso']);
        exit;
    }

	$searchspeaker = trim(get_http_var('pid'));
	if ($searchspeaker) {
		$searchstring .= ($searchstring?' ':'') . 'speaker:' . $searchspeaker;
    }
	$searchmajor = trim(get_http_var('section'));
	if (!$searchmajor) {
        // Legacy URLs used maj
		$searchmajor = trim(get_http_var('maj'));
	}
    if ($searchmajor) {
        $searchstring .= " section:" . $searchmajor;
    }
   	$searchgroupby = trim(get_http_var('groupby'));
    if ($searchgroupby) {
        $searchstring .= " groupby:" . $searchgroupby;
    }

    // We have only one of these, rather than one in HANSARDLIST also
    global $SEARCHENGINE;

    if (get_http_var('o')=='p') {
        $wtt = get_http_var('wtt');
        $SEARCHENGINE = new SEARCHENGINE($searchstring);
        $pagetitle = $SEARCHENGINE->query_description_short();
        if ($wtt) {
            $pagetitle = 'League table of Lords who say ' . $pagetitle;
        } else {
            $pagetitle = 'Who says ' . $pagetitle . ' the most?';
        }
        $DATA->set_page_metadata($this_page, 'title', $pagetitle);
        $PAGE->page_start();
        $PAGE->stripe_start();
    	$PAGE->search_form();
        $SEARCHENGINE = new SEARCHENGINE($searchstring . ' groupby:speech');
        $count = $SEARCHENGINE->run_count();
        if ($count <= 0) {
            print '<p>There were no results.</p>';
            $PAGE->page_end();
            return;
        }
        $sort_order = 'date';
        $SEARCHENGINE->run_search(0, 10000, 'date');
        $gids = $SEARCHENGINE->get_gids();
        if (count($gids) <= 0) {
            print '<p>There were no results.</p>';
            $PAGE->page_end();
            return;
        }

        $q_house = '';
        if (ctype_digit(get_http_var('house')))
            $q_house = get_http_var('house');

        $speakers = array();
        $big_list = join('","', $gids);
        $db = new ParlDB;
        $q = $db->query('SELECT gid,speaker_id,hdate FROM hansard WHERE gid IN ("' . $big_list . '")');
        print '<!-- Counts: ' . count($gids) . ' vs ' . $q->rows() . ' -->';
        for ($n=0; $n<$q->rows(); $n++) {
            $gid = $q->field($n, 'gid');
            $speaker_id = $q->field($n, 'speaker_id');
            $hdate = $q->field($n, 'hdate');
            if (!isset($speakers[$speaker_id])) {
                $speakers[$speaker_id] = 0;
                $maxdate[$speaker_id] = '1001-01-01';
                $mindate[$speaker_id] = '9999-12-31';
            }
            $speakers[$speaker_id]++;
            if ($hdate < $mindate[$speaker_id]) $mindate[$speaker_id] = $hdate;
            if ($hdate > $maxdate[$speaker_id]) $maxdate[$speaker_id] = $hdate;
        }
        if (count($speakers)) {
            $speaker_ids = join(',', array_keys($speakers));
            $q = $db->query('SELECT member_id, person_id, title,first_name,last_name,constituency,house,party,
                                moffice_id, dept, position, from_date, to_date
                            FROM member LEFT JOIN moffice ON member.person_id = moffice.person
                            WHERE member_id IN (' . $speaker_ids . ')
                            ' . ($q_house ? " AND house=$q_house" : '') . '
                            ORDER BY left_house DESC');
            for ($n=0; $n<$q->rows(); $n++) {
                $mid = $q->field($n, 'member_id');
                if (!isset($pids[$mid])) {
                    $title = $q->field($n, 'title');
                    $first = $q->field($n, 'first_name');
                    $last = $q->field($n, 'last_name');
                    $cons = $q->field($n, 'constituency');
                    $house = $q->field($n, 'house');
                    $party = $q->field($n, 'party');
                    $full_name = ucfirst(member_full_name($house, $title, $first, $last, $cons));
                    $pid = $q->field($n, 'person_id');
                    $pids[$mid] = $pid;
                    $houses[$pid] = $house;
                }
                $dept = $q->field($n, 'dept');
                $posn = $q->field($n, 'position');
                $moffice_id = $q->field($n, 'moffice_id');
                if ($dept && $q->field($n, 'to_date') == '9999-12-31')
                    $office[$pid][$moffice_id] = prettify_office($posn, $dept);
                # $names[$mid] = '<a href="/' . ($house==1?'mp':'peer') . '/?m=' . $mid . '">' . $full_name . ($house==1?' MP':'') . '</a>';
                if (!isset($names[$pid])) {
                    $names[$pid] = $full_name . ($house==1?' MP':'');
                    $parties[$pid] = $party;
                }
            }
        }
        $pids[0] = 0;
        $parties[0] = '';
        $names[0] = 'Headings, procedural text, etc.';
        $counts = array();
        $party_count = array();
        foreach ($speakers as $speaker_id => $count) {
            if (!isset($pids[$speaker_id])) continue;
            $pid = $pids[$speaker_id];
            if (!isset($counts[$pid])) {
                $counts[$pid] = 0;
                $pmaxdate[$pid] = '1001-01-01';
                $pmindate[$pid] = '9999-12-31';
            }
            if (!isset($party_count[$parties[$pid]]))
                $party_count[$parties[$pid]] = 0;
            $counts[$pid] += $count;
            $party_count[$parties[$pid]] += $count;
            if ($mindate[$speaker_id] < $pmindate[$pid]) $pmindate[$pid] = $mindate[$speaker_id];
            if ($maxdate[$speaker_id] > $pmaxdate[$pid]) $pmaxdate[$pid] = $maxdate[$speaker_id];
        }
        arsort($counts);
        arsort($party_count);
        if (!count($counts)) {
            print '<p>There were no results.</p>';
            $PAGE->page_end();
            return;
        }
        if (count($gids) == 10000) {
            print '<p><em>This service runs on a maximum number of 10,000 results, to conserve memory</em></p>';
        }
        print "\n\n<!-- ";
        foreach ($party_count as $party => $count) {
            print "$party:$count<br>";
        }
        print " -->\n\n";
        if ($wtt) { ?>
<p><strong><big>Now, try reading what a couple of these Lords are saying,
to help you find someone appropriate. When you've found someone,
hit the "I want to write to this Lord" button on their results page
to go back to WriteToThem.
</big></strong></p>
<?      }
?>
<p>Please note that this search is only for the exact word/phrase entered.
For example, putting in 'autism' won't return results for 'autistic spectrum disorder',
you will have to search for it separately.</p>
<table><tr><th>Number of occurences</th><th><?

if ($wtt) print 'Speaker';
else { ?>Table includes - <?

$URL = new URL($this_page);
$url_l = $URL->generate('html', array('house'=>2));
$url_c = $URL->generate('html', array('house'=>1));
$URL->remove('house');
$url_b = $URL->generate();
if ($q_house==1) {
    print 'MPs | <a href="' . $url_l . '">Lords</a> | <a href="' . $url_b . '">Both</a>';
} elseif ($q_house==2) {
    print '<a href="' . $url_c . '">MPs</a> | Lords | <a href="' . $url_b . '">Both</a>';
} else {
    print '<a href="' . $url_c . '">MPs</a> | <a href="' . $url_l . '">Lords</a> | Both';
}

} ?></th><th>Date range</th></tr>
<?
        foreach ($counts as $pid => $count) {
            print '<tr><td align="center">';
            print $count . '</td><td>';
            if ($pid) {
                if ($houses[$pid]==1) {
                    print '<span style="color:#009900">&bull;</span>';
                } elseif ($houses[$pid]==2) {
                    print '<span style="color:#990000">&bull;</span>';
                }
                print ' <a href="/search/?s='.urlencode($searchstring).'&amp;pid=' . $pid;
                if ($wtt) print '&amp;wtt=2';
                print '">';
            }
            print $names[$pid];
            if ($pid) print '</a>';
            if ($parties[$pid]) print " ($parties[$pid])";
            if (isset($office[$pid]))
                print ' - ' . join('; ', $office[$pid]);
            print '</td> <td>';
            if (format_date($pmindate[$pid], 'M Y') == format_date($pmaxdate[$pid], 'M Y')) {
                print format_date($pmindate[$pid], 'M Y');
            } else {
                print str_replace(' ', '&nbsp;', format_date($pmindate[$pid], 'M Y') . ' &ndash; ' . format_date($pmaxdate[$pid], 'M Y'));
            }
            print '</td></tr>';
        }
        print '</table>';
/*    } elseif (get_http_var('o') == 't') {
        $SEARCHENGINE = new SEARCHENGINE($searchstring);
        $pagetitle = $SEARCHENGINE->query_description_short();
        $pagetitle = 'When is ' . $pagetitle . ' said most in debates?';
        $DATA->set_page_metadata($this_page, 'title', $pagetitle);
        $PAGE->page_start();
        $PAGE->stripe_start();
    	$PAGE->search_form();
        $SEARCHENGINE = new SEARCHENGINE($searchstring . ' groupby:speech section:debates section:whall');
        $count = $SEARCHENGINE->run_count();
        if ($count <= 0) {
            print '<p>There were no results.</p>';
            $PAGE->page_end();
            return;
        }
        $sort_order = 'date';
        $SEARCHENGINE->run_search(0, 10000, 'date');
        $gids = $SEARCHENGINE->get_gids();
        if (count($gids) <= 0) {
            print '<p>There were no results.</p>';
            $PAGE->page_end();
            return;
        }

        $hdates = array();
        $big_list = join('","', $gids);
        $db = new ParlDB;
        $q = $db->query('SELECT hdate FROM hansard WHERE gid IN ("' . $big_list . '")');
        print '<!-- Counts: ' . count($gids) . ' vs ' . $q->rows() . ' -->';
        for ($n=0; $n<$q->rows(); $n++) {
            $hdate = $q->field($n, 'hdate');
            if (!isset($hdates[$hdate]))
                $hdates[$hdate] = 0;
            $hdates[$hdate]++;
        }
        arsort($hdates);
        print '<table><tr><th>No.</th><th>Date</th></tr>';
        foreach ($hdates as $hdate => $count) {
            print '<tr><td>';
            print $count . '</td><td>';
            print '<a href="/hansard/?d=' . $hdate . '">';
            print $hdate;
            print '</a>';
            print '</td></tr>';
        }
        print '</table>';
*/
    } else {

        $SEARCHENGINE = new SEARCHENGINE($searchstring); 
    	$pagetitle = "Search: " . $SEARCHENGINE->query_description_short();
    	$pagenum = get_http_var('p');
    	if (is_numeric($pagenum) && $pagenum > 1) {
    		$pagetitle .= " page $pagenum";
    	}
	
    	$DATA->set_page_metadata($this_page, 'title', $pagetitle);
    	$PAGE->page_start();
    	$PAGE->stripe_start();
    	$PAGE->search_form();
	
        $o = get_http_var('o');
    	$args = array (
    		's' => $searchstring,
    		'p' => $pagenum,
    		'num' => get_http_var('num'),
            'pop' => get_http_var('pop'),
            'o' => ($o=='d' || $o=='r') ? $o : 'd',
    	);
	
    	$LIST = new HANSARDLIST();

        if ($args['s']) {
            $db = $LIST->db;
    	    find_members($args);
        }

        $LIST->display('search', $args);
	
        if ($args['s']) {
            find_constituency($args);
            #        find_users($args);
        	find_glossary_items($args);
            #        find_comments($args);
    	}
    }
} else {
	// No search term. Display help.
	$this_page = 'search_help';
	$PAGE->page_start();
	$PAGE->stripe_start();
	include INCLUDESPATH . 'easyparliament/staticpages/search_help.php';
}

$PAGE->stripe_end(array (
	array (
		'type'		=> 'include',
		'content'	=> 'search'
	)
));
$PAGE->page_end();

function find_comments($args) {
	global $PAGE, $db;
    $commentlist = new COMMENTLIST;    
    $commentlist->display('search', $args);
}

function find_constituency ($args) {
	// We see if the user is searching for a postcode or constituency.
	global $PAGE, $db;

	if ($args['s'] != '') {
        $searchterm = $args['s'];
    } else {
        $PAGE->error_message('No search string');
        return false;
    }

	$constituencies = array();
    $constituency = '';
	$validpostcode = false;

	if (validate_postcode($searchterm)) {
		// Looks like a postcode - can we find the constituency?
		$constituency = postcode_to_constituency($searchterm);
        if ($constituency != '') {
            $validpostcode = true;
        }
	}

	if ($constituency == '' && $searchterm) {
		// No luck so far - let's see if they're searching for a constituency.
		$try = strtolower($searchterm);
		if (normalise_constituency_name($try)) {
			$constituency = normalise_constituency_name($try);
		} else {
            $query = "select distinct
                    (select name from constituency where cons_id = o.cons_id and main_name) as name 
                from constituency AS o where name like '%" . mysql_escape_string($try) . "%'
                and from_date <= date(now()) and date(now()) <= to_date";
            $q = $db->query($query);
            for ($n=0; $n<$q->rows(); $n++) {
                $constituencies[] = $q->field($n, 'name');
            }
        }
	}

    if (count($constituencies)==1) {
        $constituency = $constituencies[0];
    }

	if ($constituency != '') {
		// Got a match, display....
			
		$MEMBER = new MEMBER(array('constituency'=>$constituency));
        $URL = new URL('mp');
        if ($MEMBER->valid) {
            $URL->insert(array('m'=>$MEMBER->member_id()));
            print '<h3>MP for ' . preg_replace("#$searchterm#i", '<span class="hi">$0</span>', $constituency);
            if ($validpostcode) {
                // Display the postcode the user searched for.
                print ' (' . htmlentities(strtoupper($args['s'])) . ')';
            }
            ?></h3>
            
            <p><a href="<?php echo $URL->generate(); ?>"><strong><?php echo htmlentities($MEMBER->first_name()) . ' ' . htmlentities($MEMBER->last_name()); ?></strong></a> (<?php echo $MEMBER->party(); ?>)</p>
    <?php
        }

	} elseif (count($constituencies)) {
        print "<h3>MPs in constituencies matching '".htmlentities($searchterm)."'</h3><ul>";
        foreach ($constituencies as $constituency) {
            $MEMBER = new MEMBER(array('constituency'=>$constituency));
            $URL = new URL('mp');
            if ($MEMBER->valid) {
                $URL->insert(array('m'=>$MEMBER->member_id()));
            }
            print '<li><a href="'.$URL->generate().'"><strong>'.htmlentities($MEMBER->first_name()).' ' .
                htmlentities($MEMBER->last_name()).'</strong></a> (' . preg_replace("#$searchterm#i", '<span class="hi">$0</span>', $constituency) .
                ', '.$MEMBER->party().')</li>';
        }
        print '</ul>';
    }
}

function find_users ($args) {
	// Maybe there'll be a better place to put this at some point...
	global $PAGE, $db;

	if ($args['s'] != '') {
		// $args['s'] should have been tidied up by the time we get here.
		// eg, by doing filter_user_input($s, 'strict');
		$searchstring = $args['s'];
	} else {
		$PAGE->error_message("No search string");
		return false;
	}
	
	$searchwords = explode(' ', $searchstring);
	
	if (count($searchwords) == 1) {
		$where = "(firstname LIKE '%" . addslashes($searchwords[0]) . "%' OR lastname LIKE '%" . addslashes($searchwords[0]) . "%')";
	} else {
		// We don't do anything special if there are more than two search words.
		// And here we're assuming the user's put the names in the right order.
		$where = "(firstname LIKE '%" . addslashes($searchwords[0]) . "%' AND lastname LIKE '%" . addslashes($searchwords[1]) . "%')";
	}

	$q = $db->query("SELECT user_id,
							firstname,
							lastname
					FROM 	users
					WHERE	$where AND confirmed=1
					ORDER BY lastname, firstname, user_id
					");

	if ($q->rows() > 0) {
	
		$URL = new URL('userview');
		$users = array();
		
		for ($n=0; $n<$q->rows(); $n++) {
            $URL->insert(array('u'=>$q->field($n, 'user_id')));
            $members[] = '<a href="' . $URL->generate() . '">' . $q->field($n, 'firstname') . ' ' . $q->field($n, 'lastname') . '</a>';
		}
		?>
	<h3>Users matching '<?php echo htmlentities($searchstring); ?>'</h3> 
	<ul>
	<li><?php print implode("</li>\n\t<li>", $members); ?></li>
	</ul>
<?php	
	}
	
	// We don't display anything if there were no matches.

}

function find_members ($args) {
	// Maybe there'll be a better place to put this at some point...
	global $PAGE, $db, $parties;
	
	if ($args['s'] != '') {
		// $args['s'] should have been tidied up by the time we get here.
		// eg, by doing filter_user_input($s, 'strict');
		$searchstring = $args['s'];
	} else {
		$PAGE->error_message("No search string");
		return false;
	}

	$searchwords = explode(' ', $searchstring);
    foreach ($searchwords as $i=>$searchword) {
        $searchwords[$i] = mysql_real_escape_string(htmlentities($searchword));
        if (!strcasecmp($searchword,'Opik'))
            $searchwords[$i] = '&Ouml;pik';
    }
	if (count($searchwords) == 1) {
		$where = "first_name LIKE '%" . $searchwords[0] . "%' OR last_name LIKE '%" . $searchwords[0] . "%'";
	} elseif (count($searchwords) == 2) {
		// We don't do anything special if there are more than two search words.
		// And here we're assuming the user's put the names in the right order.
		$where = "(first_name LIKE '%" . $searchwords[0] . "%' AND last_name LIKE '%" . $searchwords[1] . "%')";
        $where .= " OR (first_name LIKE '%" . $searchwords[1] . "%' AND last_name LIKE '%" . $searchwords[0] . "%')";
	} else {
		$where = "(first_name LIKE '%" . $searchwords[0].' '.$searchwords[1] . "%' AND last_name LIKE '%" . $searchwords[2] . "%')";
        $where .= " OR (first_name LIKE '%" . $searchwords[0] . "%' AND last_name LIKE '%" . $searchwords[1].' '.$searchwords[2] . "%')";
    }

	$q = $db->query("SELECT person_id,
                            title, first_name, last_name,
							constituency, party,
                            left_house, house
					FROM 	member
					WHERE	($where)
					ORDER BY last_name, first_name, person_id, entered_house desc
					");

	if ($q->rows() > 0) {
	
		$URL1 = new URL('mp');
		$URL2 = new URL('peer');
		$members = array();
		
        $last_pid = -1;
		for ($n=0; $n<$q->rows(); $n++) {
            if ($q->field($n, 'person_id') != $last_pid) {
                $last_pid = $q->field($n, 'person_id');
                if ($q->field($n, 'left_house') != '9999-12-31') {
                    $former = 'formerly ';
                } else {
                    $former = '';
                }
                if ($q->field($n, 'house') == 1) {
                    $URL1->insert(array('pid'=>$last_pid));
                    $s = '<a href="' . $URL1->generate() . '"><strong>';
                    $s .= $q->field($n, 'first_name') . ' ' . $q->field($n, 'last_name') . '</strong></a> (' . $former . $q->field($n, 'constituency') . ', ';
                } else {
                    $URL2->insert(array('pid'=>$last_pid));
                    $s = '<a href="' . $URL2->generate() . '"><strong>';
                    $s .= member_full_name($q->field($n, 'house'), $q->field($n, 'title'), $q->field($n, 'first_name'), $q->field($n, 'last_name'), $q->field($n, 'constituency') );
                    $s .= '</strong></a> (';
                }
                $party = $q->field($n, 'party');
                if (isset($parties[$party]))
                    $party = $parties[$party];
                $s .= $party . ')';
                $members[] = $s;
            }
		}
		?>
	<h3>MPs and Peers matching '<?php echo htmlentities($searchstring); ?>'</h3> 
	<ul>
	<li><?php print implode("</li>\n\t<li>", $members); ?></li>
	</ul>
<?php	
	}
	
	// We don't display anything if there were no matches.

}

// Checks to see if the search term provided has any similar matching entries in the glossary.
// If it does, show links off to them.
function find_glossary_items($args) {
	
	$searchterm = $args['s'];
	$GLOSSARY = new GLOSSARY($args);

	if (isset($GLOSSARY->num_search_matches) && $GLOSSARY->num_search_matches >= 1) {

		// Got a match(es), display....
		$URL = new URL('glossary');
		$URL->insert(array('gl' => ""));
		
		?>
				<h3>Matching glossary terms:</h3> 
				<p><?
		$n = 1;
		foreach($GLOSSARY->search_matches as $glossary_id => $term) {
			$URL->update(array("gl" => $glossary_id)); 
			?><a href="<?php echo $URL->generate(); ?>"><strong><?php echo htmlentities($term['title']); ?></strong></a><?
			if ($n < $GLOSSARY->num_search_matches) {
				print ", ";
			}
			$n++;
		}
		?></p>
<?
	}
}


?>
