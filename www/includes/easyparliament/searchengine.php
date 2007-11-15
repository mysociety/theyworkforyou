<?php
# vim:sw=4:ts=4:et:nowrap

/*
SEARCHENGINE class 2004-05-26
francis@flourish.org

Example usage:

        include_once INCLUDESPATH."easyparliament/searchengine.php";

        $searchengine = new SEARCHENGINE($searchstring);
        $description = $searchengine->query_description();
        $short_description = $searchengine->query_description_short();

        $count = $searchengine->run_count();

        // $first_result begins at 0
        $searchengine->run_search($first_result, $results_per_page);
        $gids = $searchengine->get_gids();
        $relevances = $searchengine->get_relevances();

        $bestpos = $searchengine->position_of_first_word($body);
        $extract = $searchengine->highlight($extract);

*/

include_once INCLUDESPATH . 'dbtypes.php';

global $xapiandb;

class SEARCHENGINE {

	function SEARCHENGINE ($query) {
        if (!defined('XAPIANDB'))
            return null;

		$this->query = $query;
        $this->stemmer = new_stem('english');
        $this->enquire = null; 

        // Any characters other than this are treated as, basically, white space
        // (apart from quotes and minuses, special case below)
        // The colon is in here for prefixes speaker:10043 and so on.
        $this->wordchars = "A-Za-z0-9:";

        // An array of normal words.
        $this->words = array();
        // All quoted phrases, as an (array of (arrays of words in each phrase)).
        $this->phrases = array();
        // Items prefixed with a colon (speaker:10024) as an (array of (name, value))
        $this->prefixed = array();
        // Words you don't want
        $this->excluded = array();
        // Stemmed words // doesn't work yet
        // $this->rough = array();
        
        // Split words up into individual words, and quoted phrases
        preg_match_all('/(' .
            '"|' . # match either a quote, or...
            '(?:(?<![' .$this->wordchars. '])-)?' . # optionally a - (exclude)
            # if at start of word (i.e. not preceded by a word character, in
            # which case it is probably a hyphenated-word)
            '['.$this->wordchars.']+' . # followed by a string of word-characters
            ')/', $query, $all_words);
        if ($all_words) {
            $all_words = $all_words[0];
        } else {
            $all_words = array();
        }
        $in_quote = false;
        foreach ($all_words as $word) {
            if ($word == '"') {
                $in_quote = !$in_quote;
                if ($in_quote) {
                    array_push($this->phrases, array());
                }
                continue;
            }
            if ($word == '') {
                continue;
            }
 
            if (strpos($word, ':') !== false) {
                $items = split(":", strtolower($word));
                $type = $items[0];
                $value = join(":", array_slice($items,1));
                if ($type == "section") {
                    if ($value == "debates" || $value == "debate") $value = 1;
                    elseif ($value == 'whall' || $value == 'westminster' || $value == 'westminhall') $value = 2;
                    elseif ($value == "wrans" || $value == "wran") $value = 3;
                    elseif ($value == 'wms' || $value == 'statements' || $value == 'statement') $value = 4;
                    elseif ($value == 'lordsdebates' || $value == 'lords') $value = 101;
                    elseif ($value == 'ni') $value = 5;
                    elseif ($value == 'pbc' || $value == 'standing') $value = 6;
                    $type = "major";
                }
                if ($type == "groupby") {
                    if ($value == "date" || $value == "day") $value = "day";
                    if ($value == "debates" || $value == "debate" || $value == "department" || $value == "departments" || $value == "dept") $value = "debate";
                    if ($value == "speech" || $value == "speeches") $value = "speech";
                }
                array_push($this->prefixed, array($type, $value));
            } elseif (strpos($word, '-') !== false) {
                array_push($this->excluded, str_replace("-", "", strtolower($word)));
            } /*else if (strpos($word, '~') !== false) {
                array_push($this->rough, str_replace("~", "", strtolower($word)));
            } */ elseif ($in_quote) {
                array_push($this->phrases[count($this->phrases) - 1], strtolower($word));
            } else {
                array_push($this->words, strtolower($word));
            }
        }

        twfy_debug("SEARCH", "words: " . var_export($this->words, true));
        twfy_debug("SEARCH", "phrases: " . var_export($this->phrases, true));
        twfy_debug("SEARCH", "prefixed: " . var_export($this->prefixed, true));
        twfy_debug("SEARCH", "excluded: " . var_export($this->excluded, true));
        // twfy_debug("SEARCH", "rough: " . var_export($this->rough, true));
    }

