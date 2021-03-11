<?php

include_once INCLUDESPATH . '../../commonlib/phplib/gaze.php';
include_once INCLUDESPATH . 'easyparliament/member.php';

class PAGE {

    // So we can tell from other places whether we need to output the page_start or not.
    // Use the page_started() function to do this.
    public $page_start_done = false;
    public $supress_heading = false;
    public $heading_displayed = false;

    // We want to know where we are with the stripes, the main structural elements
    // of most pages, so that if we output an error message we can wrap it in HTML
    // that won't break the rest of the page.
    // Changed in $this->stripe_start().
    public $within_stripe_main = false;
    public $within_stripe_sidebar = false;

    public function page_start() {
        if ( !$this->page_started() ) {
            $this->checkForAdmin();
            $this->displayHeader();
        }
    }

    private function displayHeader() {
        global $page_errors;
        $h = new MySociety\TheyWorkForYou\Renderer\Header();
        $u = new MySociety\TheyWorkForYou\Renderer\User();

        $data = $h->data;
        $data = array_merge($u->data, $data);
        if ( isset($page_errors) ) {
            $data['page_errors'] = $page_errors;
        }
        $data['banner_text'] = '';
        extract($data);
        require_once INCLUDESPATH . 'easyparliament/templates/html/header.php';

        echo '<div class="full-page legacy-page static-page"> <div class="full-page__row"> <div class="panel">';

        $this->page_start_done = true;
    }

    private function checkForAdmin() {
        global $DATA, $this_page, $THEUSER;
        $parent = $DATA->page_metadata($this_page, "parent");
        if ($parent == 'admin' && (!$THEUSER->isloggedin() || !$THEUSER->is_able_to('viewadminsection'))) {
            if (!$THEUSER->isloggedin()) {
                $THISPAGE = new \MySociety\TheyWorkForYou\Url($this_page);

                $LOGINURL = new \MySociety\TheyWorkForYou\Url('userlogin');
                $LOGINURL->insert(array('ret' => $THISPAGE->generate('none') ));

                $text = "<a href=\"" . $LOGINURL->generate() . "\">You'd better sign in!</a>";
            } else {
                $text = "That's all folks!";
            }
            $this_page = 'home';
            $this->displayHeader();
            echo $text;
            $this->page_end();
            exit();
        }
    }

    public function page_end() {
        if ( !$this->page_started() ) {
            $this->page_start();
        }

        echo '</div></div></div>';
        $footer = new MySociety\TheyWorkForYou\Renderer\Footer();
        $footer_links = $footer->data;
        require_once INCLUDESPATH . 'easyparliament/templates/html/footer.php';
    }

    public function page_started() {
        return $this->page_start_done == true ? true : false;
    }

    public function heading_displayed() {
        return $this->heading_displayed == true ? true : false;
    }

    public function within_stripe() {
        if ($this->within_stripe_main == true || $this->within_stripe_sidebar == true) {
            return true;
        } else {
            return false;
        }
    }

    public function within_stripe_sidebar() {
        if ($this->within_stripe_sidebar == true) {
            return true;
        } else {
            return false;
        }
    }

    public function stripe_start($type='side', $id='', $extra_class = '') {
        // $type is one of:
        //  'full' - a full width div
        //  'side' - a white stripe with a coloured sidebar.
        //           (Has extra padding at the bottom, often used for whole pages.)
        //  'head-1' - used for the page title headings in hansard.
        //  'head-2' - used for section/subsection titles in hansard.
        //  '1', '2' - For alternating stripes in listings.
        //  'time-1', 'time-2' - For displaying the times in hansard listings.
        //  'procedural-1', 'procedural-2' - For the proecdures in hansard listings.
        //  'foot' - For the bottom stripe on hansard debates/wrans listings.
        // $id is the value of an id for this div (if blank, not used).
        ?>
        <div class="stripe-<?php echo $type; ?><?php if ($extra_class != '') echo ' ' . $extra_class; ?>"<?php
        if ($id != '') {
            print ' id="' . $id . '"';
        }
        ?>>
            <div class="main">
<?php
        $this->within_stripe_main = true;
        // On most, uncomplicated pages, the first stripe on a page will include
        // the page heading. So, if we haven't already printed a heading on this
        // page, we do it now...
        if (!$this->heading_displayed() && $this->supress_heading != true) {
            $this->heading();
        }
    }


