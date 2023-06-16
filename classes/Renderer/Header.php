<?php

namespace MySociety\TheyWorkForYou\Renderer;

/**
 * Template Header Renderer
 *
 * Prepares variables for inclusion in a template header.
 */

class Header
{

    public $nav_highlights;

    public $data;

    private $keywords_title;

    public function __construct() {
        $this->data = array();

        $this->get_page_url();
        $this->get_page_title();
        $this->get_page_keywords();
        $this->get_header_links();
        $this->get_next_prev_links();
        $this->get_rss_link();
        $this->get_menu_highlights();
        $this->get_top_and_bottom_links();
        $this->add_robots_metadata();
    }

    private function add_robots_metadata() {
        global $DATA, $this_page;

        # Robots header
        if (DEVSITE) {
            $this->data['robots'] = 'noindex,nofollow';
        } elseif ($robots = $DATA->page_metadata($this_page, 'robots')) {
            $this->data['robots'] = $robots;
        }
    }

    /**
     * Work out what the page url
     */
    private function get_page_url() {
        $protocol = 'https://';
        if (DEVSITE) {
            $protocol = 'http://';
        }
        $url = $protocol . DOMAIN;
        if (array_key_exists('REQUEST_URI', $_SERVER)) {
            $url = $url . $_SERVER['REQUEST_URI'];
        }
        $this->data['page_url'] = $url;
    }

    /**
     * Work out what the page title and the keywords for it should be
     */
    private function get_page_title() {
        global $DATA, $this_page;

        $this->data['page_title'] = '';
        $sitetitle = $DATA->page_metadata($this_page, "sitetitle");
        $og_title = '';
        $this->keywords_title = '';

        if ($this_page == 'overview') {
            $this->data['page_title'] = $sitetitle . ': ' . $DATA->page_metadata($this_page, "title");

        } else {

            if ($page_title = $DATA->page_metadata($this_page, "title")) {
                $this->data['page_title'] = $page_title;
            }
            // We'll put this in the meta keywords tag.
            $this->keywords_title = $this->data['page_title'];

            $parent_page = $DATA->page_metadata($this_page, 'parent');
            if ($parent_title = $DATA->page_metadata($parent_page, 'title')) {
                if ($this->data['page_title']) {
                    $this->data['page_title'] .= ': ';
                }
                $this->data['page_title'] .= $parent_title;
            }

            if ($this->data['page_title'] == '') {
                $this->data['page_title'] = $sitetitle;
            } else {
                $og_title = $this->data['page_title'];
                $this->data['page_title'] .= ' - ' . $sitetitle;
            }
        }

        # for overriding the OpenGraph image
        $this->data['og_image'] = '';

        // Pages can specify a custom OpenGraph title if they want, otherwise
        // we use the page title without the trailing " - $sitetitle".
        $this->data['og_title'] = $DATA->page_metadata($this_page, "og_title") ?: $og_title;

    }

    private function get_page_keywords() {
        global $DATA, $this_page;
        ////////////////////////////////////////////////////////////
        // Meta keywords
        if (!$this->data['meta_keywords'] = $DATA->page_metadata($this_page, "meta_keywords")) {
            $this->data['meta_keywords'] = $this->keywords_title;
            if ($this->data['meta_keywords']) {
                $this->data['meta_keywords'] .= ', ';
            }
            $this->data['meta_keywords'] .= 'Hansard, Official Report, Parliament, government, House of Commons, House of Lords, MP, Peer, Member of Parliament, MPs, Peers, Lords, Commons, Scottish Parliament, Northern Ireland Assembly, MSP, MLA, MSPs, MLAs, London Assembly Members, MS, MSs, Welsh Parliament, Senedd Cymru, Senedd, Member of the Senedd';
        }

        $this->data['meta_description'] = gettext("Making it easy to keep an eye on the UK’s parliaments. Discover who represents you, how they’ve voted and what they’ve said in debates.");
        if ($DATA->page_metadata($this_page, "meta_description")) {
            $this->data['meta_description'] = $DATA->page_metadata($this_page, "meta_description");
        }
    }