    function make_phrase($phrasearray) {
        return '"' . join(' ', $phrasearray) . '"';
    }

    function query_description_internal($long) {
    	global $PAGE, $hansardmajors;
    	
        if (!defined('XAPIANDB'))
            return '';

        $description = "";

        if (count($this->words) > 0) {
            if ($long and $description == "") {
                $description .= " containing";
            }
            $description .= " the ". make_plural("word", count($this->words));
            $description .= " '";
            if (count($this->words) > 2) {
                $description .= join("', '", array_slice($this->words, 0, -2));
                $description .= "', '";
                $description .= $this->words[count($this->words)-2] . "', and '" . $this->words[count($this->words)-1];
            } elseif (count($this->words) == 2) {
                $description .= $this->words[0] . "' and '" . $this->words[1];
            } else {
                $description .= $this->words[0];
            }
            $description .= "'";
        }

        if (count($this->phrases) > 0) {
            if ($description == "") {
                if ($long) {
                    $description .= " containing";
                }
            } else {
                $description .= " and";
            }
            $description .= " the ". make_plural("phrase", count($this->phrases)) . " ";
            $description .= join(', ', array_map(array($this, "make_phrase"), $this->phrases));
        }

        if (count($this->excluded) > 0) {
            if (count($this->words) > 0 or count($this->phrases) > 0) {
                $description .= " but not";
            } else {
                $description .= " excluding";
            }
            $description .= " the ". make_plural("word", count($this->excluded));
            $description .= " '" . join(' ', $this->excluded) . "'";
        }

/*        if (count($this->rough) > 0) {
            if ($description == "") {
                if ($long) {
                    $description .= " containing ";
                }
            }
            $description .= " roughly words '" . join(' ', $this->rough) . "'";
        } */

        $major = array(); $speaker = array();
        foreach( $this->prefixed as $items ) {
            if ($items[0] == 'speaker') {
                $member = new MEMBER(array('person_id' => $items[1]));
                $name = $member->full_name();
                $speaker[] = $name;
            } elseif ($items[0] == 'major') {
                if (isset($hansardmajors[$items[1]]['title'])) {
                    $major[] = $hansardmajors[$items[1]]['title'];
                } else {
                    $PAGE->error_message("Unknown major section '$items[1]' ignored");
                }
            } elseif ($items[0] == 'groupby') {
                if ($items[1] == 'day') {
                    $description .= ' grouped by day';
                } elseif ($items[1] == 'debate') {
                    $description .= ' grouped by debate/department';
                } elseif ($items[1] == 'speech') {
                    $description .= ' showing all speeches';
                } else {
                    $PAGE->error_message("Unknown group by '$items[1]' ignored");
                }
            } elseif ($items[0] == "bias") {
                list($weight, $halflife) = explode(":", $items[1]);
                $description .= " bias by $weight halflife $halflife seconds";
            } elseif ($items[0] == 'date') {
                $description .= ' spoken on ' . $items[1];
            } elseif ($items[0] == 'batch') {
                # silently ignore, as description goes in email alerts
                #$description .= ' in search batch ' . $items[1];
            } else {
                $PAGE->error_message("Unknown search prefix '$items[0]' ignored");
            }
        }
        if (sizeof($speaker)) $description .= ' spoken by ' . join(' or ', $speaker);
        if (sizeof($major)) $description .= ' in ' . join(' or ', $major);

        return trim($description);
    }