    public function stripe_end ($contents = array(), $extra = '') {
        // $contents is an array containing 0 or more hashes.
        // Each hash has two values, 'type' and 'content'.
        // 'Type' could be one of these:
        //  'include' - will include a sidebar named after the value of 'content'.php.
        //  'nextprev' - $this->nextprevlinks() is called ('content' currently ignored).
        //  'html' - The value of the 'content' is simply displayed.
        //  'extrahtml' - The value of the 'content' is displayed after the sidebar has
        //                  closed, but within this stripe.

        // If $contents is empty then '&nbsp;' will be output.

        /* eg, take this hypothetical array:
            $contents = array(
                array (
                    'type'  => 'include',
                    'content'   => 'mp'
                ),
                array (
                    'type'  => 'html',
                    'content'   => "<p>This is your MP</p>\n"
                ),
                array (
                    'type'  => 'nextprev'
                ),
                array (
                    'type'  => 'none'
                ),
                array (
                    'extrahtml' => '<a href="blah">Source</a>'
                )
            );

            The sidebar div would be opened.
            This would first include /includes/easyparliament/templates/sidebars/mp.php.
            Then display "<p>This is your MP</p>\n".
            Then call $this->nextprevlinks().
            The sidebar div would be closed.
            '<a href="blah">Source</a>' is displayed.
            The stripe div is closed.

            But in most cases we only have 0 or 1 hashes in $contents.

        */

        // $extra is html that will go after the sidebar has closed, but within
        // this stripe.
        // eg, the 'Source' bit on Hansard pages.
        global $DATA, $this_page;

        $this->within_stripe_main = false;
        ?>
            </div> <!-- end .main -->
            <div class="sidebar">

        <?php
        $this->within_stripe_sidebar = true;
        $extrahtml = '';

        if (count($contents) == 0) {
            print "\t\t\t&nbsp;\n";
        } else {
            #print '<div class="sidebar">';
            foreach ($contents as $hash) {
                if (isset($hash['type'])) {
                    if ($hash['type'] == 'include') {
                        $this->include_sidebar_template($hash['content']);

                    } elseif ($hash['type'] == 'nextprev') {
                        $this->nextprevlinks();

                    } elseif ($hash['type'] == 'html') {
                        print $hash['content'];

                    } elseif ($hash['type'] == 'extrahtml') {
                        $extrahtml .= $hash['content'];
                    }
                }

            }
        }

        $this->within_stripe_sidebar = false;
        ?>
            </div> <!-- end .sidebar -->
            <div class="break"></div>
<?php
        if ($extrahtml != '') {
            ?>
            <div class="extra"><?php echo $extrahtml; ?></div>
<?php
            }
            ?>
        </div> <!-- end .stripe-* -->

<?php
    }



    public function include_sidebar_template($sidebarname) {
        global $this_page, $DATA;

            $sidebarpath = INCLUDESPATH.'easyparliament/sidebars/'.$sidebarname.'.php';

            if (file_exists($sidebarpath)) {
                include $sidebarpath;
            }
    }


    public function block_start($data=array()) {
        // Starts a 'block' div, used mostly on the home page,
        // on the MP page, and in the sidebars.
        // $data is a hash like this:
        //  'id'    => 'help',
        //  'title' => 'What are debates?'
        //  'url'   => '/help/#debates'     [if present, will be wrapped round 'title']
        //  'body'  => false    [If not present, assumed true. If false, no 'blockbody' div]
        // Both items are optional (although it'll look odd without a title).

        $this->blockbody_open = false;

        if (isset($data['id']) && $data['id'] != '') {
            $id = ' id="' . $data['id'] . '"';
        } else {
            $id = '';
        }

        $title = isset($data['title']) ? $data['title'] : '';

        if (isset($data['url'])) {
            $title = '<a href="' . $data['url'] . '">' . $title . '</a>';
        }
        ?>
                <div class="block"<?php echo $id; ?>>
                    <h4><?php echo $title; ?></h4>
<?php
        if (!isset($data['body']) || $data['body'] == true) {
            ?>
                    <div class="blockbody">
<?php
            $this->blockbody_open = true;
            }
    }

    public function block_end() {
        if ($this->blockbody_open) {
            ?>
                    </div>
<?php
            }
            ?>
                </div> <!-- end .block -->

<?php
    }