    private function get_header_links() {
        global $this_page;

        $this->data['header_links'] = array();
        if ($this_page != 'overview') {

            $URL = new \MySociety\TheyWorkForYou\Url('overview');

            $this->data['header_links'][] = array(
                'rel'   => 'start',
                'title' => 'Home',
                'href'  => $URL->generate()
            );

        }
    }

    private function generate_next_prev_link($nextprev, $linktype) {
        $link = null;
        if (isset($nextprev[$linktype]) && isset($nextprev[$linktype]['url'])) {

            if (isset($nextprev[$linktype]['body'])) {
                $linktitle = _htmlentities( trim_characters($nextprev[$linktype]['body'], 0, 40) );
                if (isset($nextprev[$linktype]['speaker']) &&
                    count($nextprev[$linktype]['speaker']) > 0) {
                    $linktitle = $nextprev[$linktype]['speaker']['name'] . ': ' . $linktitle;
                }

            } elseif (isset($nextprev[$linktype]['hdate'])) {
                $linktitle = format_date($nextprev[$linktype]['hdate'], SHORTDATEFORMAT);
            }

            $link = array(
                'rel'   => $linktype,
                'title' => $linktitle,
                'href'  => $nextprev[$linktype]['url']
            );
        }

        return $link;
    }

    private function get_next_prev_links() {
        global $DATA, $this_page;

        $nextprev = $DATA->page_metadata($this_page, "nextprev");

        if ($nextprev) {
            // Four different kinds of back/forth links we might build.
            $links = array ("first", "prev", "up", "next", "last");

            foreach ($links as $type) {
                if ( $link = $this->generate_next_prev_link( $nextprev, $type ) ) {

                    $this->data['header_links'][] = $link;
                }
            }
        }
    }

    private function get_rss_link() {
        global $DATA, $this_page;

        if ($DATA->page_metadata($this_page, 'rss')) {
            // If this page has an RSS feed set.
            $this->data['page_rss_url'] = 'https://' . DOMAIN . WEBPATH . $DATA->page_metadata($this_page, 'rss');
        }
    }

    // We work out which of the items in the top and bottom menus
    // are highlighted
    private function get_menu_highlights() {
        global $DATA, $this_page;

        $parent = $DATA->page_metadata($this_page, 'parent');

        if (!$parent) {

            $top_highlight = $this_page;
            $bottom_highlight = '';

            $selected_top_link = $DATA->page_metadata('hansard', 'menu');
            $url = new \MySociety\TheyWorkForYou\Url('hansard');
            $selected_top_link['link'] = $url->generate();

        } else {

            $parents = array($parent);
            $p = $parent;
            while ($p) {
                $p = $DATA->page_metadata($p, 'parent');
                if ($p) {
                    $parents[] = $p;
                }
            }

            $top_highlight = array_pop($parents);
            if (!$parents) {
                // No grandparent - this page's parent is in the top menu.
                // We're on one of the pages linked to by the bottom menu.
                // So highlight it and its parent.
                $bottom_highlight = $this_page;
            } else {
                // This page is not in either menu. So highlight its parent
                // (in the bottom menu) and its grandparent (in the top).
                $bottom_highlight = array_pop($parents);
            }

            $selected_top_link = $DATA->page_metadata($top_highlight, 'menu');
            if (!$selected_top_link) {
                # Just in case something's gone wrong
                $selected_top_link = $DATA->page_metadata('hansard', 'menu');
            }
            $url = new \MySociety\TheyWorkForYou\Url($top_highlight);
            $selected_top_link['link'] = $url->generate();

        }

        if ($top_highlight == 'hansard') {
            $section = 'uk';
            $selected_top_link['text'] = 'UK';
        } elseif ($top_highlight == 'ni_home') {
            $section = 'ni';
            $selected_top_link['text'] = 'Northern Ireland';
        } elseif ($top_highlight == 'sp_home') {
            $section = 'scotland';
            $selected_top_link['text'] = 'Scotland';
        } elseif ($top_highlight == 'wales_home') {
            $section = 'wales';
            $selected_top_link['text'] = gettext('Wales');
        } elseif ($top_highlight == 'london_home') {
            $section = 'london';
            $selected_top_link['text'] = 'London Assembly';
        } else {
            $section = '';
        }

        // if we're searching within a parliament, put this in the top bar
        if ($this_page == "search") {
            if (isset($_GET['section'])) {
                $section = $_GET['section'];
                if ($section == 'scotland') {
                    $selected_top_link['text'] = 'Scotland';
                } elseif ($section == 'ni') {
                    $selected_top_link['text'] = 'Northern Ireland';
                } elseif ($section == 'wales') {
                    $selected_top_link['text'] = gettext('Wales');
                } elseif ($section == 'london') {
                    $selected_top_link['text'] = 'London Assembly';
                } else {
                    $selected_top_link['text'] = 'UK';
                }
            }
        }

        // for the alerts page, put the most recent membership's house
        // in the top bar
        if ($this_page == "alert"){
            if (isset($_GET['pid'])) {
                $pid = $_GET['pid'];
                $person = new \MySociety\TheyWorkForYou\Member(array('person_id' => $pid));
                $membership = $person->getMostRecentMembership();
                $parliament = $membership['house'];
                if ($parliament == 'ni') {
                    $selected_top_link['text'] = 'Northern Ireland';
                } elseif ($parliament == HOUSE_TYPE_SCOTLAND) {
                    $selected_top_link['text'] = 'Scotland';
                } elseif ($parliament == HOUSE_TYPE_WALES) {
                    $selected_top_link['text'] = gettext('Wales');
                } elseif ($parliament == HOUSE_TYPE_LONDON_ASSEMBLY) {
                    $selected_top_link['text'] = 'London Assembly';
                }
            }

        }

        $this->nav_highlights = array(
            'top' => $top_highlight,
            'bottom' => $bottom_highlight,
            'top_selected' => $selected_top_link,
            'section' => $section,
        );
    }