    // Return textual description of search
    function query_description_short() {
        return $this->query_description_internal(false);
    }

    // Return textual description of search
    function query_description_long() {
        return $this->query_description_internal(true);
    }

    // Return stem of a word
    function stem($word) {
        return stem_stem_word($this->stemmer, strtolower($word));
    }

    // Internal use mainly - you probably want query_description.  Converts
    // parsed form of query that PHP knows into a full textual form again (for
    // feeding to Xapian's queryparser).
    function query_remade() {
        $remade = array();
        foreach( $this->phrases as $phrase ) {
            $remade[] = '"' . join(' ', $phrase) . '"';
        }
        if ($this->words) {
            $remade = array_merge($remade, $this->words);
        }

        $prefixes = array();
        foreach( $this->prefixed as $items ) {
            if (!isset($prefixes[$items[0]])) $prefixes[$items[0]] = array();
            if ($items[0] != 'groupby' && $items[0] != 'bias') {
                $prefixes[$items[0]][] = $items[0] . ':' . $items[1];
            }
        }
        foreach ($prefixes as $prefix) {
            if (count($prefix))
                $remade[] = '(' . join(' OR ', $prefix) . ')';
        }

        $query = trim(join(' AND ', $remade));
        if ($this->excluded) {
            $query .= ' NOT (' . join(' AND ', $this->excluded) . ')';
        }
        // $remade .= ' ' . join(' ', array_map(array($this, "stem"), $this->rough));
        return $query;
    }

    // Perform partial query to get a count of number of matches
    function run_count () {
        if (!defined('XAPIANDB'))
            return null;

		$start = getmicrotime();
        global $xapiandb;
        if (!$xapiandb) {
            $xapiandb = new_database(XAPIANDB);
        }
        if (!$this->enquire) {
            $this->enquire = new_enquire($xapiandb);
        }

        $queryparser = new_queryparser();
        queryparser_set_stemming_strategy($queryparser, QueryParser_STEM_NONE);
        queryparser_set_default_op($queryparser, Query_OP_AND);
        queryparser_add_prefix($queryparser, "speaker", "speaker:");
        queryparser_add_prefix($queryparser, "major", "major:");
        queryparser_add_prefix($queryparser, 'date', 'date:');
        queryparser_add_prefix($queryparser, 'batch', 'batch:');
        twfy_debug("SEARCH", "query remade -- ". $this->query_remade());
        // We rebuild (with query_remade) our query and feed that text string to 
        // the query parser.  This is because the error handling in the query parser
        // is a bit knackered, and we want to be sure our highlighting etc. exactly
        // matches. XXX don't need to do this for more recent Xapians
        $query = queryparser_parse_query($queryparser, $this->query_remade());
        twfy_debug("SEARCH", "queryparser description -- " . query_get_description($query));

        enquire_set_query($this->enquire, $query);

        // Set collapsing and sorting
        global $PAGE;
        $collapsed = false;
        foreach( $this->prefixed as $items ) {
            if ($items[0] == 'groupby') {
                $collapsed = true;
                if ($items[1] == 'day') 
                    enquire_set_collapse_key($this->enquire, 2);
                else if ($items[1] == 'debate')
                    enquire_set_collapse_key($this->enquire, 3);
                else if ($items[1] == 'speech')
                    ; // no collapse key
                else 
                    $PAGE->error_message("Unknown group by '$items[1]' ignored");
            } elseif ($items[0] == 'bias') {
                list($weight, $halflife) = explode(":", $items[1]);
                enquire_set_bias($this->enquire, $weight, intval($halflife));
            } elseif ($items[0] == 'speaker') {
                # Don't do any collapsing if we're searching for a person's speeches
                $collapsed = true;
            }
        }
        // default to grouping by subdebate, i.e. by page
        if (!$collapsed)
            enquire_set_collapse_key($this->enquire, 7);
        
        $matches = enquire_get_mset($this->enquire, 0, 500);
        // Take either: 1) the estimate which is sometimes too large or 2) the
        // size which is sometimes too low (it is limited to the 500 in the line
        // above).  We get the exact mset we need later, according to which page
        // we are on.
        if (mset_size($matches) < 500) {
            $count = mset_size($matches);
        } else {
            $count = mset_get_matches_estimated($matches);
        }
		$duration = getmicrotime() - $start;
		twfy_debug ("SEARCH", "Search count took $duration seconds.");
        return $count;
    }