    public function heading() {
        global $this_page, $DATA;

        // As well as a page's title, we may display that of its parent.
        // A page's parent can have a 'title' and a 'heading'.
        // The 'title' is always used to create the <title></title>.
        // If we have a 'heading' however, we'll use that here, on the page, instead.

        $parent_page = $DATA->page_metadata($this_page, 'parent');

        if ($parent_page != '') {
            // Not a top-level page, so it has a section heading.
            // This is the page title of the parent.
            $section_text = $DATA->page_metadata($parent_page, 'title');

        } else {
            // Top level page - no parent, hence no parental title.
            $section_text = '';
        }


        // A page can have a 'title' and a 'heading'.
        // The 'title' is always used to create the <title></title>.
        // If we have a 'heading' however, we'll use that here, on the page, instead.

        $page_text = $DATA->page_metadata($this_page, "heading");

        if ($page_text == '' && !is_bool($page_text)) {
            // If the metadata 'heading' is set, but empty, we display nothing.
        } elseif ($page_text == false) {
            // But if it just hasn't been set, we use the 'title'.
            $page_text = $DATA->page_metadata($this_page, "title");
        }

        if ($page_text == $section_text) {
            // We don't want to print both.
            $section_text = '';
        } elseif (!$page_text && $section_text) {
            // Bodge for if we have a section_text but no page_text.
            $page_text = $section_text;
            $section_text = '';
        }

        # XXX Yucky
        if ($this_page != 'home' && $this_page != 'contact') {
            if ($section_text && $parent_page != 'help_us_out' && $parent_page != 'home' && $this_page != 'campaign') {
                print "\t\t\t\t<h1>$section_text";
                if ($page_text) {
                    print "\n\t\t\t\t<br><span>$page_text</span>\n";
                }
                print "</h1>\n";
            } elseif ($page_text) {
                print "\t\t\t\t<h1>$page_text</h1>\n";
            }
        }

        // So we don't print the heading twice by accident from $this->stripe_start().
        $this->heading_displayed = true;
    }

    public function postcode_form() {
        // Used on the mp (and yourmp) pages.
        // And the userchangepc page.
        global $THEUSER;

        echo '<br>';
        $this->block_start(array('id'=>'mp', 'title'=>'Find out about your MP/MSPs/MLAs'));
        echo '<form action="/postcode/" method="get">';
        if ($THEUSER->postcode_is_set()) {
            $FORGETURL = new \MySociety\TheyWorkForYou\Url('userchangepc');
            $FORGETURL->insert(array('forget'=>'t'));
            ?>
                        <p>Your current postcode: <strong><?php echo $THEUSER->postcode(); ?></strong> &nbsp; <small>(<a href="<?php echo $FORGETURL->generate(); ?>" title="The cookie storing your postcode will be erased">Forget this postcode</a>)</small></p>
<?php
        }
        ?>
                        <p><strong>Enter your UK postcode: </strong>

                        <input type="text" name="pc" value="<?php echo _htmlentities(get_http_var('pc')); ?>" maxlength="10" size="10"> <input type="submit" value="GO" class="submit"> <small>(e.g. BS3 1QP)</small>
                        </p>
                        </form>
<?php
        $this->block_end();
    }

    public function error_message($message, $fatal = false, $status = 500) {
        // If $fatal is true, we exit the page right here.
        // $message is like the array used in $this->message()
        global $page_errors;

        // if possible send a 500 error so that google or whatever doesn't
        // cache the page. Rely on the fact that an inpage errors will be
        // sent after a page_start and hence the headers have been sent
        if (!headers_sent()) {
            header("HTTP/1.0 $status Internal Server Error");
        }

        if (is_string($message)) {
            // Sometimes we're just sending a single line to this function
            // rather like the bigger array...
            $message = array (
                'text' => $message
            );
        }

        // if the page has started then we're most likely in an old school page
        // so we should just print out the error, otherwise stick it in the error
        // global which will then be displayed by the header template
        if ( $this->page_started() ) {
            $this->message($message, 'error');
        } else {
            if ( !isset($page_errors) ) {
                $page_errors = array();
            }
            $page_errors[]  = $message;
        }

        if ($fatal) {
            if (!$this->page_started()) {
                $this->page_start();
            }

            if ($this->within_stripe()) {
                $this->stripe_end();
            }
            $this->page_end();
        }

    }


    public function message($message, $class='') {
        // Generates a very simple but common page content.
        // Used for when a user logs out, or votes, or any simple thing
        // where there's a little message and probably a link elsewhere.
        // $message is an array like:
        //      'title' => 'You are now logged out'.
        //      'text'  => 'Some more text here',
        //      'linkurl' => 'http://www.easyparliament.org/debates/',
        //      'linktext' => 'Back to previous page'
        // All fields optional.
        // 'linkurl' should already have htmlentities done on it.
        // $class is a class name that will be applied to the message's HTML elements.

        if ($class != '') {
            $class = ' class="' . $class . '"';
        }

        $need_to_close_stripe = false;

        if (!$this->within_stripe()) {
            $this->stripe_start();
            $need_to_close_stripe = true;
        }

        if (isset($message['title'])) {
            ?>
            <h3<?php echo $class; ?>><?php echo $message['title']; ?></h3>
<?php
        }

        if (isset($message['text'])) {
            ?>
            <p<?php echo $class; ?>><?php echo $message['text']; ?></p>
<?php
        }

        if (isset($message['linkurl']) && isset($message['linktext'])) {
            ?>
            <p><a href="<?php echo $message['linkurl']; ?>"><?php echo $message['linktext']; ?></a></p>
<?php
        }

        if ($need_to_close_stripe) {
            $this->stripe_end();
        }
    }

