<?php
# vim:sw=4:ts=4:et:nowrap

namespace MySociety\TheyWorkForYou\Search;

class ParseArgs {

    private $searchstring;
    public $searchkeyword;

    private function parse_advanced_params() {
        $searchstring = '';

        if ($advphrase = get_http_var('phrase')) {
            $searchstring .= ' "' . $advphrase . '"';
        }

        if ($advexclude = get_http_var('exclude')) {
            $searchstring .= ' -' . join(' -', preg_split('/\s+/', $advexclude));
        }

        return $searchstring;
    }

    private function parse_date_params() {
        $searchstring = '';

        if (get_http_var('from') || get_http_var('to')) {
            $from = parse_date(get_http_var('from'));
            if ($from) $from = $from['iso'];
            else $from = '1935-10-01';
            $to = parse_date(get_http_var('to'));
            if ($to) $to = $to['iso'];
            else $to = date('Y-m-d');
            $searchstring .= " $from..$to";
        }

        return $searchstring;
    }

    private function parse_column_params() {
        $searchstring = '';

        if ($column = trim(get_http_var('column'))) {
            if (preg_match('#^(\d+)W$#', $column, $m)) {
                $searchstring .= " column:$m[1] section:wrans";
            } elseif (preg_match('#^(\d+)WH$#', $column, $m)) {
                $searchstring .= " column:$m[1] section:whall";
            } elseif (preg_match('#^(\d+)WS$#', $column, $m)) {
                $searchstring .= " column:$m[1] section:wms";
            } elseif (preg_match('#^\d+$#', $column)) {
                $searchstring .= " column:$column";
            }
        }

        return $searchstring;
    }

    private function parse_groupby_params() {
        $searchstring = '';

        if ($searchgroupby = trim(get_http_var('groupby'))) {
            $searchstring .= " groupby:$searchgroupby";
        }

        return $searchstring;
    }

    private function parse_person_params() {
        $searchstring = '';

        # Searching from MP pages
        if ($searchspeaker = trim(get_http_var('pid'))) {
            $searchstring .= " speaker:$searchspeaker";
        }

        # Searching from MP pages
        if ($searchspeaker = trim(get_http_var('person'))) {
            $q = \MySociety\TheyWorkForYou\Utility\Search::searchMemberDbLookup($searchspeaker);
            $pids = array();
            $row_count = $q->rows();
            for ($i=0; $i<$row_count; $i++) {
                $pids[$q->field($i, 'person_id')] = true;
            }
            $pids = array_keys($pids);
            if (count($pids) > 0) {
                $searchstring .= ' speaker:' . join(' speaker:', $pids);
            }
        }

        return $searchstring;
    }

    private function parse_search_restrictions() {
        $searchstring = '';

        if ($advdept = get_http_var('department')) {
            $searchstring .= ' department:' . preg_replace('#[^a-z]#i', '', $advdept);
        }

        if ($advparty = get_http_var('party')) {
            $searchstring .= ' party:' . join(' party:', explode(',', $advparty));
        }

        $advsection = get_http_var('section');
        if (!$advsection)
            $advsection = get_http_var('maj'); # Old URLs had this
        if (is_array($advsection)) {
            $searchstring .= ' section:' . join(' section:', $advsection);
        } elseif ($advsection) {
            $searchstring .= " section:$advsection";
        }

        return $searchstring;
    }

    private function tidy_search_string($search_main, $searchstring) {
        $searchstring = trim($searchstring);
        if ($search_main && $searchstring) {
            if (strpos($search_main, 'OR') !== false) {
                $search_main = "($search_main)";
            }
            $searchstring = "$search_main $searchstring";
        } elseif ($search_main) {
            $searchstring = $search_main;
        }

        $searchstring_conv = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $searchstring);
        if (!$searchstring_conv) {
            $searchstring_conv = @iconv('Windows-1252', 'ISO-8859-1//TRANSLIT', $searchstring);
        }
        if ($searchstring_conv) {
            $searchstring = $searchstring_conv;
        }

        return $searchstring;
    }

    public function construct_search_string() {

        // If q has a value (other than the default empty string) use that over s.
        if (get_http_var('q') != '') {
            $search_main = trim(get_http_var('q'));
        } else {
            $search_main = trim(get_http_var('s'));
        }

        $this->searchkeyword = $search_main;

        $searchstring = $this->parse_advanced_params();
        $searchstring .= $this->parse_date_params();
        $searchstring .= $this->parse_column_params();
        $searchstring .= $this->parse_search_restrictions();
        $searchstring .= $this->parse_groupby_params();
        $searchstring .= $this->parse_person_params();

        $searchstring = $this->tidy_search_string($search_main, $searchstring);

        twfy_debug('SEARCH', _htmlspecialchars($searchstring));
        return $searchstring;
    }
}