    // Perform the full search...
    function run_search ($first_result, $results_per_page, $sort_order='relevance') {
		$start = getmicrotime();

        // NOTE: this is to do sort by date
        switch ($sort_order) {
            case 'date':
                enquire_set_sorting($this->enquire, 0, 1);
                break;
            case 'created':
                enquire_set_sorting($this->enquire, 6, 1); 
            default:
                //do nothing, default ordering is by relevance
                break;
        }
        $matches = enquire_get_mset($this->enquire, $first_result, $results_per_page);
		$this->gids = array();
        $this->created = array();
		$this->relevances = array();
        $iter = mset_begin($matches);
        $end = mset_end($matches);
        while (!msetiterator_equals($iter, $end))
        {
            $relevancy =  msetiterator_get_percent($iter);
            $weight =  msetiterator_get_weight($iter);
            $doc = msetiterator_get_document($iter);
            $gid = document_get_data($doc);
            if ($sort_order=='created') {
                array_push($this->created, document_get_value($doc, 6));
            }
			twfy_debug("SEARCH", "gid: $gid relevancy: $relevancy% weight: $weight");
			array_push($this->gids, "uk.org.publicwhip/".$gid);
			array_push($this->relevances, $relevancy);
            msetiterator_next($iter);
        }
		$duration = getmicrotime() - $start;
		twfy_debug ("SEARCH", "Run search took $duration seconds.");
    }	
    // ... use these to get the results
    function get_gids() {
        return $this->gids;
    }
    function get_relevances() {
        return $this->relevances;
    }
    function get_createds() {
        return $this->created;
    }

    // Puts HTML highlighting round all the matching words in the text
    function highlight($body) {
        // Contents will be used in preg_replace() to highlight the search terms.
        $findwords = array();
        $replacewords = array();
            
        foreach ($this->words as $word) {
            if (ctype_digit($word)) {
                array_push($findwords, "/\b($word|" . number_format($word) . ")\b/");
            } else {
    			array_push($findwords, "/\b($word)\b/i");
            }
			array_push($replacewords, "<span class=\"hi\">\\1</span>");
            //array_push($findwords, "/([^>\.\'])\b(" . $word . ")\b([^<\'])/i");
            //array_push($replacewords, "\\1<span class=\"hi\">\\2</span>\\3");
        }
        foreach( $this->phrases as $phrase ) {
            $phrasematch = join($phrase, '[^'.$this->wordchars.']+');
            array_push($findwords, "/\b($phrasematch)\b/i");
            $replacewords[] = "<span class=\"hi\">\\1</span>";
        }
            
        // Highlight search words.
        $hlbody = preg_replace($findwords, $replacewords, $body);
        // Remove any highlighting within HTML.
        $hlbody = preg_replace('#<(a|phrase)\s([^>]*?)<span class="hi">(.*?)</span>([^>]*?)">#', "<\\1 \\2\\3\\4\">", $hlbody);
        $hlbody = preg_replace('#<(/?)<span class="hi">a</span>([^>]*?)>#', "<\\1a\\2>", $hlbody); # XXX Horrible hack
        // Collapse duplicates
        $hlbody = preg_replace("#</span>(\W+)<span class=\"hi\">#", "\\1", $hlbody);

        return $hlbody;
    }

