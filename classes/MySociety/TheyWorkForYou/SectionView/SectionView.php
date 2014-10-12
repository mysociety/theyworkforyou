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
            $this->display_section_or_speech();
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
        global $this_page, $GLOSSARY, $PAGE, $THEUSER;

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
            $GLOSSARY = new \MySociety\TheyWorkForYou\Glossary($args);
        }

        try {
            $result = $this->list->display('gid', $args);
        } catch (\MySociety\TheyWorkForYou\RedirectException $e) {
            $URL = new \MySociety\TheyWorkForYou\Url($this->major_data['page_all']);
            if ($this->major == 6) {
                # Magically (as in I can't remember quite why), pbc_clause will
                # contain the new URL without any change...
                $URL->remove( array('id') );
            } else {
                $URL->insert( array('id'=>$e->getMessage()) );
            }
            redirect($URL->generate('none'));
        }

        if ($this->list->commentspage == $this_page) {
            $PAGE->stripe_start('side', 'comments');
            $COMMENTLIST = new \MySociety\TheyWorkForYou\CommentList;
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
