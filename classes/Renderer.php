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
        include_once INCLUDESPATH . 'postcode.inc';

        ////////////////////////////////////////////////////////////
        // Find the user's country. Used by header, so a safe bit to do regardless.
        if (preg_match('#^[A-Z]{2}$#i', get_http_var('country'))) {
            $data['country'] = strtoupper(get_http_var('country'));
        } else {
            $data['country'] = Gaze::get_country_by_ip($_SERVER["REMOTE_ADDR"]);
        }

        ////////////////////////////////////////////////////////////
        // Get the page data
        global $DATA, $this_page, $THEUSER;

        $header = new Renderer\Header();
        $data = array_merge($header->data, $data);

        ////////////////////////////////////////////////////////////
        // User Navigation Links

        $data['user_nav_links'] = array();

        // We may want to send the user back to this current page after they've
        // joined, logged out or logged in. So we put the URL in $returl.
        $URL = new \URL($this_page);
        $returl = $URL->generate('none');

        //user logged in
        if ($THEUSER->isloggedin()) {

            // The 'Edit details' link.
            $menudata   = $DATA->page_metadata('userviewself', 'menu');
            $edittext   = $menudata['text'];
            $edittitle  = $menudata['title'];
            $EDITURL    = new \URL('userviewself');
            if ($this_page == 'userviewself' || $this_page == 'useredit' || $header->top_highlight == 'userviewself') {
                $editclass = 'on';
            } else {
                $editclass = '';
            }

            // The 'Log out' link.
            $menudata   = $DATA->page_metadata('userlogout', 'menu');
            $logouttext = $menudata['text'];
            $logouttitle= $menudata['title'];

            $LOGOUTURL  = new \URL('userlogout');
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

            $JOINURL    = new \URL('userjoin');
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

            $LOGINURL   = new \URL('userlogin');
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
            if (postcode_is_scottish($THEUSER->postcode()))
                $items[] = 'yourmsp';
            elseif (postcode_is_ni($THEUSER->postcode()))
                $items[] = 'yourmla';
            foreach ($items as $item) {
                $menudata   = $DATA->page_metadata($item, 'menu');
                $logintext  = $menudata['text'];
                $URL = new \URL($item);
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

        $SEARCH = new \URL('search');
        $SEARCH->reset();
        $data['search_url'] = $SEARCH->generate();

        ////////////////////////////////////////////////////////////
        // Search URL
        // Footer Links

        $footer = new Renderer\Footer();
        $data['footer_links'] = $footer->data;

        # banner text
        $b = new Model\Banner;
        $data['banner_text'] = $b->get_text();

        # mini survey
        // we never want to display this on the front page or any
        // other survey page we might have
        if (!in_array($this_page, array('survey', 'overview'))) {
            $mini = new MiniSurvey;
            $data['mini_survey'] = $mini->get_values();
        }

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

}