    // Find the position of the first of the search words/phrases in $body.
    function position_of_first_word($body) {
        $lcbody = ' ' . strtolower($body) . ' '; // spaces to make regexp mapping easier
        $pos = -1;

        // look for phrases
        foreach( $this->phrases as $phrase ) {
            $phrasematch = join($phrase, '[^'.$this->wordchars.']+');
            if (preg_match('/([^'.$this->wordchars.']' . $phrasematch . '[^'.$this->wordchars. '])/', $lcbody, $matches))
            {
                $wordpos = strpos( $lcbody, $matches[0] );
                if ($wordpos) {
                   if ( ($wordpos < $pos) || ($pos==-1) ) {
                        $pos = $wordpos;
                    }
                }
            }
        }

        // only look for earlier words if phrases weren't found
        if ($pos == -1) 
        {
            foreach( $this->words as $word ) {
                if (ctype_digit($word)) $word = '(?:'.$word.'|'.number_format($word).')';
                if (preg_match('/([^'.$this->wordchars.']' . $word . '[^'.$this->wordchars. '])/', $lcbody, $matches))
                {
                    $wordpos = strpos( $lcbody, $matches[0] );
                    if ($wordpos) {
                       if ( ($wordpos < $pos) || ($pos==-1) ) {
                            $pos = $wordpos;
                        }
                    }
                }
            }
        }

        if ($pos == -1) {
            $pos = 0;
        }
    
        return $pos;
    }

/*
    old stemming code (does syntax highlighting with stemming, but does it badly)

			$splitextract = preg_split("/([a-zA-Z]+)/", $extract, -1, PREG_SPLIT_DELIM_CAPTURE);
			$hlextract = "";
			foreach( $splitextract as $extractword) {
				$hl = false;
				foreach( $searchstring_stemwords as $word ) {
					if ($word == '') {
						continue;
					}
					
					$matchword  = $searchengine->stem($extractword);
					#print "$extractword : $matchword : $word<br>";
					if ($matchword == $word) {
						$hl = true;
						break;
					}
				}
				if ($hl)
					$hlextract .= "<span class=\"hi\">$extractword</span>";
				else
					$hlextract .= $extractword;
			}
            $hlextract = preg_replace("#</span>\s+<span class=\"hi\">#", " ", $hlextract);


*/

/*    This doesn't work yet as PHP bindings are knackered - the idea is
    to do all parsing here and replace queryparser, so we can do stuff
    how we want more.  e.g. sync highlighting with the queries better */

// Instead we are now parsing in PHP, and rebuilding something to feed to 
// query parser.  Yucky but works.

/*        $querydummy = new_query("dummy");
        $query1 = new_query("ethiopia");
        $query2 = new_query("economic");
        #$query = query_querycombine($querydummy, Query_OP_AND, $query1, $query2);
        $query = new_QueryCombine(Query_OP_AND, $query1, $query2);
#new_QueryCombine
#        $query = query_querycombine($query1, Query_OP_OR, $query1, $query2);
#        foreach ($this->words as $word) {
 #           $query = new_query(Query_OP_OR, $query, new_query($word));
  #      }
        print "description:" . query_get_description($query) . "<br>"; */
}

global $SEARCHENGINE;
$SEARCHENGINE = null;

