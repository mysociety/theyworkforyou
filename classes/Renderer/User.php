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
    }

    private function setupNavLinks() {
        $this->data['user_nav_links'] = array();

        // We may want to send the user back to this current page after they've
        // joined, logged out or logged in. So we put the URL in $returl.
        $URL = new \MySociety\TheyWorkForYou\Url($this->page);
        $this->returl = $URL->generate('none');

        //user logged in
        if ($this->user->isloggedin()) {
            $this->addLoggedInLinks();
        } else {
            $this->addLoggedOutLinks();
        }
    }

    private function AddLangSwitcher(){
        if (preg_match('#^(senedd|wales|ms(?!p))#', $this->page)) {
            $href = $_SERVER['REQUEST_URI'];
            if (LANGUAGE == 'cy') {
                $text = 'English';
                $href = "//" . DOMAIN . $href;
            } else {
                $text = 'Cymraeg';
                if (strpos(DOMAIN, 'www') !== false) {
                    $href = "//" . str_replace('www.', 'cy.', DOMAIN) . $href;
                } else {
                    $href = "//cy." . DOMAIN . $href;
                }
            }
            $this->data['user_nav_links'][] = array(
                'href' => $href,
                'classes' => '',
                'title' => '',
                'text' => $text,
            );
        }
    }

    private function addLoggedInLinks() {
        // The 'Edit details' link.
        $menudata   = $this->pagedata->page_metadata('userviewself', 'menu');
        $edittitle  = $menudata['title'];
        $EDITURL    = new \MySociety\TheyWorkForYou\Url('userviewself');
        if ($this->page == 'userviewself' || $this->page == 'useredit' ) {
            $editclass = 'on';
        } else {
            $editclass = '';
        }

        // The 'Log out' link.
        $menudata   = $this->pagedata->page_metadata('userlogout', 'menu');
        $logouttext = $menudata['text'];
        $logouttitle= $menudata['title'];

        $LOGOUTURL  = new \MySociety\TheyWorkForYou\Url('userlogout');
        if ($this->page != 'userlogout') {
            $LOGOUTURL->insert(array("ret"=>$this->returl));
            $logoutclass = '';
        } else {
            $logoutclass = 'on';
        }

        $username = $this->user->firstname() . ' ' . $this->user->lastname();

        $this->addRepLinks();

        $this->data['user_nav_links'][] = array(
            'href'    => $EDITURL->generate(),
            'title'   => $edittitle,
            'classes' => $editclass,
            'text'    => _htmlentities($username)
        );

        $this->data['user_nav_links'][] = array(
            'href'    => $LOGOUTURL->generate(),
            'title'   => $logouttitle,
            'classes' => $logoutclass,
            'text'    => $logouttext
        );

        $this->addContactLink();
        $this->addDonateLink();
        $this->AddLangSwitcher();
    }

    private function addLoggedOutLinks() {
        // The 'Join' link.
        $menudata   = $this->pagedata->page_metadata('userjoin', 'menu');
        $jointext   = $menudata['text'];
        $jointitle  = $menudata['title'];

        $JOINURL    = new \MySociety\TheyWorkForYou\Url('userjoin');
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

        $LOGINURL = new \MySociety\TheyWorkForYou\Url('userlogin');
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

        $this->addRepLinks();
        $this->addContactLink();
        $this->addDonateLink();
        $this->AddLangSwitcher();
    }

    // add links to your MP etc if postcode set
    private function addRepLinks() {
        if ($this->user->postcode_is_set()) {

            $areas = \MySociety\TheyWorkForYou\Utility\Postcode::postcodeToConstituencies($this->user->postcode());
            $items = array('yourmp');
            if (isset($areas['SPC'])) {
                $items[] = 'yourmsp';
            } elseif (isset($areas['NIE'])) {
                $items[] = 'yourmla';
            } elseif (isset($areas['WAC'])) {
                $items[] = 'yourms';
            }

            foreach ($items as $item) {
                $menudata   = $this->pagedata->page_metadata($item, 'menu');
                $logintext  = $menudata['text'];
                $URL = new \MySociety\TheyWorkForYou\Url($item);
                $this->data['user_nav_links'][] = array(
                    'href'    => $URL->generate(),
                    'title'   => '',
                    'classes' => '',
                    'text'    => $logintext
                );
            }
        }

    }

    private function addContactLink() {
        $menudata = $this->pagedata->page_metadata('contact', 'menu');
        $text = $menudata['text'];
        $title = $menudata['title'];
        $url = new \MySociety\TheyWorkForYou\Url('contact');
        $this->data['user_nav_links'][] = array(
            'href'    => $url->generate(),
            'title'   => $title,
            'classes' => '',
            'text'    => $text
        );
    }

    private function addDonateLink() {
        if (LANGUAGE == 'cy') {
            return;
        }
        $menudata = $this->pagedata->page_metadata('donate', 'menu');
        $text = $menudata['text'];
        $title = $menudata['title'];
        $url = new \MySociety\TheyWorkForYou\Url('donate');
        $this->data['user_nav_links'][] = array(
            'href'    => $url->generate(),
            'title'   => $title,
            'classes' => 'donate-button',
            'text'    => $text
        );
    }

}