    private function get_top_and_bottom_links() {
        global $DATA;

        // Page names mapping to those in metadata.php.
        // Links in the top menu, and the sublinks we see if
        // we're within that section.
        $nav_items = array (
            array('home'),
            array('hansard', 'mps', 'peers', 'alldebatesfront', 'wranswmsfront', 'pbc_front', 'divisions_recent', 'calendar_summary'),
            array('sp_home', 'spoverview', 'msps', 'spdebatesfront', 'spwransfront'),
            array('ni_home', 'nioverview', 'mlas'),
            array('wales_home', 'seneddoverview', 'mss', 'wales_debates'),
            array('london_home', 'lmqsfront', 'london-assembly-members'),
        );

        $this->data['assembly_nav_links'] = array();
        $this->data['section_nav_links'] = array();

        //get the top and bottom links
        foreach ($nav_items as $bottompages) {
            $toppage = array_shift($bottompages);

            // Generate the links for the top menu.

            // What gets displayed for this page.
            $menudata = $DATA->page_metadata($toppage, 'menu');
            $title = '';
            if ($menudata) {
                $text = $menudata['text'];
                $title = $menudata['title'];
            }
            if (!$title) {
                continue;
            }

            //get link and description for the menu ans add it to the array
            $class = $toppage == $this->nav_highlights['top'] ? 'on' : '';

            // if current language is cy and the page does not start with /senedd/
            // we need to escape back to the english site
            $URL = new \MySociety\TheyWorkForYou\Url($toppage);
            $url = $URL->generate();
            if (LANGUAGE == 'cy' && strpos($url, '/senedd/') === false ) {
                $url = "//" . DOMAIN . $url;
            }

            $top_link = array(
                'href'    => $url,
                'title'   => $title,
                'classes' => $class,
                'text'    => $text
            );
            array_push($this->data['assembly_nav_links'], $top_link);

            if ($toppage == $this->nav_highlights['top']) {

                // This top menu link is highlighted, so generate its bottom menu.
                foreach ($bottompages as $bottompage) {
                    $menudata = $DATA->page_metadata($bottompage, 'menu');
                    $text = $menudata['text'];
                    $title = $menudata['title'];
                    // Where we're linking to.
                    $URL = new \MySociety\TheyWorkForYou\Url($bottompage);
                    $class = $bottompage == $this->nav_highlights['bottom'] ? 'on' : '';
                    $this->data['section_nav_links'][] = array(
                        'href'    => $URL->generate(),
                        'title'   => $title,
                        'classes' => $class,
                        'text'    => $text
                    );
                }
            }
        }

        $this->data['assembly_nav_current'] = $this->nav_highlights['top_selected']['text'];
    }

}
