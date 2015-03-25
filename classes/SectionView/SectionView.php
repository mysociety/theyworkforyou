<?php
# For displaying any debate calendar, day, debate, speech page or related.

namespace MySociety\TheyWorkForYou\SectionView;

class SectionView {

    function __construct() {
        global $hansardmajors;
        $this->major_data = $hansardmajors[$this->major];
        $this->page_base = str_replace('year', '', $this->major_data['page_year']);
        if (!property_exists($this, 'list')) {
            $this->class = "\\" . $this->class;
            $this->list = new $this->class();
        }
    }

    public function display() {
        global $PAGE;
        if ($year = get_http_var('y')) {
            $this->display_year($year);
        } elseif (($date = get_http_var('d')) && ($column = get_http_var('c'))) {
            $this->display_column($date, $column);
        } elseif ($date = get_http_var('d')) {
            $this->display_day($date);
        } elseif (get_http_var('id')) {
            $data = $this->display_section_or_speech();
            return $data;
        } else {
            $this->display_front();
        }
        $PAGE->page_end();
    }

    protected function display_year($year) {
        global $this_page, $PAGE, $DATA;
        $this_page = $this->page_base . 'year';
        if (is_numeric($year)) {
            $DATA->set_page_metadata($this_page, 'title', $year);
        }

        $PAGE->page_start();
        $PAGE->stripe_start();
        $args = array ( 'year' => $year );
        $this->list->display('calendar', $args);
        $blocks = array();
        $blocks[] = array( 'type' => 'nextprev' );
        if ($this->major == 1) {
            $blocks[] = array(
                'type' => 'include',
                'content' => 'minisurvey',
            );
        }
        $blocks[] = array(
            'type' => 'include',
            'content' => $this->major_data['sidebar']
        );
        $PAGE->stripe_end($blocks);
    }

    protected function display_column($date, $column) {
        global $this_page;
        $this_page = $this->page_base . 'column';
        $args = array( 'date' => $date, 'column' => $column );
        $this->list->display('column', $args);
    }

    protected function display_day($date) {
        global $this_page;
        $this_page = $this->page_base . 'day';
        $args = array ( 'date' => get_http_var('d') );
        $this->list->display('date', $args);
    }

