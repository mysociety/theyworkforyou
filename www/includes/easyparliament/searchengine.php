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
if (version_compare(phpversion(), '5.0', '>='))
    include_once '/usr/share/php5/xapian.php';

class SEARCHENGINE {

	function SEARCHENGINE ($query) {
        if (!defined('XAPIANDB') || !XAPIANDB)
            return null;

        global $xapiandb, $PAGE, $hansardmajors, $parties;
        if (!$xapiandb) {
            $xapiandb = new XapianDatabase(XAPIANDB);
        }
		$this->query = $query;
        if (!isset($this->stemmer)) $this->stemmer = new XapianStem('english');
        if (!isset($this->enquire)) $this->enquire = new XapianEnquire($xapiandb);
        if (!isset($this->queryparser)) {
            $this->queryparser = new XapianQueryParser();
            $this->datevaluerange = new XapianDateValueRangeProcessor(1);
            $this->queryparser->set_stemmer($this->stemmer);
            $this->queryparser->set_stemming_strategy(XapianQueryParser::STEM_SOME);
            $this->queryparser->set_database($xapiandb);
            $this->queryparser->set_default_op(Query_OP_AND);
            $this->queryparser->add_boolean_prefix('speaker', 'S');
            $this->queryparser->add_boolean_prefix('major', 'M');
            $this->queryparser->add_boolean_prefix('date', 'D');
            $this->queryparser->add_boolean_prefix('batch', 'B');
            $this->queryparser->add_boolean_prefix('segment', 'U');
            $this->queryparser->add_boolean_prefix('department', 'G');
            $this->queryparser->add_boolean_prefix('party', 'P');
            $this->queryparser->add_boolean_prefix('column', 'C');
            $this->queryparser->add_boolean_prefix('gid', 'Q');
            $this->queryparser->add_valuerangeprocessor($this->datevaluerange);
        }

        # Force words to lower case
        $this->query = preg_replace('#(department|party):.+?\b#ie', 'strtolower("$0")', $this->query);

        // Any characters other than this are treated as, basically, white space
        // (apart from quotes and minuses, special case below)
        // The colon is in here for prefixes speaker:10043 and so on.
        $this->wordchars = "A-Za-z0-9,.'&:";
        $this->wordcharsnodigit = "A-Za-z0-9'&";

        // An array of normal words.
        $this->words = array();
        // All quoted phrases, as an (array of (arrays of words in each phrase)).
        $this->phrases = array();
        // Items prefixed with a colon (speaker:10024) as an (array of (name, value))
        $this->prefixed = array();
        
        // Split words up into individual words, and quoted phrases
        preg_match_all('/(' .
            '"|' . # match either a quote, or...
            '(?:(?<![' .$this->wordchars. '])-)?' . # optionally a - (exclude)
            # if at start of word (i.e. not preceded by a word character, in
            # which case it is probably a hyphenated-word)
            '['.$this->wordchars.']+' . # followed by a string of word-characters
            ')/', $this->query, $all_words);
        if ($all_words) {
            $all_words = $all_words[0];
        } else {
            $all_words = array();
        }
        $in_quote = false;
        $from = ''; $to = '';
        foreach ($all_words as $word) {
            if ($word == '"') {
                $in_quote = !$in_quote;
                continue;
            }
            if ($word == '') {
                continue;
            }

            if (strpos($word, ':') !== false) {
                $items = split(":", strtolower($word));
                $type = $items[0];
                $value = strtolower(join(":", array_slice($items,1)));
                if ($type == 'section') {
                    $newv = $value;
                    if ($value == 'debates' || $value == 'debate') $newv = 1;
                    elseif ($value == 'whall' || $value == 'westminster' || $value == 'westminhall') $newv = 2;
                    elseif ($value == 'wrans' || $value == 'wran') $newv = 3;
                    elseif ($value == 'wms' || $value == 'statements' || $value == 'statement') $newv = 4;
                    elseif ($value == 'lordsdebates' || $value == 'lords') $newv = 101;
                    elseif ($value == 'ni' || $value == 'nidebates') $newv = 5;
                    elseif ($value == 'pbc' || $value == 'standing') $newv = 6;
                    elseif ($value == 'sp') $newv = 7;
                    elseif ($value == 'spwrans') $newv = 8;
                    elseif ($value == 'uk') $newv = array(1,2,3,4,6,101);
                    elseif ($value == 'scotland') $newv = array(7,8);
                    if (is_array($newv)) {
                        $newv = 'major:' . join(' major:', $newv);
                    } else {
                        $newv = "major:$newv";
                    }
                    $this->query = str_ireplace("$type:$value", $newv, $this->query);
                } elseif ($type == 'groupby') {
                    $newv = $value;
                    if ($value == 'debates' || $value == 'debate') $newv = 'debate';
                    if ($value == 'speech' || $value == 'speeches') $newv = 'speech';
                    $this->query = str_ireplace("$type:$value", '', $this->query);
                    array_push($this->prefixed, array($type, $newv));
                } elseif ($type == 'from') {
                    $from = $value;
                } elseif ($type == 'to') {
                    $to = $value;
                }
            } elseif (strpos($word, '-') !== false) {
            } elseif ($in_quote) {
            } elseif (strpos($word, '..') !== false) {
            } elseif ($word == 'OR' || $word == 'AND' || $word == 'XOR' || $word == 'NEAR') {
            } else {
                array_push($this->words, strtolower($word));
            }
        }
        if ($from && $to) {
            $this->query = str_ireplace("from:$from", '', $this->query);
            $this->query = str_ireplace("to:$to", '', $this->query);
            $this->query .= " $from..$to";
        } elseif ($from) {
            $this->query = str_ireplace("from:$from", '', $this->query);
            $this->query .= " $from..".date('Ymd');
        } elseif ($to) {
            $this->query = str_ireplace("to:$to", '', $this->query);
            $this->query .= " 19990101..$to";
        }

        twfy_debug("SEARCH", "prefixed: " . var_export($this->prefixed, true));

        twfy_debug("SEARCH", "query -- ". $this->query);
        $query = $this->queryparser->parse_query($this->query,
            XapianQueryParser::FLAG_BOOLEAN | XapianQueryParser::FLAG_PHRASE |
            XapianQueryParser::FLAG_LOVEHATE | XapianQueryParser::FLAG_WILDCARD |
            XapianQueryParser::FLAG_SPELLING_CORRECTION
        );
        $this->enquire->set_query($query);

        # Now parse the parsed query back into a query string, yummy

        $qd = $query->get_description();
        twfy_debug("SEARCH", "queryparser original description -- " . $qd);
        $qd = substr($qd, 14, -1); # Strip Xapian::Query()
        $qd = preg_replace('#:\(.*?\)#', '', $qd); # Don't need pos or weight
        # Date range
        $qd = preg_replace('#VALUE_RANGE 1 (\d+) (\d+)#e', 'preg_replace("#(\d{4})(\d\d)(\d\d)#", "\$3/\$2/\$1", $1)
            . ".." . preg_replace("#(\d{4})(\d\d)(\d\d)#", "\$3/\$2/\$1", $2)', $qd);
        # Replace phrases with the phrase in quotes
        preg_match_all('#\(([^(]*? PHRASE [^(]*?)\)#', $qd, $m);
        foreach ($m[1] as $phrase) {
            $phrase_new = preg_replace('# PHRASE \d+#', '', $phrase);
            $this->phrases[] = preg_split('#\s+#', $phrase_new);
            $qd = str_replace("($phrase)", '"'.$phrase_new.'"', $qd);
        }
        preg_match_all('#\(([^(]*? NEAR [^(]*?)\)#', $qd, $m);
        foreach ($m[1] as $mm) {
            $mmn = preg_replace('# NEAR \d+ #', ' NEAR ', $mm);
            $qd = str_replace("($mm)", "($mmn)", $qd);
        }
        # Awesome regexes to get rid of superfluous matching brackets
        $qd = preg_replace('/( \( ( (?: (?>[^ ()]+) | (?1) ) (?: [ ](?:AND|OR|XOR|FILTER|NEAR[ ]\d+|PHRASE[ ]\d+)[ ] (?: (?>[^ ()]+) | (?1) ) )*  ) \) ) [ ] (FILTER|AND_NOT)/x', '$2 $3', $qd);
        $qd = preg_replace('/(?:FILTER | 0 [ ] \* ) [ ] ( \( ( (?: (?>[^ ()]+) | (?1) ) (?: [ ](?:AND|OR|XOR)[ ] (?: (?>[^ ()]+) | (?1) ) )*  ) \) )/x', '$2', $qd);
        $qd = preg_replace('/(?:FILTER | 0 [ ] \* ) [ ] ( [^()] )/x', '$1', $qd);
        $qd = str_replace('AND ', '', $qd); # AND is the default
        $qd = preg_replace('/^ ( \( ( (?: (?>[^()]+) | (?1) )* ) \) ) $/x', '$2', $qd);
        # Other prefixes
        $qd = preg_replace('#\bU(\d+)\b#', 'segment:$1', $qd);
        $qd = preg_replace('#\bC(\d+)\b#', 'column:$1', $qd);
        $qd = preg_replace('#\bQ(.*?)\b#', 'gid:$1', $qd);
        $qd = preg_replace('#\bP(.*?)\b#e', '"party:" . (isset($parties[ucfirst("$1")]) ? $parties[ucfirst("$1")] : "$1")', $qd);
        $qd = preg_replace('#\bD(.*?)\b#', 'date:$1', $qd);
        $qd = preg_replace('#\bG(.*?)\b#', 'department:$1', $qd); # XXX Lookup to show proper name of dept
        if (strstr($qd, '(M1 OR M2 OR M3 OR M4 OR M6 OR M101)')) {
            $qd = str_replace('(M1 OR M2 OR M3 OR M4 OR M6 OR M101)', 'section:uk', $qd);
        } elseif (strstr($qd, '(M7 OR M8)')) {
            $qd = str_replace('(M7 OR M8)', 'section:scotland', $qd);
        }
        $qd = preg_replace('#\bM(\d+)\b#e', '"section:" . (isset($hansardmajors[$1]["title"]) ? $hansardmajors[$1]["title"] : "$1")', $qd);
        # Speakers
        preg_match_all('#S(\d+)#', $qd, $m);
        foreach ($m[1] as $mm) {
                $member = new MEMBER(array('person_id' => $mm));
                $name = $member->full_name();
                $qd = str_replace("S$mm", "speaker:$name", $qd);
        }

        # Replace stemmed things with their unstemmed terms from the query
        preg_match_all('#Z[a-z]+#', $qd, $m);
        foreach ($m[0] as $mm) {
            $iter = $this->queryparser->unstem_begin($mm);
            $end = $this->queryparser->unstem_end($mm);
            $tt = array();
            while (!$iter->equals($end)) {
                $tt[] = $iter->get_term();
                $iter->next();
            }
            $qd = str_replace($mm, join(',',array_unique($tt)), $qd);
        }
        # Simplify display of excluded words
        $qd = preg_replace('#AND_NOT ([a-z0-9"]+)#', '-$1', $qd);
        preg_match_all('#AND_NOT \((.*?)\)#', $qd, $m);
        foreach ($m[1] as $mm) {
            $mmn = '-' . join(' -', explode(' OR ', $mm));
            $qd = str_replace("AND_NOT ($mm)", $mmn, $qd);
        }

        foreach( $this->prefixed as $items ) {
            if ($items[0] == 'groupby') {
                if ($items[1] == 'debate') {
                    $qd .= ' grouped by debate';
                } elseif ($items[1] == 'speech') {
                    $qd .= ' showing all speeches';
                } else {
                    $PAGE->error_message("Unknown group by '$items[1]' ignored");
                }
            }
        }
        $this->query_desc = trim($qd);

        #print 'DEBUG: ' . $query->get_description();
        twfy_debug("SEARCH", "queryparser description -- " . $this->query_desc);
    }

    function query_description_internal($long) {
        if (!defined('XAPIANDB') || !XAPIANDB) return '';
        return $this->query_desc;
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
        return $this->stemmer->apply(strtolower($word));
    }

    function get_spelling_correction() {
        return $this->queryparser->get_corrected_query_string();
    }

    // Perform partial query to get a count of number of matches
    function run_count () {
        if (!defined('XAPIANDB') || !XAPIANDB)
            return null;

		$start = getmicrotime();

        // Set collapsing and sorting
        global $PAGE;
        $collapsed = false;
        if (preg_match('#(speaker|segment):\d+#', $this->query)) {
            $collapsed = true;
        }
        foreach( $this->prefixed as $items ) {
            if ($items[0] == 'groupby') {
                $collapsed = true;
                if ($items[1] == 'speech')
                    ; // no collapse key
                elseif ($items[1] == 'debate')
                    $this->enquire->set_collapse_key(3);
                else 
                    $PAGE->error_message("Unknown group by '$items[1]' ignored");
            }
        }

        // default to grouping by subdebate, i.e. by page
        if (!$collapsed)
            $this->enquire->set_collapse_key(3);
        
        $matches = $this->enquire->get_mset(0, 500);
        // Take either: 1) the estimate which is sometimes too large or 2) the
        // size which is sometimes too low (it is limited to the 500 in the line
        // above).  We get the exact mset we need later, according to which page
        // we are on.
        if ($matches->size() < 500) {
            $count = $matches->size();
        } else {
            $count = $matches->get_matches_estimated();
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
                $this->enquire->set_sort_by_value(0);
                break;
            case 'created':
                $this->enquire->set_sort_by_value(2); 
            default:
                //do nothing, default ordering is by relevance
                break;
        }
        $matches = $this->enquire->get_mset($first_result, $results_per_page);
		$this->gids = array();
        $this->created = array();
        $this->collapsed = array();
		$this->relevances = array();
        $iter = $matches->begin();
        $end = $matches->end();
        while (!$iter->equals($end)) {
            $relevancy = $iter->get_percent();
            $weight    = $iter->get_weight();
            $collapsed = $iter->get_collapse_count();
            $doc       = $iter->get_document();
            $gid       = $doc->get_data();
            if ($sort_order == 'created') {
                array_push($this->created, $doc->get_value(6));
            }
			twfy_debug("SEARCH", "gid: $gid relevancy: $relevancy% weight: $weight");
			array_push($this->gids, "uk.org.publicwhip/".$gid);
            array_push($this->collapsed, $collapsed);
			array_push($this->relevances, $relevancy);
            $iter->next();
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
            
        $was_array = false;
        if (is_array($body)) {
            $was_array = true;
            $body = join('|||', $body);
        }

		$splitextract = preg_split('/([0-9,.]+|['.$this->wordcharsnodigit.']+)/', $body, -1, PREG_SPLIT_DELIM_CAPTURE);
		$hlextract = "";
        $stemmed_words = array_map(array($this, 'stem'), $this->words);
		foreach( $splitextract as $extractword) {
            $endswithamp = '';
            if (preg_match('/&$/', $extractword)) {
                $extractword = substr($extractword, 0, -1);
                $endswithamp = '&';
            }
			$hl = false;
			foreach( $stemmed_words as $word ) {
				if ($word == '') continue;
				
				$matchword  = $this->stem($extractword);
				if ($matchword == $word) {
					$hl = true;
					break;
				}
			}
			if ($hl)
				$hlextract .= "<span class=\"hi\">$extractword</span>$endswithamp";
			else
				$hlextract .= $extractword . $endswithamp;
		}
        $body = preg_replace("#</span>\s+<span class=\"hi\">#", " ", $hlextract);

        /*
        XXX OLD Way of doing it, doesn't work too well with stemming...
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
        */

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

        if ($was_array) {
            $hlbody = explode('|||', $hlbody);
        }

        return $hlbody;
    }

    // Find the position of the first of the search words/phrases in $body.
    function position_of_first_word($body) {
        $lcbody = ' ' . strtolower($body) . ' '; // spaces to make regexp mapping easier
        $pos = -1;

        // look for phrases
        foreach( $this->phrases as $phrase ) {
            $phrasematch = join($phrase, '[^'.$this->wordchars.']+');
            if (preg_match('/([^'.$this->wordchars.']' . $phrasematch . '[^A-Za-z0-9])/', $lcbody, $matches))
            {
                $wordpos = strpos( $lcbody, $matches[0] );
                if ($wordpos) {
                   if ( ($wordpos < $pos) || ($pos==-1) ) {
                        $pos = $wordpos;
                    }
                }
            }
        }
        if ($pos != -1) return $pos;

		$splitextract = preg_split('/([0-9,.]+|['.$this->wordcharsnodigit.']+)/', $lcbody, -1, PREG_SPLIT_DELIM_CAPTURE);
        $stemmed_words = array_map(array($this, 'stem'), $this->words);
		foreach( $splitextract as $extractword) {
            $extractword = preg_replace('/&$/', '', $extractword);
            if (!$extractword) continue;
            $wordpos = strpos($lcbody, $extractword);
            if (!$wordpos) continue;
			foreach( $stemmed_words as $word ) {
				if ($word == '') continue;
				$matchword = $this->stem($extractword);
				if ($matchword == $word && ($wordpos < $pos || $pos==-1)) {
                    $pos = $wordpos;
				}
			}
		}
        // only look for earlier words if phrases weren't found
        if ($pos != -1) return $pos;

        foreach( $this->words as $word ) {
            if (ctype_digit($word)) $word = '(?:'.$word.'|'.number_format($word).')';
            if (preg_match('/([^'.$this->wordchars.']' . $word . '[^'.$this->wordchars. '])/', $lcbody, $matches)) {
                $wordpos = strpos( $lcbody, $matches[0] );
                if ($wordpos) {
                    if ( ($wordpos < $pos) || ($pos==-1) ) {
                        $pos = $wordpos;
                    }
                }
            }
        }
        // only look for something containing the word (ie. something stemmed, but doesn't work all the time) if no whole word was found
        if ($pos != -1) return $pos;

        foreach( $this->words as $word ) {
            if (ctype_digit($word)) $word = '(?:'.$word.'|'.number_format($word).')';
            if (preg_match('/(' . $word . ')/', $lcbody, $matches)) {
                $wordpos = strpos( $lcbody, $matches[0] );
                if ($wordpos) {
                    if ( ($wordpos < $pos) || ($pos==-1) ) {
                        $pos = $wordpos;
                    }
                }
            }
        }

        if ($pos == -1)
            $pos = 0;
        return $pos;
    }
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

