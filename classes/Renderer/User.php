<?php

namespace MySociety\TheyWorkForYou\Renderer;

/**
 * User data for headers
 */

class User
{
    public $data;

    private $user;
    private $pagedata;
    private $page;
    private $returl;

    public function __construct() {
        global $THEUSER, $DATA, $this_page;

        $this->user = $THEUSER;
        $this->pagedata = $DATA;
        $this->page = $this_page;
        $this->data = array();
        $this->setupNavLinks();
        $this->addRepLinks();
    }

    private function setupNavLinks() {

        $this->data['user_nav_links'] = array();

        // We may want to send the user back to this current page after they've
        // joined, logged out or logged in. So we put the URL in $returl.
        $URL = new \URL($this->page);
        $this->returl = $URL->generate('none');

        //user logged in
        if ($this->user->isloggedin()) {
            $this->addLoggedInLinks();
        } else {
            $this->addLoggedOutLinks();
        }
    }

    private function addLoggedInLinks() {
        // The 'Edit details' link.
        $menudata   = $this->pagedata->page_metadata('userviewself', 'menu');
        $edittext   = $menudata['text'];
        $edittitle  = $menudata['title'];
        $EDITURL    = new \URL('userviewself');
        if ($this->page == 'userviewself' || $this->page == 'useredit' ) {
            $editclass = 'on';
        } else {
            $editclass = '';
        }

        // The 'Log out' link.
        $menudata   = $this->pagedata->page_metadata('userlogout', 'menu');
        $logouttext = $menudata['text'];
        $logouttitle= $menudata['title'];

        $LOGOUTURL  = new \URL('userlogout');
        if ($this->page != 'userlogout') {
            $LOGOUTURL->insert(array("ret"=>$this->returl));
            $logoutclass = '';
        } else {
            $logoutclass = 'on';
        }

        $username = $this->user->firstname() . ' ' . $this->user->lastname();

        $this->data['user_nav_links'][] = array(
            'href'    => $LOGOUTURL->generate(),
            'title'   => $logouttitle,
            'classes' => $logoutclass,
            'text'    => $logouttext
        );
        $this->data['user_nav_links'][] = array(
            'href'    => $EDITURL->generate(),
            'title'   => $edittitle,
            'classes' => $editclass,
            'text'    => $edittext
        );
        $this->data['user_nav_links'][] = array(
            'href'    => $EDITURL->generate(),
            'title'   => $edittitle,
            'classes' => $editclass,
            'text'    => _htmlentities($username)
        );
    }

    private function addLoggedOutLinks() {
        // The 'Join' link.
        $menudata   = $this->pagedata->page_metadata('userjoin', 'menu');
        $jointext   = $menudata['text'];
        $jointitle  = $menudata['title'];

        $JOINURL    = new \URL('userjoin');
        if ($this->page != 'userjoin') {
            if ($this->page != 'userlogout' && $this->page != 'userlogin') {
                // We don't do this on the logout page, because then the user
                // will return straight to the logout page and be logged out
                // immediately!
                $JOINURL->insert(array("ret"=>$this->returl));
            }
            $joinclass = '';
        } else {
            $joinclass = 'on';
        }

        // The 'Log in' link.
        $menudata = $this->pagedata->page_metadata('userlogin', 'menu');
        $logintext = $menudata['text'];
        $logintitle = $menudata['title'];

        $LOGINURL = new \URL('userlogin');
        if ($this->page != 'userlogin') {
            if ($this->page != "userlogout" &&
                $this->page != "userpassword" &&
                $this->page != 'userjoin') {
                // We don't do this on the logout page, because then the user
                // will return straight to the logout page and be logged out
                // immediately!
                // And it's also silly if we're sent back to Change Password.
                // And the join page.
                $LOGINURL->insert(array("ret"=>$this->returl));
            }
            $loginclass = '';
        } else {
            $loginclass = 'on';
        }

        $this->data['user_nav_links'][] = array(
            'href'    => $LOGINURL->generate(),
            'title'   => $logintitle,
            'classes' => $loginclass,
            'text'    => $logintext
        );

        $this->data['user_nav_links'][] = array(
            'href'    => $JOINURL->generate(),
            'title'   => $jointitle,
            'classes' => $joinclass,
            'text'    => $jointext
        );
    }

    // add links to your MP etc if postcode set
    private function addRepLinks() {
        if ($this->user->postcode_is_set()) {
            include_once INCLUDESPATH . 'postcode.inc';

            $items = array('yourmp');
            if (postcode_is_scottish($this->user->postcode())) {
                $items[] = 'yourmsp';
            } elseif (postcode_is_ni($this->user->postcode())) {
                $items[] = 'yourmla';
            }

            foreach ($items as $item) {
                $menudata   = $this->pagedata->page_metadata($item, 'menu');
                $logintext  = $menudata['text'];
                $URL = new \URL($item);
                $this->data['user_nav_links'][] = array(
                    'href'    => $URL->generate(),
                    'title'   => '',
                    'classes' => '',
                    'text'    => $logintext
                );
            }
        }

    }

}