    public function informational($text) {
        print '<div class="informational left">' . $text . '</div>';
    }

    public function set_hansard_headings($info) {
        // Called from HANSARDLIST->display().
        // $info is the $data['info'] array passed to the template.
        // If the page's HTML hasn't already been started, it sets the page
        // headings that will be needed later in the page.

        global $DATA, $this_page;

        if ($this->page_started()) return;
        // The page's HTML hasn't been started yet, so we'd better do it.

        // Set the page title (in the <title></title>).
        $page_title = '';

        if (isset($info['text_heading'])) {
            $page_title = $info['text_heading'];
        } elseif (isset($info['text'])) {
            // Use a truncated version of the page's main item's body text.
            // trim_words() is in utility.php. Trim to 40 chars.
            $page_title = trim_characters($info['text'], 0, 40);
        }

        if ($page_title != '') {
            // If page title has been set by now, it is good enough to display
            // in the open graph title tag, without the extra date info etc.
            $DATA->set_page_metadata($this_page, 'og_title', $page_title);
        }

        if (isset($info['date'])) {
            // debatesday and wransday pages.
            if ($page_title != '') {
                $page_title .= ': ';
            }
            $page_title .= format_date ($info['date'], SHORTDATEFORMAT);
        }

        if ($page_title != '') {
            $DATA->set_page_metadata($this_page, 'title', $page_title);
        }

        if (isset($info['date'])) {
            // Set the page heading (displayed on the page).
            $page_heading = format_date($info['date'], LONGERDATEFORMAT);
            $DATA->set_page_metadata($this_page, 'heading', $page_heading);
        }

    }

    public function nextprevlinks() {

        // Generally called from $this->stripe_end();

        global $DATA, $this_page;

        // We'll put the html in these and print them out at the end of the function...
        $prevlink = '';
        $uplink = '';
        $nextlink = '';

        // This data is put in the metadata in hansardlist.php
        $nextprev = $DATA->page_metadata($this_page, 'nextprev');
        // $nextprev will have three arrays: 'prev', 'up' and 'next'.
        // Each should have a 'body', 'title' and 'url' element.


        // PREVIOUS ////////////////////////////////////////////////

        if (isset($nextprev['prev'])) {

            $prev = $nextprev['prev'];

            if (isset($prev['url'])) {
                $prevlink = '<a href="' . $prev['url'] . '" title="' . $prev['title'] . '" class="linkbutton">&laquo; ' . $prev['body'] . '</a>';

            } else {
                $prevlink = '&laquo; ' . $prev['body'];
            }
        }

        if ($prevlink != '') {
            $prevlink = '<span class="prev">' . $prevlink . '</span>';
        }


        // UP ////////////////////////////////////////////////

        if (isset($nextprev['up'])) {

            $uplink = '<span class="up"><a href="' .  $nextprev['up']['url'] . '" title="' . $nextprev['up']['title'] . '">' . $nextprev['up']['body'] . '</a>';
            if (get_http_var('s')) {
                $URL = new \MySociety\TheyWorkForYou\Url($this_page);
                $uplink .= '<br><a href="' . $URL->generate() . '">Remove highlighting</a>';
            }
            $uplink .= '</span>';
        }


        // NEXT ////////////////////////////////////////////////

        if (isset($nextprev['next'])) {
            $next = $nextprev['next'];

            if (isset($next['url'])) {
                $nextlink = '<a href="' .  $next['url'] . '" title="' . $next['title'] . '" class="linkbutton">' . $next['body'] . ' &raquo;</a>';
            } else {
                $nextlink = $next['body'] . ' &raquo;';
            }
        }

        if ($nextlink != '') {
            $nextlink = '<span class="next">' . $nextlink . '</span>';
        }


        if ($uplink || $prevlink || $nextlink) {
            echo "<p class='nextprev'>$nextlink $prevlink $uplink</p><br class='clear'>";
        }
    }