function search_by_usage($search, $house = 0) {
        $data = array();
        $SEARCHENGINE = new SEARCHENGINE($search);
        $data['pagetitle'] = $SEARCHENGINE->query_description_short();
        $SEARCHENGINE = new SEARCHENGINE($search . ' groupby:speech');
        $count = $SEARCHENGINE->run_count();
        if ($count <= 0) {
            $data['error'] = 'No results';
            return $data;
        }
        $SEARCHENGINE->run_search(0, 10000, 'date');
        $gids = $SEARCHENGINE->get_gids();
        if (count($gids) <= 0) {
            $data['error'] = 'No results';
            return $data;
        }
        if (count($gids) == 10000)
            $data['limit_reached'] = true;

        # Fetch all the speakers of the results, count them up and get min/max date usage
        $speaker_count = array();
        $gids = join('","', $gids);
        $db = new ParlDB;
        $q = $db->query('SELECT gid,speaker_id,hdate FROM hansard WHERE gid IN ("' . $gids . '")');
        for ($n=0; $n<$q->rows(); $n++) {
            $gid = $q->field($n, 'gid');
            $speaker_id = $q->field($n, 'speaker_id'); # This is member ID
            $hdate = $q->field($n, 'hdate');
            if (!isset($speaker_count[$speaker_id])) {
                $speaker_count[$speaker_id] = 0;
                $maxdate[$speaker_id] = '1001-01-01';
                $mindate[$speaker_id] = '9999-12-31';
            }
            $speaker_count[$speaker_id]++;
            if ($hdate < $mindate[$speaker_id]) $mindate[$speaker_id] = $hdate;
            if ($hdate > $maxdate[$speaker_id]) $maxdate[$speaker_id] = $hdate;
        }

        # Fetch details of all the speakers
        if (count($speaker_count)) {
            $speaker_ids = join(',', array_keys($speaker_count));
            $q = $db->query('SELECT member_id, person_id, title,first_name,last_name,constituency,house,party,
                                moffice_id, dept, position, from_date, to_date, left_house
                            FROM member LEFT JOIN moffice ON member.person_id = moffice.person
                            WHERE member_id IN (' . $speaker_ids . ')
                            ' . ($house ? " AND house=$house" : '') . '
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
                    $speakers[$pid]['house'] = $house;
                    $speakers[$pid]['left'] = $q->field($n, 'left_house');
                }
                $dept = $q->field($n, 'dept');
                $posn = $q->field($n, 'position');
                $moffice_id = $q->field($n, 'moffice_id');
                if ($dept && $q->field($n, 'to_date') == '9999-12-31')
                    $speakers[$pid]['office'][$moffice_id] = prettify_office($posn, $dept);
                if (!isset($speakers[$pid]['name'])) {
                    $speakers[$pid]['name'] = $full_name . ($house==1?' MP':'');
                    $speakers[$pid]['party'] = $party;
                }
            }
        }
        $pids[0] = 0;
        $speakers[0] = array('party'=>'', 'name'=>'Headings, procedural text, etc.', 'house'=>0, 'count'=>0);
        $party_count = array();
        $ok = 0;
        foreach ($speaker_count as $speaker_id => $count) {
            if (!isset($pids[$speaker_id])) continue;
            $pid = $pids[$speaker_id];
            if (!isset($speakers[$pid]['pmindate'])) {
                $speakers[$pid]['count'] = 0;
                $speakers[$pid]['pmaxdate'] = '1001-01-01';
                $speakers[$pid]['pmindate'] = '9999-12-31';
                $ok = 1;
            }
            if (!isset($party_count[$speakers[$pid]['party']]))
                $party_count[$speakers[$pid]['party']] = 0;
            $speakers[$pid]['count'] += $count;
            $party_count[$speakers[$pid]['party']] += $count;
            if ($mindate[$speaker_id] < $speakers[$pid]['pmindate']) $speakers[$pid]['pmindate'] = $mindate[$speaker_id];
            if ($maxdate[$speaker_id] > $speakers[$pid]['pmaxdate']) $speakers[$pid]['pmaxdate'] = $maxdate[$speaker_id];
        }
        function sort_by_count($a, $b) {
            if ($a['count'] > $b['count']) return -1;
            if ($a['count'] < $b['count']) return 1;
            return 0;
        }
        if ($speakers[0]['count']==0) unset($speakers[0]);
        uasort($speakers, 'sort_by_count');
        arsort($party_count);
        if (!$ok) {
            $data['error'] = 'No results';
            return $data;
        }

        $data['party_count'] = $party_count;
        $data['speakers'] = $speakers;
        return $data;
}

