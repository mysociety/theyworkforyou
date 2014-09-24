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
        # += as we *don't* want to override any already supplied argument
        $args += array (
            'gid' => get_http_var('id'),
            's' => get_http_var('s'), // Search terms to be highlighted.
            'member_id' => get_http_var('m'), // Member's speeches to be highlighted.
            'glossarise' => 1 // Glossary is on by default
        );

        if (preg_match('/speaker:(\d+)/', get_http_var('s'), $mmm)) {
            $args['person_id'] = $mmm[1];
        }

        // Glossary can be turned off in the url
        if (get_http_var('ug') == 1) {
            $args['glossarise'] = 0;
        } else {
            $args['sort'] = "regexp_replace";
            $GLOSSARY = new \GLOSSARY($args);
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
            redirect($URL->generate('none'));
        }

/*
        if ($this->list->commentspage == $this_page) {
            $PAGE->stripe_start('side', 'comments');
            $COMMENTLIST = new \COMMENTLIST;
            $args['user_id'] = get_http_var('u');
            $args['epobject_id'] = $this->list->epobject_id();
            $COMMENTLIST->display('ep', $args);
            $PAGE->stripe_end();
            $PAGE->stripe_start('side', 'addcomment');
            $commentdata = array(
                'epobject_id' => $this->list->epobject_id(),
                'gid' => get_http_var('id'), # wrans is LIST->gid?
                'return_page' => $this_page
            );
            $PAGE->comment_form($commentdata);
            if ($THEUSER->isloggedin()) {
                $sidebar = array(
                    array(
                        'type' => 'include',
                        'content' => 'comment'
                    )
                );
                $PAGE->stripe_end($sidebar);
            } else {
                $PAGE->stripe_end();
            }
        }
*/

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

        $bodies = array();
        foreach ($data['rows'] as $row) {
            $body = $row['body'];
            $body = preg_replace('#<phrase class="honfriend" id="uk.org.publicwhip/member/(\d+)" name="([^"]*?)">(.*?\s*\((.*?)\))</phrase>#', '<a href="/mp/?m=$1" title="Our page on $2 - \'$3\'">$4</a>', $body);
            $body = preg_replace('#<phrase class="offrep" id="(.*?)/(\d+)-(\d+)-(\d+)\.(.*?)">(.*?)</phrase>#e', '\'<a href="/search/?pop=1&s=date:$2$3$4+column:$5+section:$1">\' . str_replace("Official Report", "Hansard", \'$6\') . \'</a>\'', $body);
            #$body = preg_replace('#<phrase class="offrep" id="((.*?)/(\d+)-(\d+)-(\d+)\.(.*?))">(.*?)</phrase>#e', "\"<a href='/search/?pop=1&amp;s=date:$3$4$5+column:$6+section:$2&amp;match=$1'>\" . str_replace('Official Report', 'Hansard', '$7') . '</a>'", $body);
            $bodies[] = $body;
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

        $speeches = 0;
        $first_speech = null;
        $data['section_title'] = '';
        $subsection_title = '';
        for ($i=0; $i<count($data['rows']); $i++) {
            $htype = $data['rows'][$i]['htype'];
            if ($htype == 10) {
                $data['section_title'] = $data['rows'][$i]['body'];
            } elseif ($htype == 11) {
                $subsection_title = $data['rows'][$i]['body'];
            } elseif ($htype == 12) {
                $data['rows'][$i]['body'] = $bodies[$i];
            }
            if ($htype == 12 || $htype == 13) {
                $speeches++;
                if (!$first_speech) {
                    $first_speech = $row;
                }
            }
        }

        if ($subsection_title) {
            $data['heading'] = $subsection_title;
        } else {
            $data['heading'] = $data['section_title'];
        }

        if ($subsection_title) {
            $data['intro'] = "This $data[section_title]";
        } else {
            $data['intro'] = "This";
        }
        if ($this->major == 1) {
            $data['location'] = 'debate took place in the House of Commons';
        } elseif ($this->major == 2) {
            $data['location'] = 'debate took place in Westminster Hall';
        } elseif ($this->major == 3) {
            $data['location'] = 'written question was answered';
        } elseif ($this->major == 4) {
            $data['location'] = 'written statement was made';
        } elseif ($this->major == 5) {
            $data['location'] = 'debate took place in the Northern Ireland Assembly';
        } elseif ($this->major == 6) {
            $data['location'] = 'debate took place in a Public Bill Committee';
        } elseif ($this->major == 7) {
            $data['location'] = 'debate took place in the Scottish Parliament';
        } elseif ($this->major == 8) {
            $data['location'] = 'Scottish Parliament written question was answered';
        } elseif ($this->major == 101) {
            $data['location'] = 'debate took place in the House of Lords';
        }

        if (array_key_exists('text_heading', $data['info'])) {
            $data['email_alert_text'] = $data['info']['text_heading'];
        } else {
            // The user has requested only part of a debate, so find a suitable title
            if ($subsection_title) {
                $data['intro'] = "This is part of the $data[section_title] debate that took place";
            } else {
                $data['intro'] = "This is part of the debate that took place";
            }
            foreach ($data['rows'] as $row) {
                if ($row['htype'] == 10 || $row['htype'] == 11) {
                    $data['email_alert_text'] = $row['body'];
                    $data['full_debate_url'] = $row['listurl'];
                    break;
                }
            }
        }

        $data['debate_time_human'] = format_time($first_speech['htime'], 'g:i a');
        $data['debate_day_human'] = format_date($first_speech['hdate'], 'jS F Y');

        $URL = new \URL($this->list->listpage);
        $URL->insert(array('d' => $first_speech['hdate']));
        $URL->remove(array('id'));
        $data['debate_day_link'] = $URL->generate();
  
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

}