    public function search_form($value='') {
        global $SEARCHENGINE;
        // Search box on the search page.
        // If $value is set then it will be displayed in the form.
        // Otherwise the value of 's' in the URL will be displayed.

        $wtt = get_http_var('wtt');

        $URL = new \MySociety\TheyWorkForYou\Url('search');
        $URL->reset(); // no need to pass any query params as a form action. They are not used.

        if ($value == '') {
            if (get_http_var('q') !== '') {
                $value = get_http_var('q');
            } else {
                $value = get_http_var('s');
            }
        }

        $person_name = '';
        if (preg_match_all('#speaker:(\d+)#', $value, $m) == 1) {
            $person_id = $m[1][0];
            $member = new MEMBER(array('person_id' => $person_id));
            if ($member->valid) {
                $value = str_replace("speaker:$person_id", '', $value);
                    $person_name = $member->full_name();
                }
            }

        echo '<div class="mainsearchbox">';
        if ($wtt<2) {
                echo '<form action="', $URL->generate(), '" method="get">';
                if (get_http_var('o')) {
                    echo '<input type="hidden" name="o" value="', _htmlentities(get_http_var('o')), '">';
                }
                if (get_http_var('house')) {
                    echo '<input type="hidden" name="house" value="', _htmlentities(get_http_var('house')), '">';
                }
                echo '<input type="text" name="q" value="', _htmlentities($value), '" size="50"> ';
                echo '<input type="submit" value=" ', ($wtt?'Modify search':'Search'), ' ">';
                $URL = new \MySociety\TheyWorkForYou\Url('search');
            $URL->insert(array('adv' => 1));
                echo '&nbsp;&nbsp; <a href="' . $URL->generate() . '">More&nbsp;options</a>';
                echo '<br>';
                if ($wtt) print '<input type="hidden" name="wtt" value="1">';
        } else { ?>
    <form action="https://www.writetothem.com/lords" method="get">
    <input type="hidden" name="pid" value="<?=_htmlentities(get_http_var('pid')) ?>">
    <input type="submit" style="font-size: 150%" value=" I want to write to this Lord "><br>
<?php
        }

        if (!$wtt && ($value || $person_name)) {
            echo '<div style="margin-top: 5px">';
            $orderUrl = new \MySociety\TheyWorkForYou\Url('search');
            $orderUrl->insert(array('s'=>$value)); # Need the parsed value
                $ordering = get_http_var('o');
                if ($ordering != 'r' && $ordering != 'd' && $ordering != 'p' && $ordering != 'o') {
                    $ordering = 'd';
                }

                if ($ordering=='r') {
                print '<strong>Sorted by relevance</strong>';
                } else {
                printf("<a href='%s'>Sort by relevance</a>", $orderUrl->generate('html', array('o'=>'r')));
                }

                print "&nbsp;|&nbsp;";
                if ($ordering=='d') {
                print '<strong>Sorted by date: newest</strong> / <a href="' . $orderUrl->generate('html', array('o'=>'o')) . '">oldest</a>';
                } elseif ($ordering=='o') {
                print '<strong>Sorted by date:</strong> <a href="' . $orderUrl->generate('html', array('o'=>'d')) . '">newest</a> / <strong>oldest</strong>';
                } else {
                printf("Sort by date: <a href='%s'>newest</a> / <a href='%s'>oldest</a>",
                    $orderUrl->generate('html', array('o'=>'d')), $orderUrl->generate('html', array('o'=>'o')));
                }

            print "&nbsp;|&nbsp;";
            if ($ordering=='p') {
                print '<strong>Use by person</strong>';
            } else {
                printf('<a href="%s">Show use by person</a>', $orderUrl->generate('html', array('o'=>'p')));
            }
            echo '</div>';

            if ($person_name) {
                ?>
                    <p>
                    <input type="radio" name="pid" value="<?php echo _htmlentities($person_id) ?>" checked>Search only <?php echo _htmlentities($person_name) ?>
                    <input type="radio" name="pid" value="">Search all speeches
                    </p>
                <?php
                }
        }

        echo '</form> </div>';
    }

    public function login_form ($errors = array()) {
        // Used for /user/login/ and /user/prompt/
        // $errors is a hash of potential errors from a previous log in attempt.
        ?>
        <form method="post" action="<?php $URL = new \MySociety\TheyWorkForYou\Url('userlogin'); $URL->reset(); echo $URL->generate(); ?>" class="login-form">

<?php
        if (isset($errors["email"])) {
            $this->error_message($errors['email']);
        }
        if (isset($errors["invalidemail"])) {
            $this->error_message($errors['invalidemail']);
        }
?>
            <p>
                <label for="email">Email address:</label></span>
                <input type="text" name="email" id="email" value="<?php echo _htmlentities(get_http_var("email")); ?>" maxlength="100" class="form-control"></span>
            </p>

<?php
        if (isset($errors["password"])) {
            $this->error_message($errors['password']);
        }
        if (isset($errors["invalidpassword"])) {
            $this->error_message($errors['invalidpassword']);
        }
?>
            <p>
                <label for="password">Password:</label>
                <input type="password" name="password" id="password" maxlength="30" class="form-control">
            </p>

            <p>
                <input type="checkbox" name="remember" id="remember" value="true"<?php
        $remember = get_http_var("remember");
        if (get_http_var("submitted") != "true" || $remember == "true") {
            print " checked";
        }
        ?>>
                <label for="remember">Keep me signed in on this device</label>
            </p>

            <p>
                <input type="submit" value="Sign in" class="button">
            </p>

            <input type="hidden" name="submitted" value="true">
<?php
        // I had to havk about with this a bit to cover glossary login.
        // Glossary returl can't be properly formatted until the "add" form
        // has been submitted, so we have to do this rubbish:
        global $glossary_returl;
        if ((get_http_var("ret") != "") || ($glossary_returl != "")) {
            // The return url for after the user has logged in.
            if (get_http_var("ret") != "") {
                $returl = get_http_var("ret");
            }
            else {
                $returl = $glossary_returl;
            }
            ?>
            <input type="hidden" name="ret" value="<?php echo _htmlentities($returl); ?>">
<?php
        }
        ?>

            <p>
                Forgotten your password?
                <a href="<?php
                    $URL = new \MySociety\TheyWorkForYou\Url("userpassword");
                    $URL->insert(array("email"=>get_http_var("email")));
                    echo $URL->generate();
                ?>">Set a new one!</a>
            </p>

            <p>
                Not yet a member?
                <a href="<?php $URL = new \MySociety\TheyWorkForYou\Url("userjoin"); echo $URL->generate(); ?>">Join now!</a>
            </p>

        </form>
<?php
    }

