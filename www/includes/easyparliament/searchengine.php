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

if (defined('XAPIANDB') AND XAPIANDB != '') {
    if (file_exists('/usr/share/php/xapian.php')) {
        include_once '/usr/share/php/xapian.php';
    } else {
        twfy_debug('SEARCH', '/usr/share/php/xapian.php does not exist');
    }
}

class SEARCHENGINE {
    public $valid = false;
    public $error;

    public function SEARCHENGINE($query) {
        if (!defined('XAPIANDB') || !XAPIANDB)
            return null;

        global $xapiandb, $PAGE, $hansardmajors, $parties;
        if (!$xapiandb) {
            if (strstr(XAPIANDB, ":")) {
                //ini_set('display_errors', 'On');
                list ($xapian_host, $xapian_port) = explode(":", XAPIANDB);
                twfy_debug("SEARCH", "Using Xapian remote backend: " . $xapian_host . " port " . $xapian_port);
                $xapiandb_remote = remote_open($xapian_host, intval($xapian_port));
                $xapiandb = new XapianDatabase($xapiandb_remote);
            } else {
                $xapiandb = new XapianDatabase(XAPIANDB);
            }
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
        $this->wordchars = "A-Za-z0-9,.'&:_\xc0-\xff";
        $this->wordcharsnodigit = "A-Za-z0-9'&_\xc0-\xff";

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
                if ($in_quote) array_push($this->phrases, array());
                if (!$in_quote && !count($this->phrases[count($this->phrases) - 1])) {
                    array_pop($this->phrases);
                }
                continue;
            }
            if ($word == '') {
                continue;
            }

            if (strpos($word, ':') !== false) {
                $items = explode(":", strtolower($word));
                $type = $items[0];
                if (substr($type, 0, 1)=='-') $type = substr($type, 1);
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
                    elseif ($value == 'spwrans' || $value == 'spwran') $newv = 8;
                    elseif ($value == 'uk') $newv = array(1,2,3,4,6,101);
                    elseif ($value == 'scotland') $newv = array(7,8);
                    elseif ($value == 'future') $newv = 'F';
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
                array_push($this->phrases[count($this->phrases) - 1], strtolower($word));
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

        # Merged people
        $db = new ParlDB;
        $merged = $db->query('SELECT * FROM gidredirect WHERE gid_from LIKE :gid_from', array(':gid_from' => "uk.org.publicwhip/person/%"));
        for ($n=0; $n<$merged->rows(); $n++) {
            $from_id = str_replace('uk.org.publicwhip/person/', '', $merged->field($n, 'gid_from'));
            $to_id = str_replace('uk.org.publicwhip/person/', '', $merged->field($n, 'gid_to'));
            $this->query = preg_replace("#speaker:($from_id|$to_id)#i", "(speaker:$from_id OR speaker:$to_id)", $this->query);
        }

        twfy_debug("SEARCH", "prefixed: " . var_export($this->prefixed, true));

        twfy_debug("SEARCH", "query -- ". $this->query);
        $flags = XapianQueryParser::FLAG_BOOLEAN | XapianQueryParser::FLAG_LOVEHATE |
            XapianQueryParser::FLAG_WILDCARD | XapianQueryParser::FLAG_SPELLING_CORRECTION;
        $flags = $flags | XapianQueryParser::FLAG_PHRASE;
        try {
            $query = $this->queryparser->parse_query($this->query, $flags);
        } catch (Exception $e) {
            # Nothing we can really do with a bad query
            $this->error = _htmlspecialchars($e->getMessage());

            return null;
        }

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
            #$this->phrases[] = preg_split('#\s+#', $phrase_new);
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
        if (strstr($qd, 'M1 OR M2 OR M3 OR M4 OR M6 OR M101')) {
            $qd = str_replace('M1 OR M2 OR M3 OR M4 OR M6 OR M101', 'section:uk', $qd);
        } elseif (strstr($qd, 'M7 OR M8')) {
            $qd = str_replace('M7 OR M8', 'section:scotland', $qd);
        }
        $qd = preg_replace('#\bM(\d+)\b#e', '"in the \'" . (isset($hansardmajors[$1]["title"]) ? $hansardmajors[$1]["title"] . "\'" : "$1")', $qd);
        $qd = preg_replace('#\bMF\b#', 'in Future Business', $qd);

        # Replace stemmed things with their unstemmed terms from the query
        $used = array();
        preg_match_all('#Z[^\s()]+#', $qd, $m);
        foreach ($m[0] as $mm) {
            $iter = $this->queryparser->unstem_begin($mm);
            $end = $this->queryparser->unstem_end($mm);
            while (!$iter->equals($end)) {
                $tt = $iter->get_term();
                if (!in_array($tt, $used)) break;
                $iter->next();
            }
            $used[] = $tt;
            $qd = preg_replace('#' . preg_quote($mm, '#') . '#', $tt, $qd, 1);
        }

        # Speakers
        for ($n=0; $n<$merged->rows(); $n++) {
            $from_id = str_replace('uk.org.publicwhip/person/', '', $merged->field($n, 'gid_from'));
            $to_id = str_replace('uk.org.publicwhip/person/', '', $merged->field($n, 'gid_to'));
            $qd = str_replace("(S$from_id OR S$to_id)", "S$to_id", $qd);
            $qd = str_replace("S$from_id OR S$to_id", "S$to_id", $qd);
        }

        preg_match_all('#S(\d+)#', $qd, $m);
        foreach ($m[1] as $mm) {
            $member = new MEMBER(array('person_id' => $mm));
            $name = iconv('iso-8859-1', 'utf-8//TRANSLIT', $member->full_name()); # Names are currently in ISO-8859-1
            $qd = str_replace("S$mm", "speaker:$name", $qd);
        }

        # Simplify display of excluded words
        $qd = preg_replace('#AND_NOT ([a-z0-9"]+)#', '-$1', $qd);
        preg_match_all('#AND_NOT \((.*?)\)#', $qd, $m);
        foreach ($m[1] as $mm) {
            $mmn = '-' . join(' -', explode(' OR ', $mm));
            $qd = str_replace("AND_NOT ($mm)", $mmn, $qd);
        }

        foreach ($this->prefixed as $items) {
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

        $qd = iconv('utf-8', 'iso-8859-1//TRANSLIT', $qd); # Xapian is UTF-8, site is ISO8859-1
        $this->query_desc = trim($qd);

        #print 'DEBUG: ' . $query->get_description();
        twfy_debug("SEARCH", "words: " . var_export($this->words, true));
        twfy_debug("SEARCH", "phrases: " . var_export($this->phrases, true));
        twfy_debug("SEARCH", "queryparser description -- " . $this->query_desc);

        $this->valid = true;
    }

    public function query_description_internal($long) {
        if (!defined('XAPIANDB') || !XAPIANDB) {
            return '';
        }
        if (!$this->valid) {
            return '[bad query]';
        }

        return $this->query_desc;
    }

    // Return textual description of search
    public function query_description_short() {
        return $this->query_description_internal(false);
    }

    // Return textual description of search
    public function query_description_long() {
        return $this->query_description_internal(true);
    }

    // Return stem of a word
    public function stem($word) {
        return $this->stemmer->apply(strtolower($word));
    }

    public function get_spelling_correction() {
         if (!defined('XAPIANDB') || !XAPIANDB)
            return null;

            return $this->queryparser->get_corrected_query_string();
    }

    // Perform partial query to get a count of number of matches
    public function run_count($first_result, $results_per_page, $sort_order='relevance') {
        if (!defined('XAPIANDB') || !XAPIANDB)
            return null;

        $start = getmicrotime();

        switch ($sort_order) {
            case 'date':
            case 'newest':
                $this->enquire->set_sort_by_value(0, true);
                break;
            case 'oldest':
                $this->enquire->set_sort_by_value(0, false);
                break;
            case 'created':
                $this->enquire->set_sort_by_value(2);
            default:
                //do nothing, default ordering is by relevance
                break;
        }

        // Set collapsing and sorting
        global $PAGE;
        $collapsed = false;
        if (preg_match('#(speaker|segment):\d+#', $this->query)) {
            $collapsed = true;
        }
        foreach ($this->prefixed as $items) {
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

        /*
        XXX Helping to debug possible Xapian bug
        foreach (array(0, 50, 100, 200, 300, 400, 460) as $fff) {
            foreach (array(0, 100, 300, 500, 1000) as $cal) {
                print "get_mset($fff, 20, $cal): ";
                $m = $this->enquire->get_mset($fff, 20, $cal);
                print $m->get_matches_estimated(). ' ';
                print $m->get_matches_lower_bound() . ' ';
                print $m->get_matches_upper_bound() . "\n";
            }
        }
        */

        #$matches = $this->enquire->get_mset(0, 500);
        $this->matches = $this->enquire->get_mset($first_result, $results_per_page, 100);
        // Take either: 1) the estimate which is sometimes too large or 2) the
        // size which is sometimes too low (it is limited to the 500 in the line
        // above).  We get the exact mset we need later, according to which page
        // we are on.
        #if ($matches->size() < 500) {
            #$count = $matches->size();
        #} else {
            $count = $this->matches->get_matches_estimated();
        #    print "DEBUG bounds: ";
        #    print $this->matches->get_matches_lower_bound();
        #    print ' - ';
        #    print $this->matches->get_matches_upper_bound();
        #}

        $duration = getmicrotime() - $start;
        twfy_debug ("SEARCH", "Search count took $duration seconds.");

        return $count;
    }

    // Perform the full search...
    public function run_search($first_result, $results_per_page, $sort_order='relevance') {
        $start = getmicrotime();

        #$matches = $this->enquire->get_mset($first_result, $results_per_page);
        $matches = $this->matches;
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
                array_push($this->created, join('', unpack('N', $doc->get_value(2)))); # XXX Needs fixing
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
    public function get_gids() {
        return $this->gids;
    }
    public function get_relevances() {
        return $this->relevances;
    }
    public function get_createds() {
        return $this->created;
    }

    // Puts HTML highlighting round all the matching words in the text
    public function highlight($body) {
        if (!defined('XAPIANDB') || !XAPIANDB)
            return $body;

        $stemmed_words = array_map(array($this, 'stem'), $this->words);
        if (is_array($body)) {
            foreach ($body as $k => $b) {
                $body[$k] = $this->highlight_internal($b, $stemmed_words);
            }

            return $body;
        } else {
            return $this->highlight_internal($body, $stemmed_words);
        }
    }

    public function highlight_internal($body, $stemmed_words) {
        if (!defined('XAPIANDB') || !XAPIANDB)
            return $body;

        # Does html_entity_decode without the htmlspecialchars
        $body = preg_replace('/&#(\d\d\d);/e', 'chr($1)', $body);
        $splitextract = preg_split('/(<[^>]*>|[0-9,.]+|['.$this->wordcharsnodigit.']+)/', $body, -1, PREG_SPLIT_DELIM_CAPTURE);
        $hlextract = "";
        foreach ($splitextract as $extractword) {
            if (preg_match('/^<[^>]*>$/', $extractword)) {
                $hlextract .= $extractword;
                continue;
            }
            $endswithamp = '';
            if (substr($extractword, -1) == '&') {
                $extractword = substr($extractword, 0, -1);
                $endswithamp = '&';
            }
            $hl = false;
            $matchword = $this->stem($extractword);
            foreach ($stemmed_words as $word) {
                if ($word == '') continue;
                if ($matchword == $word) {
                    $hl = true;
                    break;
                }
            }
            if ($hl) {
                $hlextract .= "<span class=\"hi\">$extractword</span>$endswithamp";
            } else {
                $hlextract .= $extractword . $endswithamp;
            }
        }
        $body = preg_replace("#</span>\s+<span class=\"hi\">#", " ", $hlextract);

        // Contents will be used in preg_replace() to highlight the search terms.
        $findwords = array();
        $replacewords = array();

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

        foreach ($this->phrases as $phrase) {
            $phrasematch = join($phrase, '[^'.$this->wordchars.']+');
            array_push($findwords, "/\b($phrasematch)\b/i");
            $replacewords[] = "<span class=\"hi\">\\1</span>";
        }

        // Highlight search phrases.
        $hlbody = preg_replace($findwords, $replacewords, $body);

        return $hlbody;
    }

    // Find the position of the first of the search words/phrases in $body.
    public function position_of_first_word($body) {
        $lcbody = ' ' . html_entity_decode(strtolower($body)) . ' '; // spaces to make regexp mapping easier
        $pos = -1;

        // look for phrases
        foreach ($this->phrases as $phrase) {
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
        foreach ($splitextract as $extractword) {
            $extractword = preg_replace('/&$/', '', $extractword);
            if (!$extractword) continue;
            $wordpos = strpos($lcbody, $extractword);
            if (!$wordpos) continue;
            foreach ($stemmed_words as $word) {
                if ($word == '') continue;
                $matchword = $this->stem($extractword);
                if ($matchword == $word && ($wordpos < $pos || $pos==-1)) {
                    $pos = $wordpos;
                }
            }
        }
        // only look for earlier words if phrases weren't found
        if ($pos != -1) return $pos;

        foreach ($this->words as $word) {
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

        foreach ($this->words as $word) {
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
        $count = $SEARCHENGINE->run_count(0, 5000, 'date');
        if ($count <= 0) {
            $data['error'] = 'No results';

            return $data;
        }
        $SEARCHENGINE->run_search(0, 5000, 'date');
        $gids = $SEARCHENGINE->get_gids();
        if (count($gids) <= 0) {
            $data['error'] = 'No results';

            return $data;
        }
        if (count($gids) == 5000)
            $data['limit_reached'] = true;

        # Fetch all the speakers of the results, count them up and get min/max date usage
        $speaker_count = array();
        $gids = join('","', $gids);
        $db = new ParlDB;
        $q = $db->query('SELECT gid,person_id,hdate FROM hansard WHERE gid IN ("' . $gids . '")');
        for ($n=0; $n<$q->rows(); $n++) {
            $gid = $q->field($n, 'gid');
            $person_id = $q->field($n, 'person_id');
            $hdate = $q->field($n, 'hdate');
            if (!isset($speaker_count[$person_id])) {
                $speaker_count[$person_id] = 0;
                $maxdate[$person_id] = '1001-01-01';
                $mindate[$person_id] = '9999-12-31';
            }
            $speaker_count[$person_id]++;
            if ($hdate < $mindate[$person_id]) $mindate[$person_id] = $hdate;
            if ($hdate > $maxdate[$person_id]) $maxdate[$person_id] = $hdate;
        }

        # Fetch details of all the speakers
        $speakers = array();
        $pids = array();
        if (count($speaker_count)) {
            $person_ids = join(',', array_keys($speaker_count));
            $q = $db->query('SELECT member_id, member.person_id, title, given_name, family_name, lordofname,
                                constituency, house, party,
                                moffice_id, dept, position, from_date, to_date, left_house
                            FROM member LEFT JOIN moffice ON member.person_id = moffice.person
                                JOIN person_names pn ON member.person_id = pn.person_id AND pn.type="name" AND pn.start_date <= left_house AND left_house <= pn.end_date
                            WHERE member.person_id IN (' . $person_ids . ')
                            ' . ($house ? " AND house=$house" : '') . '
                            ORDER BY left_house DESC');
            for ($n=0; $n<$q->rows(); $n++) {
                $mid = $q->field($n, 'member_id');
                if (!isset($pids[$mid])) {
                    $title = $q->field($n, 'title');
                    $first = $q->field($n, 'given_name');
                    $last = $q->field($n, 'family_name');
                    $lordofname = $q->field($n, 'lordofname');
                    $house = $q->field($n, 'house');
                    $party = $q->field($n, 'party');
                    $full_name = ucfirst(member_full_name($house, $title, $first, $last, $lordofname));
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
                }
                if ( !isset($speakers[$pid]['party']) && $party ) {
                    $speakers[$pid]['party'] = $party;
                }
            }
        }
        if (isset($speaker_count[0])) {
            $speakers[0] = array('party'=>'', 'name'=>'Headings, procedural text, etc.', 'house'=>0, 'count'=>0);
        }
        $party_count = array();
        $ok = 0;
        foreach ($speakers as $pid => &$speaker) {
            $speaker['count'] = $speaker_count[$pid];
            $speaker['pmaxdate'] = $maxdate[$pid];
            $speaker['pmindate'] = $mindate[$pid];
            $ok = 1;
            if (!isset($party_count[$speaker['party']]))
                $party_count[$speaker['party']] = 0;
            $party_count[$speaker['party']] += $count;
        }

        function sort_by_count($a, $b) {
            if ($a['count'] > $b['count']) return -1;
            if ($a['count'] < $b['count']) return 1;
            return 0;
        }
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

// Return query result from looking for MPs
function search_member_db_lookup($searchstring, $current_only=false) {
    if (!$searchstring) return false;
    $searchwords = explode(' ', $searchstring, 3);
    $params = array();
    if (count($searchwords) == 1) {
        $params[':like_0'] = '%' . $searchwords[0] . '%';
        $where = "given_name LIKE :like_0 OR family_name LIKE :like_0 OR lordofname LIKE :like_0";
    } elseif (count($searchwords) == 2) {
        // We don't do anything special if there are more than two search words.
        // And here we're assuming the user's put the names in the right order.
        $params[':like_0'] = '%' . $searchwords[0] . '%';
        $params[':like_1'] = '%' . $searchwords[1] . '%';
        $params[':like_0_and_1'] = '%' . $searchwords[0] . ' '. $searchwords[1] . '%';
        $params[':like_0_and_1_hyphen'] = '%' . $searchwords[0] . '-'. $searchwords[1] . '%';
        $where = "(given_name LIKE :like_0 AND family_name LIKE :like_1)";
        $where .= " OR (given_name LIKE :like_1 AND family_name LIKE :like_0)";
        $where .= " OR (title LIKE :like_0 AND family_name LIKE :like_1)";
        $where .= " OR given_name LIKE :like_0_and_1 OR given_name LIKE :like_0_and_1_hyphen";
        $where .= " OR family_name LIKE :like_0_and_1 OR family_name LIKE :like_0_and_1_hyphen";
        $where .= " OR lordofname LIKE :like_0_and_1";
        if (strtolower($searchwords[0]) == 'nick') {
            $where .= " OR (given_name LIKE '%nicholas%' AND family_name LIKE :like_1)";
        }
    } else {
        $searchwords[2] = str_replace('of ', '', $searchwords[2]);
        $params[':like_0'] = '%' . $searchwords[0] . '%';
        $params[':like_1'] = '%' . $searchwords[1] . '%';
        $params[':like_2'] = '%' . $searchwords[2] . '%';
        $params[':like_0_and_1'] = '%' . $searchwords[0] . ' '. $searchwords[1] . '%';
        $params[':like_1_and_2'] = '%' . $searchwords[1] . ' '. $searchwords[2] . '%';
        $params[':like_1_and_2_hyphen'] = '%' . $searchwords[1] . '-'. $searchwords[2] . '%';
        $where = "(given_name LIKE :like_0_and_1 AND family_name LIKE :like_2)";
        $where .= " OR (given_name LIKE :like_0 AND family_name LIKE :like_1_and_2)";
        $where .= " OR (given_name LIKE :like_0 AND family_name LIKE :like_1_and_2_hyphen)";
        $where .= " OR (title LIKE :like_0 AND family_name LIKE :like_1_and_2)";
        $where .= " OR (title LIKE :like_0 AND family_name LIKE :like_1_and_2_hyphen)";
        $where .= " OR (title LIKE :like_0 AND given_name LIKE :like_1 AND family_name LIKE :like_2)";
        $where .= " OR (title LIKE :like_0 AND family_name LIKE :like_1 AND lordofname LIKE :like_2)";
    }

    $db = new ParlDB;
    $q = $db->query("SELECT person_id FROM person_names WHERE type='name' AND ($where)", $params);
    return $q;
}

function search_member_db_lookup_with_names($searchstring, $current_only=false) {
    $q = search_member_db_lookup($searchstring, $current_only);

    if ( !$q->rows ) {
        return $q;
    }

    $person_ids = array();
    for ($i=0; $i<$q->rows(); ++$i) {
        $pid = $q->field($i, 'person_id');
        $person_ids[$pid] = 1;
    }
    $pids = array_keys($person_ids);
    $pids_str = join(',', $pids);

    $where = '';
    if ($current_only) {
        $where = "AND left_house='9999-12-31'";
    }

    # This is not totally accurate (e.g. minimum entered date may be from a
    # different house, or similar), but should be good enough.
    $q = $db->query("SELECT member.person_id,
                            title, given_name, family_name, lordofname,
                            constituency, party,
                            (SELECT MIN(entered_house) FROM member m WHERE m.person_id=member.person_id) min_entered_house,
                            left_house, house
                    FROM member, person_names pn
                    WHERE member.person_id IN ($pids_str) $where
                        AND member.person_id = pn.person_id AND pn.type = 'name'
                        AND pn.start_date <= member.left_house AND member.left_house <= pn.end_date
                        AND left_house = (SELECT MAX(left_house) FROM member m WHERE m.person_id=member.person_id)
                    GROUP BY person_id
                    ORDER BY family_name, lordofname, given_name, person_id");

    return $q;
}

// Given a search term, find constituencies by name or postcode
// Returns a list of the array of constituencies, then a boolean saying whether
// it was a postcode used.
function search_constituencies_by_query($searchterm) {
    if (validate_postcode($searchterm)) {
        // Looks like a postcode - can we find the constituency?
        $constituency = postcode_to_constituency($searchterm);
        if ($constituency) {
            return array( array($constituency), true );
        }
    }

    // No luck so far - let's see if they're searching for a constituency.
    $try = strtolower($searchterm);
    $query = "select distinct
            (select name from constituency where cons_id = o.cons_id and main_name) as name
        from constituency AS o where name like :try
        and from_date <= date(now()) and date(now()) <= to_date";
    $db = new ParlDB;
    $q = $db->query($query, array(':try' => '%' . $try . '%'));

    $constituencies = array();
    for ($n=0; $n<$q->rows(); $n++) {
        $constituencies[] = $q->field($n, 'name');
    }

    return array( $constituencies, false );
}