    protected function display_section_or_speech($args = array()) {
        global $DATA, $this_page, $THEUSER;

        # += as we *don't* want to override any already supplied argument
        $args += array (
            'gid' => get_http_var('id'),
            's' => get_http_var('s'), // Search terms to be highlighted.
            'member_id' => get_http_var('m'), // Member's speeches to be highlighted.
        );

        if (preg_match('/speaker:(\d+)/', get_http_var('s'), $mmm)) {
            $args['person_id'] = $mmm[1];
        }

        try {
            $data = $this->list->display('gid', $args, 'none');
        } catch (\RedirectException $e) {
            $URL = new \URL($this->major_data['page_all']);
            if ($this->major == 6) {
                # Magically (as in I can't remember quite why), pbc_clause will
                # contain the new URL without any change...
                $URL->remove( array('id') );
            } else {
                $URL->insert( array('id'=>$e->getMessage()) );
            }
            # put the search term back in so highlighting works.
            # NB: as we don't see the # part of the URL we lose this :(
            if ( $args['s'] !== '' ) {
                $URL->insert( array('s'=>$args['s']) );
            }
            redirect($URL->generate('none'));
        }

        $data['individual_item'] = ($this->list->commentspage == $this_page);

        if ($data['individual_item']) {
            $COMMENTLIST = new \COMMENTLIST;
            $args['user_id'] = get_http_var('u');
            $args['epobject_id'] = $this->list->epobject_id();
            $data['comments']['object'] = $COMMENTLIST;
            $data['comments']['args'] = $args;
            $data['comments']['commentdata'] = array(
                'epobject_id' => $this->list->epobject_id(),
                'gid' => get_http_var('id'), # wrans is LIST->gid?
                'return_page' => $this_page
            );
        }

        if (!isset($data['info'])) {
            header("HTTP/1.0 404 Not Found");
            exit; # XXX
        }

        # Okay, let's set up highlighting and glossarisation

        $SEARCHENGINE = null;
        if (isset($data['info']['searchstring']) && $data['info']['searchstring'] != '') {
            $SEARCHENGINE = new \SEARCHENGINE($data['info']['searchstring']);
        }

        // Before we print the body text we need to insert glossary links
        // and highlight search string words.

        $speeches = 0;
        $bodies = array();
        foreach ($data['rows'] as $row) {
            $htype = $row['htype'];
            if ($htype == 12 || $htype == 13) {
                $speeches++;
            }
            $body = $row['body'];
            $body = preg_replace('#<phrase class="honfriend" id="uk.org.publicwhip/member/(\d+)" name="([^"]*?)">(.*?\s*\((.*?)\))</phrase>#', '<a href="/mp/?m=$1" title="Our page on $2 - \'$3\'">$4</a>', $body);
           $body = preg_replace_callback('#<phrase class="offrep" id="(.*?)/(\d+)-(\d+)-(\d+)\.(.*?)">(.*?)</phrase>#', function($matches) {
                return '<a href="/search/?pop=1&s=date:' . $matches[2] . $matches[3] . $matches[4] . '+column:' . $matches[5] . '+section:' . $matches[1] .'">' . str_replace("Official Report", "Hansard", $matches[6]) . '</a>';
            }, $body);
            #$body = preg_replace('#<phrase class="offrep" id="((.*?)/(\d+)-(\d+)-(\d+)\.(.*?))">(.*?)</phrase>#e', "\"<a href='/search/?pop=1&amp;s=date:$3$4$5+column:$6+section:$2&amp;match=$1'>\" . str_replace('Official Report', 'Hansard', '$7') . '</a>'", $body);
            $bodies[] = $body;
        }

        // Do all this unless the glossary is turned off in the URL
        if (get_http_var('ug') != 1) {
            // And glossary phrases
            twfy_debug_timestamp('Before glossarise');

            $args['sort'] = "regexp_replace";
            $GLOSSARY = new \GLOSSARY($args);
            $bodies = $GLOSSARY->glossarise($bodies, 1);

            twfy_debug_timestamp('After glossarise');
        }
        if ($SEARCHENGINE) {
            // We have some search terms to highlight.
            twfy_debug_timestamp('Before highlight');
            $bodies = $SEARCHENGINE->highlight($bodies);
            twfy_debug_timestamp('After highlight');
        }

        $first_speech = null;
        $data['section_title'] = '';
        $subsection_title = '';
        for ($i=0; $i<count($data['rows']); $i++) {
            $row = $data['rows'][$i];
            $htype = $row['htype'];
            // HPOS should be defined below if it's needed; otherwise default to 0
            $heading_hpos = 0;
            if ($htype == 10) {
                $data['section_title'] = $row['body'];
                $heading_hpos = $row['hpos'];
            } elseif ($htype == 11) {
                $subsection_title = $row['body'];
                $heading_hpos = $row['hpos'];
            } elseif ($htype == 12) {
                # Splitting out highlighting results back into individual bits
                $data['rows'][$i]['body'] = $bodies[$i];
            }
            if ($htype == 12 || $htype == 13) {
                if (!$first_speech) {
                    $first_speech = $data['rows'][$i];
                }

                # Voting links
                $data['rows'][$i]['voting_data'] = '';
                if (isset($row['votes'])) {
                    $data['rows'][$i]['voting_data'] = $this->generate_votes( $row['votes'], $row['epobject_id'], $row['gid'] );
                }

                # Annotation link
                if ($this->is_debate_section_page()) {
                    // Build the 'Add an annotation' link.
                    if (!$THEUSER->isloggedin()) {
                        $URL = new \URL('userprompt');
                        $URL->insert(array('ret'=>$row['commentsurl']));
                        $data['rows'][$i]['annotation_url'] = $URL->generate();
                    } else {
                        $data['rows'][$i]['annotation_url'] = $row['commentsurl'];
                    }

                    $data['rows'][$i]['commentteaser'] = $this->generate_commentteaser($row);
                }

                if (isset($row['mentions'])) {
                    $data['rows'][$i]['mentions'] = $this->get_question_mentions_html($row['mentions']);
                }

                if ($this->major == 1) {
                    $data['rows'][$i]['video'] = $this->get_video_html($row, $heading_hpos, $speeches);
                }
            }
        }

        if ($subsection_title) {
            $data['heading'] = $subsection_title;
        } else {
            $data['heading'] = $data['section_title'];
        }

        if ($subsection_title) {
            $data['intro'] = "$data[section_title]";
        } else {
            $data['intro'] = "";
        }
        if ($this->major == 1) {
            $data['location'] = '&ndash; in the House of Commons';
        } elseif ($this->major == 2) {
            $data['location'] = '&ndash; in Westminster Hall';
        } elseif ($this->major == 3) {
            $data['location'] = 'written question &ndash; answered';
        } elseif ($this->major == 4) {
            $data['location'] = 'written statement &ndash; made';
        } elseif ($this->major == 5) {
            $data['location'] = '&ndash; in the Northern Ireland Assembly';
        } elseif ($this->major == 6) {
            $data['location'] = '&ndash; in a Public Bill Committee';
        } elseif ($this->major == 7) {
            $data['location'] = '&ndash; in the Scottish Parliament';
        } elseif ($this->major == 8) {
            $data['location'] = '&ndash; Scottish Parliament written question &ndash; answered';
        } elseif ($this->major == 101) {
            $data['location'] = '&ndash; in the House of Lords';
        }

        $data['current_assembly'] = "westminster--debate";
        switch ($data['assembly_nav_current']) {
            case "UK":
                $data['current_assembly'] = "westminster--debate";
                break;
            case "SCOTLAND":
                $data['current_assembly'] = "scotland";
                break;
            case "NORTHERN IRELAND":
                $data['current_assembly'] = "ni";
                break;
        }

        if (array_key_exists('text_heading', $data['info'])) {
            // avoid having Clause 1 etc as the alert text search string on PBC pages as it's
            // almost certainly not what the person wants
            if ( $this->major == 6 ) {
                $data['email_alert_text'] = $data['section_title'];
            } else {
                $data['email_alert_text'] = $data['info']['text_heading'];
            }
        } else {
            // The user has requested only part of a debate, so find a suitable title
            if ($subsection_title) {
                $data['intro'] = "Part of $data[section_title]";
            } else {
                $data['intro'] = "Part of the debate";
            }
            foreach ($data['rows'] as $row) {
                if ($row['htype'] == 10 || $row['htype'] == 11) {
                    $data['email_alert_text'] = $row['body'];
                    $data['full_debate_url'] = $row['listurl'];
                    break;
                }
            }
        }
        // strip a couple of common characters that result in encode junk in the
        // search string
        $data['email_alert_text'] = preg_replace('/(?:[:()\[\]]|&#\d+;)/', '', $data['email_alert_text']);

        $data['debate_time_human'] = format_time($first_speech['htime'], 'g:i a');
        $data['debate_day_human'] = format_date($first_speech['hdate'], 'jS F Y');

        $URL = new \URL($this->list->listpage);
        $URL->insert(array('d' => $first_speech['hdate']));
        $URL->remove(array('id'));
        $data['debate_day_link'] = $URL->generate();

        $data['nextprev'] = $DATA->page_metadata($this_page, 'nextprev');

        return $data;
    }