    public function mp_search_form($person_id) {
        // Search box on the MP page.

        $URL = new \MySociety\TheyWorkForYou\Url('search');
        $URL->remove(array('s', 'q'));
        ?>
                <div class="mpsearchbox">
                    <form action="<?php echo $URL->generate(); ?>" method="get">
                    <p>
                    <input name="q" size="12">
                    <input type="hidden" name="pid" value="<?=$person_id ?>">
                    <input type="submit" class="submit" value="GO"></p>
                    </form>
                </div>
<?php
    }

    public function glossary_atoz(&$GLOSSARY) {
    // Print out a nice list of lettered links to glossary pages

        $letters = array ();

        foreach ($GLOSSARY->alphabet as $letter => $eps) {
            // if we're writing out the current letter (list or item)
            if ($letter == $GLOSSARY->current_letter) {
                // if we're in item view - show the letter as "on" but make it a link
                if ($GLOSSARY->current_term != '') {
                    $URL = new \MySociety\TheyWorkForYou\Url('glossary');
                    $URL->insert(array('az' => $letter));
                    $letter_link = $URL->generate('url');

                    $letters[] = "<li class=\"on\"><a href=\"" . $letter_link . "\">" . $letter . "</a></li>";
                }
                // otherwise in list view show no link
                else {
                    $letters[] = "<li class=\"on\">" . $letter . "</li>";
                }
            }
            elseif (!empty($GLOSSARY->alphabet[$letter])) {
                $URL = new \MySociety\TheyWorkForYou\Url('glossary');
                $URL->insert(array('az' => $letter));
                $letter_link = $URL->generate('url');

                $letters[] = "<li><a href=\"" . $letter_link . "\">" . $letter . "</a></li>";
            }
            else {
                $letters[] = '<li>' . $letter . '</li>';
            }
        }
        ?>
                    <div class="letters">
                        <ul>
    <?php
        for ($n=0; $n<13; $n++) {
            print $letters[$n];
        }
        ?>
                        </ul>
                        <ul>
    <?php
        for ($n=13; $n<26; $n++) {
            print $letters[$n];
        }
        ?>
                        </ul>
                    </div>
        <?php
    }

    public function glossary_display_term(&$GLOSSARY) {
    // Display a single glossary term
        global $this_page;

        $term = $GLOSSARY->current_term;

        $term['body'] = $GLOSSARY->glossarise($term['body'], 0, 1);

        // add some extra controls for the administrators
        if ($this_page == "admin_glossary") {
            print "<a id=\"gl".$term['glossary_id']."\"></a>";
            print "<h3>" . $term['title'] . "</h3>";
            $URL = new \MySociety\TheyWorkForYou\Url('admin_glossary');
            $URL->insert(array("delete_confirm" => $term['glossary_id']));
            $delete_url = $URL->generate();
            $admin_links = "<br><small><a href=\"".$delete_url."\">delete</a></small>";
        }
        else {
            $admin_links = "";
        }

        if (isset($term['user_id'])) {
            $URL = new \MySociety\TheyWorkForYou\Url('userview');
            $URL->insert(array('u' => $term['user_id']));
            $user_link = $URL->generate('url');

            $user_details = "\t\t\t\t<p><small>contributed by user <a href=\"" . $user_link . "\">" . $term['firstname'] . " " . $term['lastname'] . "</a></small>" . $admin_links . "</p>\n";
        }
        else {
            $user_details = "";
        }

        print "\t\t\t\t<p class=\"glossary-body\">" . $term['body'] . "</p>\n" . $user_details;

        if ($this_page == "glossary_item") {
            // Add a direct search link for current glossary item
            $URL = new \MySociety\TheyWorkForYou\Url('search');
            // remember to quote the term for phrase matching in search
            $URL->insert(array('s' => '"'.$term['title'].'"'));
            $search_url = $URL->generate();
            printf ("\t\t\t\t<p>Search hansard for \"<a href=\"%s\" title=\"View search results for this glossary item\">%s</a>\"</p>", $search_url, $term['title']);
        }
    }

