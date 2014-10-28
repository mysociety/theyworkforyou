<?php

namespace MySociety\TheyWorkForYou;

/**
 * Template Renderer
 *
 * Prepares variables for inclusion in a template, as well as handling variables
 * for use in header and footer.
 */

class Renderer
{

    /**
     * Output Page
     *
     * Assembles a completed page from template and sends it to output.
     *
     * @param string $template The name of the template file to load.
     * @param array  $data     An associative array of data to be made available to the template.
     */

    public static function output($template, $data = array())
    {

        // Include includes.
        // TODO: Wrap these in a class somewhere autoloadable.

        ////////////////////////////////////////////////////////////
        // Find the user's country. Used by header, so a safe bit to do regardless.
        if (get_http_var('country')) {
            $data['country'] = strtoupper(get_http_var('country'));
        } else {
            $data['country'] = Utility\Gaze::getCountryByIp($_SERVER["REMOTE_ADDR"]);
        }

        ////////////////////////////////////////////////////////////
        // Get the page data
        global $DATA, $this_page, $THEUSER;

        ////////////////////////////////////////////////////////////
        // Assemble the page title
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

        ////////////////////////////////////////////////////////////
        // Header <link>s

        $header_links = array();

        if ($this_page != 'overview') {

            $URL = new Url('overview');

            $data['header_links'][] = array(
                'rel'   => 'start',
                'title' => 'Home',
                'href'  => $URL->generate()
            );

        }

        ////////////////////////////////////////////////////////////
        // Create the next/prev/up links for navigation.
        // Their data is put in the metadata in hansardlist.php

        $nextprev = $DATA->page_metadata($this_page, "nextprev");

        if ($nextprev) {
            // Four different kinds of back/forth links we might build.
            $links = array ("first", "prev", "up", "next", "last");

            foreach ($links as $n => $type) {
                if (isset($nextprev[$type]) && isset($nextprev[$type]['listurl'])) {

                    if (isset($nextprev[$type]['body'])) {
                        $linktitle = _htmlentities( trim_characters($nextprev[$type]['body'], 0, 40) );
                        if (isset($nextprev[$type]['speaker']) &&
                            count($nextprev[$type]['speaker']) > 0) {
                            $linktitle = $nextprev[$type]['speaker']['first_name'] . ' ' . $nextprev[$type]['speaker']['last_name'] . ': ' . $linktitle;
                        }

                    } elseif (isset($nextprev[$type]['hdate'])) {
                        $linktitle = format_date($nextprev[$type]['hdate'], SHORTDATEFORMAT);
                    }

                    $data['header_links'][] = array(
                        'rel'   => $type,
                        'title' => $linktitle,
                        'href'  => $nextprev[$type]['listurl']
                    );
                }
            }
        }

        ////////////////////////////////////////////////////////////
        // Page RSS URL

        if ($DATA->page_metadata($this_page, 'rss')) {
            // If this page has an RSS feed set.
            $data['page_rss_url'] = 'http://' . DOMAIN . WEBPATH . $DATA->page_metadata($this_page, 'rss');
        }

        ////////////////////////////////////////////////////////////
        // Site Navigation Links

        $data['assembly_nav_links'] = array();
        $data['section_nav_links'] = array();

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

        // We work out which of the items in the top and bottom menus
        // are highlighted - $top_highlight and $bottom_highlight respectively.
        $parent = $DATA->page_metadata($this_page, 'parent');

        if (!$parent) {

            $top_highlight = $this_page;
            $bottom_highlight = '';

            $selected_top_link = $DATA->page_metadata('hansard', 'menu');
            $url = new Url('hansard');
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
            $url = new Url($top_highlight);
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

        $nav_highlights = array(
            'top' => $top_highlight,
            'bottom' => $bottom_highlight,
            'top_selected' => $selected_top_link,
            'section' => $section,
        );

        //get the top and bottom links
        $top_links = array();
        $bottom_links = array();
        foreach ($nav_items as $bottompages) {
            $toppage = array_shift($bottompages);

            // Generate the links for the top menu.

            // What gets displayed for this page.
            $menudata = $DATA->page_metadata($toppage, 'menu');
                $text = $menudata['text'];
                $title = $menudata['title'];
            if (!$title) continue;

                //get link and description for the menu ans add it to the array
            $class = $toppage == $nav_highlights['top'] ? 'on' : '';
                $URL = new Url($toppage);
                $top_link = array(
                    'href'    => $URL->generate(),
                    'title'   => $title,
                    'classes' => $class,
                    'text'    => $text
                );
                array_push($data['assembly_nav_links'], $top_link);

            if ($toppage == $nav_highlights['top']) {

                // This top menu link is highlighted, so generate its bottom menu.
                foreach ($bottompages as $bottompage) {
                    $menudata = $DATA->page_metadata($bottompage, 'menu');
                    $text = $menudata['text'];
                    $title = $menudata['title'];
                    // Where we're linking to.
                    $URL = new Url($bottompage);
                    $class = $bottompage == $nav_highlights['bottom'] ? 'on' : '';
                    $data['section_nav_links'][] = array(
                        'href'    => $URL->generate(),
                        'title'   => $title,
                        'classes' => $class,
                        'text'    => $text
                    );
                }
            }
        }

        $data['assembly_nav_current'] = $nav_highlights['top_selected']['text'];

        ////////////////////////////////////////////////////////////
        // User Navigation Links

        $data['user_nav_links'] = array();

        // We may want to send the user back to this current page after they've
        // joined, logged out or logged in. So we put the URL in $returl.
        $URL = new Url($this_page);
        $returl = $URL->generate('none');

        //user logged in
        if ($THEUSER->isloggedin()) {

            // The 'Edit details' link.
            $menudata   = $DATA->page_metadata('userviewself', 'menu');
            $edittext   = $menudata['text'];
            $edittitle  = $menudata['title'];
            $EDITURL    = new Url('userviewself');
            if ($this_page == 'userviewself' || $this_page == 'useredit' || $top_highlight == 'userviewself') {
                $editclass = 'on';
            } else {
                $editclass = '';
            }

            // The 'Log out' link.
            $menudata   = $DATA->page_metadata('userlogout', 'menu');
            $logouttext = $menudata['text'];
            $logouttitle= $menudata['title'];

            $LOGOUTURL  = new Url('userlogout');
            if ($this_page != 'userlogout') {
                $LOGOUTURL->insert(array("ret"=>$returl));
                $logoutclass = '';
            } else {
                $logoutclass = 'on';
            }

            $username = $THEUSER->firstname() . ' ' . $THEUSER->lastname();

            $data['user_nav_links'][] = array(
                'href'    => $LOGOUTURL->generate(),
                'title'   => $logouttitle,
                'classes' => $logoutclass,
                'text'    => $logouttext
            );
            $data['user_nav_links'][] = array(
                'href'    => $EDITURL->generate(),
                'title'   => $edittitle,
                'classes' => $editclass,
                'text'    => $edittext
            );
            $data['user_nav_links'][] = array(
                'href'    => $EDITURL->generate(),
                'title'   => $edittitle,
                'classes' => $editclass,
                'text'    => _htmlentities($username)
            );

        } else {
        // User not logged in

            // The 'Join' link.
            $menudata   = $DATA->page_metadata('userjoin', 'menu');
            $jointext   = $menudata['text'];
            $jointitle  = $menudata['title'];

            $JOINURL    = new Url('userjoin');
            if ($this_page != 'userjoin') {
                if ($this_page != 'userlogout' && $this_page != 'userlogin') {
                    // We don't do this on the logout page, because then the user
                    // will return straight to the logout page and be logged out
                    // immediately!
                    $JOINURL->insert(array("ret"=>$returl));
                }
                $joinclass = '';
            } else {
                $joinclass = 'on';
            }

            // The 'Log in' link.
            $menudata   = $DATA->page_metadata('userlogin', 'menu');
            $logintext  = $menudata['text'];
            $logintitle = $menudata['title'];

            $LOGINURL   = new Url('userlogin');
            if ($this_page != 'userlogin') {
                if ($this_page != "userlogout" &&
                    $this_page != "userpassword" &&
                    $this_page != 'userjoin') {
                    // We don't do this on the logout page, because then the user
                    // will return straight to the logout page and be logged out
                    // immediately!
                    // And it's also silly if we're sent back to Change Password.
                    // And the join page.
                    $LOGINURL->insert(array("ret"=>$returl));
                }
                $loginclass = '';
            } else {
                $loginclass = 'on';
            }

                $data['user_nav_links'][] = array(
                    'href'    => $LOGINURL->generate(),
                    'title'   => $logintitle,
                    'classes' => $loginclass,
                    'text'    => $logintext
                );

                $data['user_nav_links'][] = array(
                    'href'    => $JOINURL->generate(),
                    'title'   => $jointitle,
                    'classes' => $joinclass,
                    'text'    => $jointext
                );
        }

        // If the user's postcode is set, then we add a link to Your MP etc.
        if ($THEUSER->postcode_is_set()) {
            $items = array('yourmp');
            if (\MySociety\TheyWorkForYou\Utility\Postcode::postcodeIsScottish($THEUSER->postcode()))
                $items[] = 'yourmsp';
            elseif (\MySociety\TheyWorkForYou\Utility\Postcode::postcodeIsNi($THEUSER->postcode()))
                $items[] = 'yourmla';
            foreach ($items as $item) {
                $menudata   = $DATA->page_metadata($item, 'menu');
                $logintext  = $menudata['text'];
                $logintitle = $menudata['title'];
                $URL = new Url($item);
                $data['user_nav_links'][] = array(
                    'href'    => $URL->generate(),
                    'title'   => '',
                    'classes' => '',
                    'text'    => $logintext
                );
            }
        }

        ////////////////////////////////////////////////////////////
        // Search URL

        $SEARCH = new Url('search');
        $SEARCH->reset();
        $data['search_url'] = $SEARCH->generate();

        ////////////////////////////////////////////////////////////
        // Search URL
        // Footer Links

        $data['footer_links']['about'] = self::get_menu_links(array ('help', 'about', 'linktous', 'houserules', 'blog', 'news', 'contact', 'privacy'));
        $data['footer_links']['assemblies'] = self::get_menu_links(array ('hansard', 'sp_home', 'ni_home', 'wales_home', 'boundaries'));
        $data['footer_links']['international'] = self::get_menu_links(array ('newzealand', 'australia', 'ireland', 'mzalendo'));
        $data['footer_links']['tech'] = self::get_menu_links(array ('code', 'api', 'data', 'pombola', 'devmailinglist', 'irc'));

        ////////////////////////////////////////////////////////////
        // Unpack the data we've been passed so it's available for use in the templates.

        extract($data);

        ////////////////////////////////////////////////////////////
        // Require the templates and output

        header('Content-Type: text/html; charset=iso-8859-1');
        require_once INCLUDESPATH . 'easyparliament/templates/html/header.php';
        require_once INCLUDESPATH . 'easyparliament/templates/html/' . $template . '.php';
        require_once INCLUDESPATH . 'easyparliament/templates/html/footer.php';
    }

    /**
     * Get Menu Links
     *
     * Takes an array of pages and returns an array suitable for use in links.
     */

    private static function get_menu_links($pages) {

        global $DATA, $this_page;
        $links = array();

        foreach ($pages as $page) {

            //get meta data
            $menu = $DATA->page_metadata($page, 'menu');
            if ($menu) {
                $title = $menu['text'];
            } else {
                $title = $DATA->page_metadata($page, 'title');
            }
            $url = $DATA->page_metadata($page, 'url');
            $tooltip = $DATA->page_metadata($page, 'heading');

            //check for external vs internal menu links
            if (!valid_url($url)) {
                $URL = new Url($page);
                $url = $URL->generate();
            }

            //make the link
            if ($page == $this_page) {
                $links[] = array(
                    'href'    => '#',
                    'title'   => '',
                    'classes' => '',
                    'text'    => $title
                );
            } else {
                $links[] = array(
                    'href'    => $url,
                    'title'   => $tooltip,
                    'classes' => '',
                    'text'    => $title
                );
            }
        }

        return $links;
    }

}
