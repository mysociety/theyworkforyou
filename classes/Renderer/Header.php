<?php

namespace MySociety\TheyWorkForYou\Renderer;

/**
 * Template Header Renderer
 *
 * Prepares variables for inclusion in a template header.
 */

class Header
{

    public $top_highlight;
    public $nav_highlights;

    /**
     * Generate header data
     *
     * @param array  $data     An associative array of data to be made available to the template.
     */

    public function get_data($data = array())
    {
        global $DATA, $this_page, $THEUSER;

        $data = $this->get_page_title_and_keywords($data);
        $data = $this->get_header_links($data);
        $data = $this->get_next_prev_links($data);
        $data = $this->get_rss_link($data);
        $data = $this->get_menu_highlights($data);
        $data = $this->get_top_and_bottom_links($data);


        # Robots header
        if (DEVSITE) {
            $data['robots'] = 'noindex,nofollow';
        } elseif ($robots = $DATA->page_metadata($this_page, 'robots')) {
            $data['robots'] = $robots;
        }

        return $data;
    }

    /**
     * Work out what the page title and the keywords for it should be
     */
    private function get_page_title_and_keywords($data) {
        global $DATA, $this_page;

        $data['page_title'] = '';
        $sitetitle = $DATA->page_metadata($this_page, "sitetitle");
        $keywords_title = '';

        if ($this_page == 'overview') {
            $data['page_title'] = $sitetitle . ': ' . $DATA->page_metadata($this_page, "title");

        } else {

            if ($page_title = $DATA->page_metadata($this_page, "title")) {
                $data['page_title'] = $page_title;
            }
            // We'll put this in the meta keywords tag.
            $keywords_title = $data['page_title'];

            $parent_page = $DATA->page_metadata($this_page, 'parent');
            if ($parent_title = $DATA->page_metadata($parent_page, 'title')) {
                if ($data['page_title']) $data['page_title'] .= ': ';
                $data['page_title'] .= $parent_title;
            }

            if ($data['page_title'] == '') {
                $data['page_title'] = $sitetitle;
            } else {
                $data['page_title'] .= ' - ' . $sitetitle;
            }
        }

        ////////////////////////////////////////////////////////////
        // Meta keywords
        if (!$data['meta_keywords'] = $DATA->page_metadata($this_page, "meta_keywords")) {
            $data['meta_keywords'] = $keywords_title;
            if ($data['meta_keywords']) $data['meta_keywords'] .= ', ';
            $data['meta_keywords'] .= 'Hansard, Official Report, Parliament, government, House of Commons, House of Lords, MP, Peer, Member of Parliament, MPs, Peers, Lords, Commons, Scottish Parliament, Northern Ireland Assembly, MSP, MLA, MSPs, MLAs';
        }

        $data['meta_description'] = '';
        if ($DATA->page_metadata($this_page, "meta_description")) {
            $data['meta_description'] = $DATA->page_metadata($this_page, "meta_description");
        }

        return $data;
    }

    private function get_header_links($data) {
        global $this_page;

        $data['header_links'] = array();
        if ($this_page != 'overview') {

            $URL = new \URL('overview');

            $data['header_links'][] = array(
                'rel'   => 'start',
                'title' => 'Home',
                'href'  => $URL->generate()
            );

        }

        return $data;
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

    private function get_next_prev_links($data) {
        global $DATA, $this_page;

        $nextprev = $DATA->page_metadata($this_page, "nextprev");

        if ($nextprev) {
            // Four different kinds of back/forth links we might build.
            $links = array ("first", "prev", "up", "next", "last");

            foreach ($links as $type) {
                if ( $link = $this->generate_next_prev_link( $nextprev, $type ) ) {

                    $data['header_links'][] = $link;
                }
            }
        }

        return $data;
    }

    private function get_rss_link($data) {
        global $DATA, $this_page;

        if ($DATA->page_metadata($this_page, 'rss')) {
            // If this page has an RSS feed set.
            $data['page_rss_url'] = 'http://' . DOMAIN . WEBPATH . $DATA->page_metadata($this_page, 'rss');
        }

        return $data;
    }

    // We work out which of the items in the top and bottom menus
    // are highlighted
    private function get_menu_highlights($data) {
        global $DATA, $this_page;

        $parent = $DATA->page_metadata($this_page, 'parent');

        if (!$parent) {

            $top_highlight = $this_page;
            $bottom_highlight = '';

            $selected_top_link = $DATA->page_metadata('hansard', 'menu');
            $url = new \URL('hansard');
            $selected_top_link['link'] = $url->generate();

        } else {

            $parents = array($parent);
            $p = $parent;
            while ($p) {
                $p = $DATA->page_metadata($p, 'parent');
                if ($p) $parents[] = $p;
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
            $url = new \URL($top_highlight);
            $selected_top_link['link'] = $url->generate();

        }

        if ($top_highlight == 'hansard') {
            $section = 'uk';
            $selected_top_link['text'] = 'UK';
        } elseif ($top_highlight == 'ni_home') {
            $section = 'ni';
            $selected_top_link['text'] = 'NORTHERN IRELAND';
        } elseif ($top_highlight == 'sp_home') {
            $section = 'scotland';
            $selected_top_link['text'] = 'SCOTLAND';
        } else {
            $section = '';
        }

        $this->nav_highlights = array(
            'top' => $top_highlight,
            'bottom' => $bottom_highlight,
            'top_selected' => $selected_top_link,
            'section' => $section,
        );

        $this->top_highlight = $top_highlight;

        return $data;
    }

    private function get_top_and_bottom_links($data) {
        global $DATA;

        // Page names mapping to those in metadata.php.
        // Links in the top menu, and the sublinks we see if
        // we're within that section.
        $nav_items = array (
            array('home'),
            array('hansard', 'mps', 'peers', 'alldebatesfront', 'wranswmsfront', 'pbc_front', 'calendar_summary'),
            array('sp_home', 'spoverview', 'msps', 'spdebatesfront', 'spwransfront'),
            array('ni_home', 'nioverview', 'mlas'),
            array('wales_home'),
        );

        $data['assembly_nav_links'] = array();
        $data['section_nav_links'] = array();

        //get the top and bottom links
        foreach ($nav_items as $bottompages) {
            $toppage = array_shift($bottompages);

            // Generate the links for the top menu.

            // What gets displayed for this page.
            $menudata = $DATA->page_metadata($toppage, 'menu');
                $text = $menudata['text'];
                $title = $menudata['title'];
            if (!$title) continue;

                //get link and description for the menu ans add it to the array
            $class = $toppage == $this->nav_highlights['top'] ? 'on' : '';
                $URL = new \URL($toppage);
                $top_link = array(
                    'href'    => $URL->generate(),
                    'title'   => $title,
                    'classes' => $class,
                    'text'    => $text
                );
                array_push($data['assembly_nav_links'], $top_link);

            if ($toppage == $this->nav_highlights['top']) {

                // This top menu link is highlighted, so generate its bottom menu.
                foreach ($bottompages as $bottompage) {
                    $menudata = $DATA->page_metadata($bottompage, 'menu');
                    $text = $menudata['text'];
                    $title = $menudata['title'];
                    // Where we're linking to.
                    $URL = new \URL($bottompage);
                    $class = $bottompage == $this->nav_highlights['bottom'] ? 'on' : '';
                    $data['section_nav_links'][] = array(
                        'href'    => $URL->generate(),
                        'title'   => $title,
                        'classes' => $class,
                        'text'    => $text
                    );
                }
            }
        }

        $data['assembly_nav_current'] = $this->nav_highlights['top_selected']['text'];

        return $data;
    }

}