    public function glossary_display_match_list(&$GLOSSARY) {
            if ($GLOSSARY->num_search_matches > 1) {
                $plural = "them";
                $definition = "some definitions";
            } else {
                $plural = "it";
                $definition = "a definition";
            }
            ?>
            <h4>Found <?php echo $GLOSSARY->num_search_matches; ?> matches for <em><?php echo $GLOSSARY->query; ?></em></h4>
            <p>It seems we already have <?php echo $definition; ?> for that. Would you care to see <?php echo $plural; ?>?</p>
            <ul class="glossary"><?php
            foreach ($GLOSSARY->search_matches as $match) {
                $URL = new \MySociety\TheyWorkForYou\Url('glossary');
                $URL->insert(array('gl' => $match['glossary_id']));
                $URL->remove(array('g'));
                $term_link = $URL->generate('url');
                ?><li><a href="<?php echo $term_link ?>"><?php echo $match['title']?></a></li><?php
            }
            ?></ul>
<?php
    }

    public function glossary_link() {
        // link to the glossary with no epobject_id - i.e. show all entries
        $URL = new \MySociety\TheyWorkForYou\Url('glossary');
        $URL->remove(array("g"));
        $glossary_link = $URL->generate('url');
        print "<small><a href=\"" . $glossary_link . "\">Browse the glossary</a></small>";
    }

    public function glossary_links() {
        print "<div>";
        $this->glossary_link();
        print "</div>";
    }

    public function page_links($pagedata) {
        // The next/prev and page links for the search page.
        global $this_page;

        // $pagedata has...
        $total_results      = $pagedata['total_results'];
        $results_per_page   = $pagedata['results_per_page'];
        $page               = $pagedata['page'];

        if ($total_results > $results_per_page) {

            $numpages = ceil($total_results / $results_per_page);

            $pagelinks = array();

            // How many links are we going to display on the page - don't want to
            // display all of them if we have 100s...
            if ($page < 10) {
                $firstpage = 1;
                $lastpage = 10;
            } else {
                $firstpage = $page - 10;
                $lastpage = $page + 9;
            }

            if ($firstpage < 1) {
                $firstpage = 1;
            }
            if ($lastpage > $numpages) {
                $lastpage = $numpages;
            }

            // Generate all the page links.
            $URL = new \MySociety\TheyWorkForYou\Url($this_page);
            $URL->insert( array('wtt' => get_http_var('wtt')) );
            if (isset($pagedata['s'])) {
                # XXX: Should be taken out in *one* place, not here + search_form etc.
                $value = $pagedata['s'];
                if (preg_match_all('#speaker:(\d+)#', $value, $m) == 1) {
                    $person_id = $m[1][0];
                    $value = str_replace('speaker:' . $person_id, '', $value);
                    $URL->insert(array('pid' => $person_id));
                    }
                $URL->insert(array('s' => $value));
            }

            for ($n = $firstpage; $n <= $lastpage; $n++) {

                if ($n > 1) {
                    $URL->insert(array('p'=>$n));
                } else {
                    // No page number for the first page.
                    $URL->remove(array('p'));
                }
                if (isset($pagedata['pid'])) {
                    $URL->insert(array('pid'=>$pagedata['pid']));
                }

                if ($n != $page) {
                    $pagelinks[] = '<a href="' . $URL->generate() . '">' . $n . '</a>';
                } else {
                    $pagelinks[] = "<strong>$n</strong>";
                }
            }

            // Display everything.

            ?>
                <div class="pagelinks">
                    Result page:
<?php

            if ($page != 1) {
                $prevpage = $page - 1;
                $URL->insert(array('p'=>$prevpage));
                ?>
                    <big><strong><a href="<?php echo $URL->generate(); ?>"><big>&laquo;</big> Previous</a></strong></big>
<?php
            }

            echo "\t\t\t\t" . implode(' ', $pagelinks);

            if ($page != $numpages) {
                $nextpage = $page + 1;
                $URL->insert(array('p'=>$nextpage));
                ?>

                    <big><strong><a href="<?php echo $URL->generate(); ?>">Next <big>&raquo;</big></a></strong></big> <?php
            }

            ?>

                </div>
<?php

        }

    }