    protected function display_front() {
        global $this_page, $PAGE, $DATA;
        $this_page = $this->page_base . 'front';
        $PAGE->page_start();
        $PAGE->stripe_start();
        $this->front_content();
        $rssurl = $DATA->page_metadata($this_page, 'rss');
        $blocks = array();
        if ($this->major == 6) {
            $blocks[] = array (
                'type' => 'include',
                'content' => 'minisurvey'
            );
        }
        $blocks[] = array ( 'type' => 'nextprev' );
        if ($this->major == 6) {
            $blocks[] = array(
                'type' => 'html',
                'content' => '
    <div class="block">
    <h4>Search bill committees</h4>
    <div class="blockbody">
    <form action="/search/" method="get">
    <p><input type="text" name="q" id="search_input" value="" size="40"> <input type="submit" value="Go">
    <input type="hidden" name="section" value="pbc">
    </p>
    </form>
    </div>
    </div>
    ',
            );
        } else {
            $blocks[] = array (
                'type' => 'include',
                'content' => 'calendar_' . $this->major_data['sidebar'],
            );
        }
        $blocks[] = array(
            'type' => 'include',
            'content' => $this->major_data['sidebar']
        );
        if ($rssurl) {
            $blocks[] = array(
                'type' => 'html',
                'content' => '<div class="block">
        <h4>RSS feed</h4>
        <p><a href="' . WEBPATH . $rssurl . '"><img alt="RSS feed" border="0" align="middle" src="http://www.theyworkforyou.com/images/rss.gif"></a>
        <a href="' . WEBPATH . $rssurl . '">RSS feed of most recent debates</a></p>
        </div>'
            );
        }
        $PAGE->stripe_end($blocks);
    }

    public function is_debate_section_page() {
        global $this_page;
        return ($this->major_data['type'] == 'debate' && $this->major_data['page_all'] == $this_page);
    }

    //$totalcomments, $comment, $commenturl
    function generate_commentteaser ($row) {
        // Returns HTML for the one fragment of comment and link for the sidebar.
        // $totalcomments is the number of comments this item has on it.
        // $comment is an array like:
        /* $comment = array (
            'comment_id' => 23,
            'user_id'    => 34,
            'body'        => 'Blah blah...',
            'posted'    => '2004-02-24 23:45:30',
            'username'    => 'phil'
            )
        */
        // $url is the URL of the item's page, which contains comments.

        if ($row['totalcomments'] == 0) {
            return;
        }

        //Add existing annotations
        $comment = $row['comment'];

        // If the comment is longer than the speech body, we want to trim it
        // to be the same length so they fit next to each other.
        // But the comment typeface is smaller, so we scale things slightly too...
        $targetsize = round(strlen($row['body']) * 0.6);

        $linktext = '';
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

        return array(
            'body' => prepare_comment_for_display($commentbody),
            'username' => _htmlentities($comment['username']),
            'linktext' => $linktext,
            'commentsurl' => $row['commentsurl'],
            'comment_id' => $comment['comment_id'],
        );
    }

    protected function get_question_mentions_html($row_data) {
        throw new \Exception('get_question_mentions_html called from class with no implementation');
    }

}