    public function display_commentreport($data) {
        // $data has key value pairs.
        // Called from $COMMENT->display_report().

        if ($data['user_id'] > 0) {
            $USERURL = new \MySociety\TheyWorkForYou\Url('userview');
            $USERURL->insert(array('id'=>$data['user_id']));
            $username = '<a href="' . $USERURL->generate() . '">' . _htmlentities($data['user_name']) . '</a>';
        } else {
            $username = _htmlentities($data['user_name']);
        }
        ?>
                <div class="comment">
                    <p class="credit"><strong>Annotation report</strong><br>
                    <small>Reported by <?php echo $username; ?> on <?php echo $data['reported']; ?></small></p>

                    <p><?php echo _htmlentities($data['body']); ?></p>
                </div>
<?php
        if ($data['resolved'] != 'NULL') {
            ?>
                <p>&nbsp;<br><em>This report has not been resolved.</em></p>
<?php
        } else {
            ?>
                <p><em>This report was resolved on <?php echo $data['resolved']; ?></em></p>
<?php
            // We could link to the person who resolved it with $data['resolvedby'],
            // a user_id. But we don't have their name at the moment.
        }

    }


    public function display_commentreportlist($data) {
        // For the admin section.
        // Gets an array of data from COMMENTLIST->render().
        // Passes it on to $this->display_table().

        if (count($data) > 0) {

            ?>
            <h3>Reported annotations</h3>
<?php
            // Put the data in an array which we then display using $PAGE->display_table().
            $tabledata['header'] = array(
                'Reported by',
                'Begins...',
                'Reported on',
                ''
            );

            $tabledata['rows'] = array();

            $EDITURL = new \MySociety\TheyWorkForYou\Url('admin_commentreport');

            foreach ($data as $n => $report) {

                if (!$report['locked']) {
                    // Yes, we could probably cope if we just passed the report_id
                    // through, but this isn't a public-facing page and life's
                    // easier if we have the comment_id too.
                    $EDITURL->insert(array(
                        'rid' => $report['report_id'],
                        'cid' => $report['comment_id'],
                    ));
                    $editlink = '<a href="' . $EDITURL->generate() . '">View</a>';
                } else {
                    $editlink = 'Locked';
                }

                $body = trim_characters($report['body'], 0, 40);

                $tabledata['rows'][] = array (
                    _htmlentities($report['firstname'] . ' ' . $report['lastname']),
                    _htmlentities($body),
                    $report['reported'],
                    $editlink
                );

            }

            $this->display_table($tabledata);

        } else {

            print "<p>There are no outstanding annotation reports.</p>\n";
        }

    }

    public function display_table($data) {
        /* Pass it data to be displayed in a <table> and it renders it
            with stripes.

        $data is like (for example):
        array (
            'header' => array (
                'ID',
                'name'
            ),
            'rows' => array (
                array (
                    '37',
                    'Guy Fawkes'
                ),
                etc...
            )
        )
        */

        ?>
    <table border="1" cellpadding="3" cellspacing="0" width="90%">
<?php
        if (isset($data['header']) && count($data['header'])) {
            ?>
    <thead>
    <tr><?php
            foreach ($data['header'] as $text) {
                ?><th><?php echo $text; ?></th><?php
            }
            ?></tr>
    </thead>
<?php
        }

        if (isset($data['rows']) && count($data['rows'])) {
            ?>
    <tbody>
<?php
            foreach ($data['rows'] as $row) {
                ?>
    <tr><?php
                foreach ($row as $text) {
                    ?><td><?php echo $text; ?></td><?php
                }
                ?></tr>
<?php
            }
            ?>
    </tbody>
<?php
        }
    ?>
    </table>
<?php

    }



    public function admin_menu() {
        // Returns HTML suitable for putting in the sidebar on Admin pages.
        global $this_page, $DATA;

        $pages = array ('admin_home',
                'admin_comments', 'admin_searchlogs', 'admin_popularsearches', 'admin_failedsearches',
                'alert_stats', 'admin_statistics', 'admin_reportstats',
                'admin_commentreports', 'admin_glossary', 'admin_glossary_pending', 'admin_badusers',
                'admin_profile_message', 'admin_photos', 'admin_mpurls', 'admin_policies', 'admin_banner', 'admin_featured', 'admin_topics',
                'admin_wikipedia',
                );

        $links = array();

        foreach ($pages as $page) {
            $title = $DATA->page_metadata($page, 'title');

            if ($page != $this_page) {
                $URL = new \MySociety\TheyWorkForYou\Url($page);
                $title = '<a href="' . $URL->generate() . '">' . $title . '</a>';
            } else {
                $title = '<strong>' . $title . '</strong>';
            }

            $links[] = $title;
        }

        $html = "<ul>\n";

        $html .= "<li>" . implode("</li>\n<li>", $links) . "</li>\n";

        $html .= "</ul>\n";

        return $html;
    }
}

$PAGE = new PAGE;
