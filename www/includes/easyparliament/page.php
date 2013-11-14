<?php

include_once INCLUDESPATH . '../../commonlib/phplib/gaze.php';
include_once INCLUDESPATH . 'easyparliament/member.php';

class PAGE {

    // So we can tell from other places whether we need to output the page_start or not.
    // Use the page_started() function to do this.
    var $page_start_done = false;
    var $supress_heading = false;
    var $heading_displayed = false;

    // We want to know where we are with the stripes, the main structural elements
    // of most pages, so that if we output an error message we can wrap it in HTML
    // that won't break the rest of the page.
    // Changed in $this->stripe_start().
    var $within_stripe_main = false;
    var $within_stripe_sidebar = false;

    function page_start () {

      ob_start();
      set_time_limit(0);
        global $DATA, $this_page, $THEUSER;

        if (!$this->page_started()) {
            // Just in case something's already started this page...
            $parent = $DATA->page_metadata($this_page, "parent");
            if ($parent == 'admin' && (!$THEUSER->isloggedin() || !$THEUSER->is_able_to('viewadminsection'))) {
                // If the user tries to access the admin section when they're not
                // allowed, then show them nothing.

                if (!$THEUSER->isloggedin()) {
                    $THISPAGE = new URL($this_page);

                    $LOGINURL = new URL('userlogin');
                    $LOGINURL->insert(array('ret' => $THISPAGE->generate('none') ));

                    $text = "<a href=\"" . $LOGINURL->generate() . "\">You'd better sign in!</a>";
                } else {
                    $text = "That's all folks!";
                }

                $this_page = 'home';

                $this->page_header();
                $this->page_body();
                $this->content_start();
                $this->stripe_start();

                print "<p>$text</p>\n";

                $this->stripe_end();
                $this->page_end();
                exit;
            }

            $this->page_header();
            $this->page_body();
            $this->content_start();

            $this->page_start_done = true;

        }
    }


    function page_end ($extra = null) {
        $this->content_end();
        $this->page_footer($extra);
    }


    function page_started () {
        return $this->page_start_done == true ? true : false;
    }

    function heading_displayed () {
        return $this->heading_displayed == true ? true : false;
    }

    function within_stripe () {
        if ($this->within_stripe_main == true || $this->within_stripe_sidebar == true) {
            return true;
        } else {
            return false;
        }
    }

    function within_stripe_sidebar () {
        if ($this->within_stripe_sidebar == true) {
            return true;
        } else {
            return false;
        }
    }

    function version($file) {
        global $memcache;
        if (!$memcache) {
            $memcache = new Memcache;
            $memcache->connect('localhost', 11211);
        }
        $memcache_key = OPTION_TWFY_DB_NAME . ':stat_cache:' . $file;
        $hash = '';
        if ( !DEVSITE ) {
            $hash = $memcache->get($memcache_key);
            if (!$hash) {
                $path = BASEDIR . '/' . $file;
                $hash = filemtime($path);
                $memcache->set($memcache_key, $hash, MEMCACHE_COMPRESSED, 300);
            }
        }

        return "$file?" . $hash;
    }

    function page_header () {
        global $DATA, $this_page;

        $linkshtml = "";

        $title = '';
        $sitetitle = $DATA->page_metadata($this_page, "sitetitle");
        $keywords_title = '';

        if ($this_page == 'overview') {
            $title = $sitetitle . ': ' . $DATA->page_metadata($this_page, "title");

        } else {

            if ($page_title = $DATA->page_metadata($this_page, "title")) {
                $title = $page_title;
            }
            // We'll put this in the meta keywords tag.
            $keywords_title = $title;

            $parent_page = $DATA->page_metadata($this_page, 'parent');
            if ($parent_title = $DATA->page_metadata($parent_page, 'title')) {
                if ($title) $title .= ': ';
                $title .= $parent_title;
            }

            if ($title == '') {
                $title = $sitetitle;
            } else {
                $title .= ' - ' . $sitetitle;
            }
        }

        if (!$meta_keywords = $DATA->page_metadata($this_page, "meta_keywords")) {
            $meta_keywords = $keywords_title;
            if ($meta_keywords) $meta_keywords .= ', ';
            $meta_keywords .= 'Hansard, Official Report, Parliament, government, House of Commons, House of Lords, MP, Peer, Member of Parliament, MPs, Peers, Lords, Commons, Scottish Parliament, Northern Ireland Assembly, MSP, MLA, MSPs, MLAs';
        }

        $meta_description = '';
        if ($meta_description = $DATA->page_metadata($this_page, "meta_description")) {
            $meta_description = '<meta name="description" content="' . htmlentities($meta_description) . '">';
        }

        if ($this_page != 'overview') {
            $URL = new URL('overview');

            $linkshtml = "\t<link rel=\"start\" title=\"Home\" href=\"" . $URL->generate() . "\">\n";
        }

        // Create the next/prev/up links for navigation.
        // Their data is put in the metadata in hansardlist.php
        $nextprev = $DATA->page_metadata($this_page, "nextprev");

        if ($nextprev) {
            // Four different kinds of back/forth links we might build.
            $links = array ("first", "prev", "up", "next", "last");

            foreach ($links as $n => $type) {
                if (isset($nextprev[$type]) && isset($nextprev[$type]['listurl'])) {

                    if (isset($nextprev[$type]['body'])) {
                        $linktitle = htmlentities( trim_characters($nextprev[$type]['body'], 0, 40) );
                        if (isset($nextprev[$type]['speaker']) &&
                            count($nextprev[$type]['speaker']) > 0) {
                            $linktitle = $nextprev[$type]['speaker']['first_name'] . ' ' . $nextprev[$type]['speaker']['last_name'] . ': ' . $linktitle;
                        }

                    } elseif (isset($nextprev[$type]['hdate'])) {
                        $linktitle = format_date($nextprev[$type]['hdate'], SHORTDATEFORMAT);
                    }

                    $linkshtml .= "\t<link rel=\"$type\" title=\"$linktitle\" href=\"" . $nextprev[$type]['listurl'] . "\">\n";
                }
            }
        }

        if (!$keywords = $DATA->page_metadata($this_page, "keywords")) {
            $keywords = "";
        } else {
            $keywords = ",".$DATA->page_metadata($this_page, "keywords");
        }

        $robots = '';
        if (DEVSITE) {
            $robots = '<meta name="robots" content="noindex,nofollow">';
        } elseif ($robots = $DATA->page_metadata($this_page, 'robots')) {
            $robots = '<meta name="robots" content="' . $robots . '">';
        }

        if (!headers_sent()) {
            header('Content-Type: text/html; charset=iso-8859-1');
            if ($this_page == 'overview') {
                header('Vary: Cookie, X-GeoIP-Country');
                header('Cache-Control: max-age=600');
            }
        }

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <title><?php echo preg_replace('#<[^>]*>#', '', $title); ?></title>
    <?=$meta_description ?>
    <meta name="keywords" content="<?php echo htmlentities($meta_keywords); ?>">
    <?=$robots ?>
    <link rel="author" title="Send feedback" href="mailto:<?php echo str_replace('@', '&#64;', CONTACTEMAIL); ?>">
    <link rel="home" title="Home" href="http://<?php echo DOMAIN; ?>/">

    <meta property="og:title" content="TheyWorkForYou">
    <meta property="og:type" content="website">
    <meta property="og:url" content="http://<?php echo DOMAIN; ?>">
    <meta property="og:image" content="http://<?php echo DOMAIN; ?>/images/favicon-256.png">
    <meta property="og:description" content="TheyWorkForYou is a website which makes it easy to keep track of your local MP's activities.">
    <meta property="fb:app_id" content="227648394066332">

    <script type="text/javascript" src="<?php echo $this->version('/js/jquery.js') ?>"></script>
    <script type="text/javascript" src="<?php echo $this->version('/js/jquery.cookie.js') ?>"></script>
    <script type="text/javascript" src="/jslib/share/share.js"></script>
    <script type="text/javascript" src="<?php echo $this->version('/js/main.js') ?>"></script>
    <script type="text/javascript" src="<?php echo $this->version('/js/bar.js') ?>"></script>
<?php
        echo $linkshtml;
    # XXX Below line for speed
?>
    <link rel="stylesheet" href="<?php echo WEBPATH; ?><?php echo $this->version('style/global.css' ) ?>" type="text/css">
    <link rel="stylesheet" href="/jslib/share/share.css" type="text/css" media="screen">
    <link rel="stylesheet" href="<?php echo WEBPATH; ?><?php echo $this->version('style/print.css') ?>" type="text/css" media="print">
<?php

        if ($rssurl = $DATA->page_metadata($this_page, 'rss')) {
            // If this page has an RSS feed set.
            echo '<link rel="alternate" type="application/rss+xml" title="TheyWorkForYou RSS" href="http://', DOMAIN, WEBPATH, $rssurl, '">';
        }

        ?>

        <link rel="apple-touch-icon" href="/images/apple-touch-60.png" />
        <link rel="apple-touch-icon" sizes="76x76" href="/images/apple-touch-76.png" />
        <link rel="apple-touch-icon" sizes="120x120" href="/images/apple-touch-120.png" />
        <link rel="apple-touch-icon" sizes="152x152" href="/images/apple-touch-152.png" />

        <?php

        if (!DEVSITE) {
?>



<script type="text/javascript">
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-660910-1']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

 function recordWTT(link, label) {
    _gat._getTrackerByName()._trackEvent('Links', 'WriteToThem', label);
    setTimeout('document.location = "' + link.href + '"', 100);
  }
</script>

<?      } ?>

</head>

<?php
    }

    function page_body () {
        global $this_page;

        // Start the body, put in the page headings.
        ?>
<body>

<div id="fb-root"></div>
<script>
window.fbAsyncInit = function() {
    FB.init({
    appId      : '227648394066332',
    xfbml      : true
    });

    FB.Event.subscribe('edge.create', function(targetUrl) {
        _gaq.push(['_trackSocial', 'facebook', 'like', targetUrl]);
    });

    FB.Event.subscribe('edge.remove', function(targetUrl) {
        _gaq.push(['_trackSocial', 'facebook', 'unlike', targetUrl]);
    });

};

(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/en_GB/all.js";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));
</script>

<div id="container">
<?php
        twfy_debug ("PAGE", "This page: $this_page");

        print "\t<a name=\"top\"></a>\n\n";
        if (defined('OPTION_GAZE_URL') && OPTION_GAZE_URL) {
            $country = gaze_get_country_from_ip($_SERVER["REMOTE_ADDR"]);
            if (get_http_var('country')) $country = strtoupper(get_http_var('country'));
            if ($country == 'NZ') {
                print '<p class="informational banner">You&rsquo;re in New Zealand, so check out <a href="http://www.theyworkforyou.co.nz">TheyWorkForYou.co.nz</a></p>';
            } elseif ($country == 'AU') {
                print '<p class="informational banner">You&rsquo;re in Australia, so check out <a href="http://www.openaustralia.org">OpenAustralia</a>, a TheyWorkForYou for down under</p>';
            } elseif ($country == 'IE') {
                print '<p class="informational banner">Check out <a href="http://www.kildarestreet.com/">KildareStreet</a>, a TheyWorkForYou for the Houses of the Oireachtas</p>';
            } elseif ($country == 'CA') {
                print '<p class="informational banner">Check out <a href="http://howdtheyvote.ca/">How&rsquo;d They Vote?</a> and <a href="http://www.openparliament.ca/">OpenParliament.ca</a></p>';
            } elseif ($this_page != 'overview') {
                #print '<p class="informational banner"><a href="http://election.theyworkforyou.com/">Find out what your candidates said on local and national issues in our quiz</a></p>';
            }
        }

# # 2009-01 interstitial
# include INCLUDESPATH . '../docs/foiorder2009/fns.php';
# echo '<div id="everypage" style="display:none">
# <p style="float:right"><a href="#top" onclick="$.cookie(\'seen_foi2\', 1, { expires: 7, path: \'/\' }); $(\'#everypage\').hide(\'slow\'); return false;">Close</a></p>
# <h2>Blimey. It looks like the Internets won &ndash; <small>a message from TheyWorkForYou</small></h2>
# <p>Sorry to interrupt, but we thought you&rsquo;d like to know that <strong>you won</strong>!';
# echo $foi2009_message;
# echo '<p align="right"><a href="#top" onclick="$.cookie(\'seen_foi2\', 1, { expires: 7, path: \'/\' }); $(\'#everypage\').hide(\'slow\'); return false;">Close</a></p>
# </div>';

        $this->mysociety_bar();
        $this->title_bar();
        $this->menu();
    }

    //render the little mysociety crossell
    function mysociety_bar () {
        global $this_page;
        ?>
            <div id="mysociety_bar">
            <?php if (1==0 && $this_page != 'overview') { ?>
                <div id="headercampaign"><p><a href="http://www.pledgebank.com/twfypatrons">Become a They Work For You Patron ...</p></a></div>
            <?php } ?>
                <ul>
                    <li id="logo">
                        <a href="http://www.mysociety.org/"><img src="/images/mysociety_small.png" alt="mySociety" width="72" height="16"></a>
                    </li>
                    <li>
                        <a href="http://www.mysociety.org/donate/?cs=1" title="Like this website? Donate to help keep it running.">Donate</a>
                    </li>
                    <li id="moresites">
                        <a id="moresiteslink" href="http://www.mysociety.org/projects/?cs=1" title="Donate to UK Citizens Online Democracy, mySociety's parent charity.">More</a>
                    </li>
                    <li >
                        <noscript>

                            <a href="http://www.mysociety.org/projects/?cs=1" title="View all mySociety's projects">More mySociety projects...</a>&nbsp;&nbsp;
                            <a href="https://secure.mysociety.org/admin/lists/mailman/listinfo/news?cs=1" title="mySociety newsletter - about once a month">mySociety newsletter</a>
                        </noscript>
                    </li>
                </ul>
            </div>

        <?php
    }

    function title_bar () {
        // The title bit of the page, with possible search box.
        global $this_page, $DATA;

        $img = '<img src="' . IMAGEPATH . 'logo.png" width="423" height="80" alt="TheyWorkForYou - Hansard and Official Reports for the UK Parliament, Scottish Parliament, and Northern Ireland Assembly">';

        if ($this_page != 'overview') {
            $HOMEURL = new URL('overview');
            $HOMEURL = $HOMEURL->generate();
            $HOMETITLE = 'To the front page of the site';
            $heading = '<a href="' . $HOMEURL . '" title="' . $HOMETITLE . '">' . $img . '</a>';
        } else {
            $heading = '<h1>' . $img . '</h1>';
        }
?>
    <div id="banner">
        <div id="title">
            <?=$heading?>
        </div>
<?php
    #       if ($this_page != 'home' && $this_page != 'search' && $this_page != 'yourmp') {
            $URL = new URL('search');
            $URL->reset();
            ?>
        <div id="search">
            <form action="<?php echo $URL->generate(); ?>" method="get">
               <label for="searchbox">Search</label><input id="searchbox" name="s" size="15">
               <input type="submit" class="submit" value="Go">
               <? /* <input type="hidden" name="section" value="<?=$section?>"> */ ?>
            </form>
            <ul>
                <li>
                    e.g. a <em>word</em>, <em>phrase</em>, <em>person</em>, or <em>postcode</em>
                </li>
                <li>
                    |
                </li>
                <li>
                    <a href="/search/">More options</a>
                </li>
            </ul>
        </div>
<?php
    #       }
        ?>
    </div> <!-- end #banner -->
<?php
    }

    // Works out which things to highlight, and which 'country' section we're in.
    // Returns array of 'top' highlight, 'bottom' highlight, and which country section to show
    function menu_highlights() {
        global $this_page, $DATA;

        // We work out which of the items in the top and bottom menus
        // are highlighted - $top_highlight and $bottom_highlight respectively.
        $parent = $DATA->page_metadata($this_page, 'parent');

        if (!$parent) {

            $top_highlight = $this_page;
            $bottom_highlight = '';

            $selected_top_link = $DATA->page_metadata('hansard', 'menu');
            $url = new URL('hansard');
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
            $url = new URL($top_highlight);
            $selected_top_link['link'] = $url->generate();

        }

        if ($top_highlight == 'hansard') {
            $section = 'uk';
        } elseif ($top_highlight == 'ni_home') {
            $section = 'ni';
        } elseif ($top_highlight == 'sp_home') {
            $section = 'scotland';
        } else {
            $section = '';
        }

        return array(
            'top' => $top_highlight,
            'bottom' => $bottom_highlight,
            'top_selected' => $selected_top_link,
            'section' => $section,
        );
    }

    function menu () {
        global $this_page, $DATA, $THEUSER;

        // Page names mapping to those in metadata.php.
        // Links in the top menu, and the sublinks we see if
        // we're within that section.
        $items = array (
            array('home'),
            array('hansard', 'overview', 'mps', 'peers', 'alldebatesfront', 'wranswmsfront', 'pbc_front', 'calendar_summary'),
            array('sp_home', 'spoverview', 'msps', 'spdebatesfront', 'spwransfront'),
            array('ni_home', 'nioverview', 'mlas'),
            array('wales_home'),
        );

        $highlights = $this->menu_highlights();

        //get the top and bottom links
        $top_links = array();
        $bottom_links = array();
        foreach ($items as $bottompages) {
            $toppage = array_shift($bottompages);

            // Generate the links for the top menu.

            // What gets displayed for this page.
            $menudata = $DATA->page_metadata($toppage, 'menu');
                $text = $menudata['text'];
                $title = $menudata['title'];
            if (!$title) continue;

                //get link and description for the menu ans add it to the array
            $class = $toppage == $highlights['top'] ? ' class="on"' : '';
                $URL = new URL($toppage);
                $top_link = array("link" => '<a href="' . $URL->generate() . '" title="' . $title . '"' . $class . '>' . $text . '</a>',
                    "title" => $title);
                array_push($top_links, $top_link);

            if ($toppage == $highlights['top']) {

                // This top menu link is highlighted, so generate its bottom menu.
                foreach ($bottompages as $bottompage) {
                    $menudata = $DATA->page_metadata($bottompage, 'menu');
                    $text = $menudata['text'];
                    $title = $menudata['title'];
                    // Where we're linking to.
                    $URL = new URL($bottompage);
                    $class = $bottompage == $highlights['bottom'] ? ' class="on"' : '';
                    $bottom_links[] = '<a href="' . $URL->generate() . '" title="' . $title . '"' . $class . '>' . $text . '</a>';
                }
            }
        }
        ?>
    <div id="menu">
        <div id="topmenu">
            <div id="topmenuselected"><a href="<?=$highlights['top_selected']['link']?>"><?=$highlights['top_selected']['text'] ?></a> <a id="topmenu-change" href="/parliaments/" onclick="toggleVisible('site');return false;"><small>(change)</small></a></div>
<?php
            $this->user_bar($highlights['top']);
            ?>
                <dl id="site">
                    <?php foreach ($top_links as $top_link) {?>
                        <dt><?php print $top_link['link']; ?></dt>
                        <dd><?php print $top_link['title']; ?></dd>
                    <?php } ?>
                </dl>

            <br>
        </div>
        <div id="bottommenu">
            <ul>
            <li><?php print implode("</li>\n\t\t\t<li>", $bottom_links); ?></li>
            </ul>
        </div>
    </div> <!-- end #menu -->

<?php
    }


    function user_bar ($top_highlight='') {
        // Called from menu(), but separated out here for clarity.
        // Does just the bit of the menu related to login/join/etc.
        global $this_page, $DATA, $THEUSER;

        // We may want to send the user back to this current page after they've
        // joined, logged out or logged in. So we put the URL in $returl.
        $URL = new URL($this_page);
        $returl = $URL->generate('none');

        //user logged in
        if ($THEUSER->isloggedin()) {

            // The 'Edit details' link.
            $menudata   = $DATA->page_metadata('userviewself', 'menu');
            $edittext   = $menudata['text'];
            $edittitle  = $menudata['title'];
            $EDITURL    = new URL('userviewself');
            if ($this_page == 'userviewself' || $this_page == 'useredit' || $top_highlight == 'userviewself') {
                $editclass = ' class="on"';
            } else {
                $editclass = '';
            }

            // The 'Log out' link.
            $menudata   = $DATA->page_metadata('userlogout', 'menu');
            $logouttext = $menudata['text'];
            $logouttitle= $menudata['title'];

            $LOGOUTURL  = new URL('userlogout');
            if ($this_page != 'userlogout') {
                $LOGOUTURL->insert(array("ret"=>$returl));
                $logoutclass = '';
            } else {
                $logoutclass = ' class="on"';
            }

            $username = $THEUSER->firstname() . ' ' . $THEUSER->lastname();

        ?>

            <ul id="user">
            <li><a href="<?php echo $LOGOUTURL->generate(); ?>" title="<?php echo $logouttitle; ?>"<?php echo $logoutclass; ?>><?php echo $logouttext; ?></a></li>
            <li><a href="<?php echo $EDITURL->generate(); ?>" title="<?php echo $edittitle; ?>"<?php echo $editclass; ?>><?php echo $edittext; ?></a></li>
            <li><a href="<?php echo $EDITURL->generate(); ?>" title="<?php echo $edittitle; ?>"<?php echo $editclass; ?>><?php echo htmlentities($username); ?></a></li>
<?php

        } else {
        // User not logged in

            // The 'Join' link.
            $menudata   = $DATA->page_metadata('userjoin', 'menu');
            $jointext   = $menudata['text'];
            $jointitle  = $menudata['title'];

            $JOINURL    = new URL('userjoin');
            if ($this_page != 'userjoin') {
                if ($this_page != 'userlogout' && $this_page != 'userlogin') {
                    // We don't do this on the logout page, because then the user
                    // will return straight to the logout page and be logged out
                    // immediately!
                    $JOINURL->insert(array("ret"=>$returl));
                }
                $joinclass = '';
            } else {
                $joinclass = ' class="on"';
            }

            // The 'Log in' link.
            $menudata   = $DATA->page_metadata('userlogin', 'menu');
            $logintext  = $menudata['text'];
            $logintitle = $menudata['title'];

            $LOGINURL   = new URL('userlogin');
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
                $loginclass = ' class="on"';
            }

        ?>
            <ul id="user">
            <li><a href="<?php echo $LOGINURL->generate(); ?>" title="<?php echo $logintitle; ?>"<?php echo $loginclass; ?>><?php echo $logintext; ?></a></li>
            <li><a href="<?php echo $JOINURL->generate(); ?>" title="<?php echo $jointitle; ?>"<?php echo $joinclass; ?>><?php echo $jointext; ?></a></li>
<?php
        }

        // If the user's postcode is set, then we add a link to Your MP etc.
        $divider = true;
        if ($THEUSER->postcode_is_set()) {
            $items = array('yourmp');
            if (postcode_is_scottish($THEUSER->postcode()))
                $items[] = 'yourmsp';
            elseif (postcode_is_ni($THEUSER->postcode()))
                $items[] = 'yourmla';
            foreach ($items as $item) {
                $menudata   = $DATA->page_metadata($item, 'menu');
                $logintext  = $menudata['text'];
                $logintitle = $menudata['title'];
                $URL = new URL($item);
                if($divider){
                    echo '<li class="divider"><a href="' . $URL->generate() . '">' . $logintext . '</a></li>';
                }else{
                    echo '<li><a href="' . $URL->generate() . '">' . $logintext . '</a></li>';
                }
                $divider = false;
            }
        }
        echo '</ul>';
    }


    // Where the actual meat of the page begins, after the title and menu.
    function content_start () {
        global $this_page, $THEUSER;
        echo '<div id="content">';

/*
        if ($this_page != 'overview') {
            $bound_pc = '';
            if (get_http_var('pc')) {
                $bound_pc = get_http_var('pc');
            } elseif ($THEUSER->postcode_is_set()) {
                $bound_pc = $THEUSER->postcode();
            }
            print '<p class="informational all"><a href="http://election.theyworkforyou.com/quiz/';
            if ($bound_pc) {
                print urlencode($bound_pc);
            }
            print '">Find out what your candidates said on local and national issues in our quiz</a></p>';
        }
*/

        $highlights = $this->menu_highlights();

    }


    function stripe_start ($type='side', $id='', $extra_class = '') {
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


    function stripe_end ($contents = array(), $extra = '') {
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

        <?
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



    function include_sidebar_template ($sidebarname) {
        global $this_page, $DATA;

            $sidebarpath = INCLUDESPATH.'easyparliament/sidebars/'.$sidebarname.'.php';

            if (file_exists($sidebarpath)) {
                include $sidebarpath;
            }
    }


    function block_start($data=array()) {
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


    function block_end () {
        if ($this->blockbody_open) {
            ?>
                    </div>
<?php
            }
            ?>
                </div> <!-- end .block -->

<?php
    }


    function heading() {
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





    function content_end () {

        print "</div> <!-- end #content -->";

    }

    //get <a> links for a particular set of pages defined in metadata.php
    function get_menu_links ($pages){
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

            //check for external vs internal menu links
            if(!valid_url($url)){
                $URL = new URL($page);
                $url = $URL->generate();
            }

            //make the link
            if ($page == $this_page) {
                $links[] = $title;
            } else {
                $links[] = '<a href="' . $url . '">' . $title . '</a>';
            }
        }

        return $links;
    }

    function page_footer ($extra = null) {
        global $DATA, $this_page;

                global $DATA, $this_page;

                $about_links = $this->get_menu_links(array ('help', 'about', 'linktous', 'houserules', 'blog', 'news', 'contact'));
                $assembly_links = $this->get_menu_links(array ('hansard', 'sp_home', 'ni_home', 'wales_home', 'boundaries'));
                $international_links = $this->get_menu_links(array ('newzealand', 'australia', 'ireland'));
                $tech_links = $this->get_menu_links(array ('code', 'api', 'data', 'devmailinglist', 'irc'));
        $landing_links = $this->get_menu_links(array ('parliament_landing', 'hansard_landing'));

        /*
                $about_links[] = '<a href="' . WEBPATH . 'api/">API</a> / <a href="http://ukparse.kforge.net/parlparse">XML</a>';
                $about_links[] = '<a href="http://github.com/mysociety/theyworkforyou">Source code</a>';

                $user_agent = ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) ? strtolower( $_SERVER['HTTP_USER_AGENT'] ) : '';
                if (stristr($user_agent, 'Firefox/'))
                    $about_links[] = '<a href="http://mycroft.mozdev.org/download.html?name=theyworkforyou">Add search to Firefox</a>';

        */
                ?>

                <div id="footer">
                    <dl>
                        <dt>About: </dt>
                        <dd>
                            <ul>
                                <?php
                                    foreach ($about_links as $about_link) {
                                        echo '<li>' . $about_link . '</li>';
                                    }
                                ?>
                            </ul>
                        </dd>
                        <dt>Parliaments &amp; assemblies: </dt>
                        <dd>
                            <ul>
                                <?php
                                    foreach ($assembly_links as $assembly_link) {
                                        echo '<li>' . $assembly_link . '</li>';
                                    }
                                ?>
                            </ul>
                        </dd>
                        <dt>International projects: </dt>
                        <dd>
                            <ul>
                                <?php
                                    foreach ($international_links as $international_link) {
                                        echo '<li>' . $international_link . '</li>';
                                    }
                                ?>
                            </ul>
                        </dd>
                        <dt>Technical: </dt>
                        <dd>
                            <ul>
                                <?php
                                    foreach ($tech_links as $tech_link) {
                                        echo '<li>' . $tech_link . '</li>';
                                    }
                                ?>
                            </ul>
                        </dd>
                        <dt>Explanatory Pages: </dt>
                        <dd>
                            <ul>
                                <?php
                                    foreach ($landing_links as $landing_link) {
                                        echo '<li>' . $landing_link . '</li>';
                                    }
                                ?>
                            </ul>
                        </dd>
                  </dl>
                  <div class="right">

                      <div class="fb-like" data-href="https://www.facebook.com/TheyWorkForYou" data-colorscheme="light" data-layout="button_count" data-action="like" data-show-faces="false" data-send="false"></div>

                      <br>

                      <a href="https://twitter.com/theyworkforyou" class="twitter-follow-button" data-show-count="false">Follow @theyworkforyou</a>

                    <script>

                        window.twttr = (function (d,s,id) {
                            var t, js, fjs = d.getElementsByTagName(s)[0];
                            if (d.getElementById(id)) return; js=d.createElement(s); js.id=id;
                            js.src="https://platform.twitter.com/widgets.js"; fjs.parentNode.insertBefore(js, fjs);
                            return window.twttr || (t = { _e: [], ready: function(f){ t._e.push(f) } });
                        }(document, "script", "twitter-wjs"));

                        // Used with the Google Analytics Tweet tracking
                        function extractParamFromUri(uri, paramName) {
                            if (!uri) {
                                return;
                            }
                            var uri = uri.split('#')[0];  // Remove anchor.
                            var parts = uri.split('?');  // Check for query params.
                            if (parts.length == 1) {
                                return;
                            }
                            var query = decodeURI(parts[1]);

                            // Find url param.
                            paramName += '=';
                            var params = query.split('&');
                            for (var i = 0, param; param = params[i]; ++i) {
                                if (param.indexOf(paramName) === 0) {
                                    return unescape(param.split('=')[1]);
                                }
                            }
                        }

                        function trackTwitter(intent_event) {
                            if (intent_event) {
                                var opt_pagePath;
                                if (intent_event.target && intent_event.target.nodeName == 'IFRAME') {
                                    opt_target = extractParamFromUri(intent_event.target.src, 'url');
                                }
                                _gaq.push(['_trackSocial', 'twitter', 'follow', opt_pagePath]);
                            }
                        }

                        twttr.ready(function (twttr) {
                            twttr.events.bind('follow', trackTwitter);
                        });

                    </script>

                      <h5>Donate</h5>
                      <p>
                          This website is run by <a href="http://www.mysociety.org/">mySociety</a>, the project of
                          a <a href="http://www.ukcod.org.uk/">registered charity</a>.
                  If you find it useful, please <a href="http://www.mysociety.org/donate/">donate</a> to keep it running.
                      </p>
                      <h5>Sign up to our newsletter</h5>
                      <form method="post" action="https://secure.mysociety.org/admin/lists/mailman/subscribe/news">
                          <input type="text" name="email">
                          <input type="submit" value="Join">
                          <div style="display:none;">Please don't fill in this box: <input type="text" name="username"></div>
                      </form>
                      <p>
                          Approximately once a month, spam free.
                      </p>
                  </div>
        <?php
        // This makes the tracker appear on all sections, but only actually on theyworkforyou.com
                //if ($DATA->page_metadata($this_page, 'track') ) {
        if (DOMAIN == 'www.theyworkforyou.com') {
                    // We want to track this page.
            // Kind of fake URLs needed for the tracker.
            $url = urlencode('http://' . DOMAIN . '/' . $this_page);
            ?>
<script type="text/javascript"><!--
an=navigator.appName;sr='http://x3.extreme-dm.com/';srw="na";srb="na";d=document;r=41;function pr(n) {
d.write("<img alt='' src=\""+sr+"n\/?tag=fawkes&p=<?php echo $url; ?>&j=y&srw="+srw+"&srb="+srb+"&l="+escape(d.referrer)+"&rs="+r+"\" height='1' width='1'>");}
s=screen;srw=s.width;an!="Netscape"?srb=s.colorDepth:srb=s.pixelDepth
pr()//-->
</script><noscript><img alt="" src="http://x3.extreme-dm.com/z/?tag=fawkes&amp;p=<?php echo $url; ?>&amp;j=n" height="1" width="1"></noscript>
<?php
        }

        // DAMN, this really shouldn't be in PAGE.
        $db = new ParlDB;
        $db->display_total_duration();

        $duration = getmicrotime() - STARTTIME;
        twfy_debug ("TIME", "Total time for page: $duration seconds.");
        if (!isset($_SERVER['WINDIR'])) {
            $rusage = getrusage();
            $duration = $rusage['ru_utime.tv_sec']*1000000 + $rusage['ru_utime.tv_usec'] - STARTTIMEU;
            twfy_debug ('TIME', "Total user time: $duration microseconds.");
            $duration = $rusage['ru_stime.tv_sec']*1000000 + $rusage['ru_stime.tv_usec'] - STARTTIMES;
            twfy_debug ('TIME', "Total system time: $duration microseconds.");
        }

?>

</div> <!-- end #footer -->
</div> <!-- end #container -->

<script type="text/javascript" charset="utf-8">
    barSetup();
</script>

</body>
</html>
<?php
        ob_end_flush();
    }

    function postcode_form() {
        // Used on the mp (and yourmp) pages.
        // And the userchangepc page.
        global $THEUSER;

        echo '<br>';
        $this->block_start(array('id'=>'mp', 'title'=>'Find out about your MP/MSPs/MLAs'));
        echo '<form action="/postcode/" method="get">';
        if ($THEUSER->postcode_is_set()) {
            $FORGETURL = new URL('userchangepc');
            $FORGETURL->insert(array('forget'=>'t'));
            ?>
                        <p>Your current postcode: <strong><?php echo $THEUSER->postcode(); ?></strong> &nbsp; <small>(<a href="<?php echo $FORGETURL->generate(); ?>" title="The cookie storing your postcode will be erased">Forget this postcode</a>)</small></p>
<?php
        }
        ?>
                        <p><strong>Enter your UK postcode: </strong>

                        <input type="text" name="pc" value="<?php echo htmlentities(get_http_var('pc')); ?>" maxlength="10" size="10"> <input type="submit" value="GO" class="submit"> <small>(e.g. BS3 1QP)</small>
                        </p>
                        <input type="hidden" name="ch" value="t">
                        </form>
<?php
        $this->block_end();
    }

    function member_rss_block ($urls) {
        // Returns the html for a person's rss feeds sidebar block.
        // Used on MP/Peer page.

        $html = '
                <div class="block">
                <h4>RSS feeds</h4>
                    <div class="blockbody">
                        <ul>
';
        if (isset($urls['appearances'])) {
            $html .= '<li><a href="' . $urls['appearances'] . '"><img src="' . WEBPATH . 'images/rss.gif" alt="RSS feed" border="0" align="middle"></a> <a href="' . $urls['appearances'] . '">Most recent appearances</a></li>';
        }

        $HELPURL = new URL('help');

        $html .= '
                        </ul>
                        <p><a href="' . $HELPURL->generate() . '#rss" title="An explanation of what RSS feeds are for"><small>What is RSS?</small></a></p>
                    </div>
                </div>
';
        return $html;

    }

    function display_member($member, $extra_info) {
        include_once INCLUDESPATH . 'easyparliament/templates/html/person.php';
    }

    function error_message ($message, $fatal = false, $status = 500) {
        // If $fatal is true, we exit the page right here.
        // $message is like the array used in $this->message()

        if (!$this->page_started()) {
            if (!headers_sent()) {
                header("HTTP/1.0 $status Internal Server Error");
            }
            $this->page_start();
        }

        if (is_string($message)) {
            // Sometimes we're just sending a single line to this function
            // rather like the bigger array...
            $message = array (
                'text' => $message
            );
        }

        $this->message($message, 'error');

        if ($fatal) {
            if ($this->within_stripe()) {
                $this->stripe_end();
            }
            $this->page_end();
        }

    }


    function message ($message, $class='') {
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

    function informational($text) {
        print '<div class="informational left">' . $text . '</div>';
    }

    function set_hansard_headings ($info) {
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

    function nextprevlinks () {

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
                $URL = new URL($this_page);
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


    function recess_message() {
        // Returns a message if parliament is currently in recess.
        include_once INCLUDESPATH."easyparliament/recess.php";
        $message = '';
        list($name, $from, $to) = recess_prettify(date('j'), date('n'), date('Y'), 1);
        if ($name) {
            $message = 'The Houses of Parliament are in their ' . $name . ' ';
            if ($from && $to) {
                $from = format_date($from, SHORTDATEFORMAT);
                $to = format_date($to, SHORTDATEFORMAT);
                if (substr($from, -4, 4) == substr($to, -4, 4)) {
                    $from = substr($from, 0, strlen($from) - 4);
                }
                $message .= "from $from until $to.";
            } else {
                $message .= 'at this time.';
            }
        }

        return $message;
    }

    function trackback_rss ($trackbackdata) {
        /*
        Outputs Trackback Auto Discovery RSS for something.

        $trackbackdata = array (
            'itemurl'   => 'http://www.easyparliament.org/debate/?id=2003-02-28.544.2',
            'pingurl'   => 'http://www.easyparliament.org/trackback/?e=2345',
            'title'     => 'This item or page title',
            'date'      => '2003-02-28T13:47:00+00:00'
        );
        */
        ?>
<!--
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:dc="http://purl.org/dc/elements/1.1/"
         xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/">
<rdf:Description
    rdf:about="<?php echo $trackbackdata['itemurl']; ?>"
    trackback:ping="<?php echo $trackbackdata['pingurl']; ?>"
    dc:identifier="<?php echo $trackbackdata['itemurl']; ?>"
    dc:title="<?php echo str_replace('"', "'", $trackbackdata['title']); ?>"
    dc:date="<?php echo $trackbackdata['date']; ?>">
</rdf:RDF>
-->
<?php
    }

    function search_form ($value='') {
        global $SEARCHENGINE;
        // Search box on the search page.
        // If $value is set then it will be displayed in the form.
        // Otherwise the value of 's' in the URL will be displayed.

        $wtt = get_http_var('wtt');

        $URL = new URL('search');
        $URL->reset(); // no need to pass any query params as a form action. They are not used.

        if ($value == '')
            $value = get_http_var('s');

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
                    echo '<input type="hidden" name="o" value="', htmlentities(get_http_var('o')), '">';
                }
                if (get_http_var('house')) {
                    echo '<input type="hidden" name="house" value="', htmlentities(get_http_var('house')), '">';
                }
                echo '<input type="text" name="s" value="', htmlentities($value), '" size="50"> ';
                echo '<input type="submit" value=" ', ($wtt?'Modify search':'Search'), ' ">';
                $URL = new URL('search');
            $URL->insert(array('adv' => 1));
                echo '&nbsp;&nbsp; <a href="' . $URL->generate() . '">More&nbsp;options</a>';
                echo '<br>';
                if ($wtt) print '<input type="hidden" name="wtt" value="1">';
        } else { ?>
    <form action="http://www.writetothem.com/lords" method="get">
    <input type="hidden" name="pid" value="<?=htmlentities(get_http_var('pid')) ?>">
    <input type="submit" style="font-size: 150%" value=" I want to write to this Lord "><br>
<?
        }

        if (!$wtt && ($value || $person_name)) {
            echo '<div style="margin-top: 5px">';
            $orderUrl = new URL('search');
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
                    <input type="radio" name="pid" value="<?php echo htmlentities($person_id) ?>" checked>Search only <?php echo htmlentities($person_name) ?>
                    <input type="radio" name="pid" value="">Search all speeches
                    </p>
                <?
                }
        }

        echo '</form> </div>';
    }

    function advanced_search_form() {
        include_once INCLUDESPATH . 'easyparliament/templates/html/search_advanced.php';
    }

    function login_form ($errors = array()) {
        // Used for /user/login/ and /user/prompt/
        // $errors is a hash of potential errors from a previous log in attempt.
        ?>
                <form method="post" action="<?php $URL = new URL('userlogin'); $URL->reset(); echo $URL->generate(); ?>">


<?php
        if (isset($errors["email"])) {
            $this->error_message($errors['email']);
        }
        if (isset($errors["invalidemail"])) {
            $this->error_message($errors['invalidemail']);
        }
?>
                <div class="row">
                <span class="label"><label for="email">Email address:</label></span>
                <span class="formw"><input type="text" name="email" id="email" value="<?php echo htmlentities(get_http_var("email")); ?>" maxlength="100" size="30" class="form"></span>
                </div>

<?php
        if (isset($errors["password"])) {
            $this->error_message($errors['password']);
        }
        if (isset($errors["invalidpassword"])) {
            $this->error_message($errors['invalidpassword']);
        }
?>
                <div class="row">
                <span class="label"><label for="password">Password:</label></span>
                <span class="formw"><input type="password" name="password" id="password" maxlength="30" size="20" class="form"></span>
                </div>

                <div class="row">
                <span class="label">&nbsp;</span>
                <span class="formw"><input type="checkbox" name="remember" id="remember" value="true"<?php
        $remember = get_http_var("remember");
        if (get_http_var("submitted") != "true" || $remember == "true") {
            print " checked";
        }
        ?>> <label for="remember">Remember login details.*</label></span>
                </div>

                <div class="row">
                <span class="label">&nbsp;</span>
                <span class="formw"><input type="submit" value="Login" class="submit"> <small><a href="<?php
        $URL = new URL("userpassword");
        $URL->insert(array("email"=>get_http_var("email")));
        echo $URL->generate();
?>">Forgotten your password?</a></small></span>
                </div>

                <div class="row">
                <small></small>
                </div>

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
                <input type="hidden" name="ret" value="<?php echo htmlentities($returl); ?>">
<?php
        }
        ?>
                </form>
<?php
    }



    function mp_search_form ($person_id) {
        // Search box on the MP page.

        $URL = new URL('search');
        $URL->remove(array('s'));
        ?>
                <div class="mpsearchbox">
                    <form action="<?php echo $URL->generate(); ?>" method="get">
                    <p>
                    <input name="s" size="12">
                    <input type="hidden" name="pid" value="<?=$person_id ?>">
                    <input type="submit" class="submit" value="GO"></p>
                    </form>
                </div>
<?php
    }


    function glossary_search_form ($args) {
        // Search box on the glossary page.
        global $THEUSER;

        $type = "";

        if (isset($args['blankform']) && $args['blankform'] == 1) {
            $formcontent = "";
        }
        else {
            $formcontent = htmlentities(get_http_var('g'));
        }

        if ($THEUSER->isloggedin()) {
            $URL = new URL($args['action']);
            $URL->remove(array('g'));
        }
        else {
            $URL = new URL('userprompt');
            $URL->remove(array('g'));
            $type = "<input type=\"hidden\" name=\"type\" value=\"2\">";
        }

        $add_link = $URL->generate('url');
        ?>
        <form action="<?php echo $add_link; ?>" method="get">
        <?php echo $type; ?>
        <p>Help make TheyWorkForYou.com better by adding a definition:<br>
        <label for="g"><input type="text" name="g" value="<?php echo $formcontent; ?>" size="45">
        <input type="submit" value="Search" class="submit"></label>
        </p>
        </form>
<?php
    }

    function glossary_add_definition_form ($args) {
        // Add a definition for a new Glossary term.
        global $GLOSSARY;

        $URL = new URL($args['action']);
        $URL->remove(array('g'));

        ?>
    <div class="glossaryaddbox">
        <form action="<?php print $URL->generate(); ?>" method="post">
        <input type="hidden" name="g" value="<?php echo $args['s']; ?>">
        <input type="hidden" name="return_page" value="glossary">
        <label for="definition"><p><textarea name="definition" id="definition" rows="15" cols="55"><?php echo htmlentities($GLOSSARY->current_term['body']); ?></textarea></p>

        <p><input type="submit" name="previewterm" value="Preview" class="submit">
        <input type="submit" name="submitterm" value="Post" class="submit"></p></label>
        <p><small>Only &lt;b&gt; and &lt;i&gt; tags are allowed. URLs and email addresses will automatically be turned into links.</small></p>
    </div>
<?php
    }

    function glossary_add_link_form ($args) {
        // Add an external link to the glossary.
        global $GLOSSARY;

        $URL = new URL('glossary_addlink');
        $URL->remove(array('g'));
        ?>
    <h4>All checks fine and dandy!</h4><p>Just so you know, we found <strong><?php echo $args['count']; ?></strong> occurences of <?php echo $GLOSSARY->query; ?> in Hansard</p>
    <p>Please add your link below:</p>
    <h4>Add an external link for <em><?php echo $args['s']; ?></em></h4>
    <div class="glossaryaddbox">
        <form action="<?php print $URL->generate(); ?>" method="post">
        <input type="hidden" name="g" value="<?php echo $args['s']; ?>">
        <input type="hidden" name="return_page" value="glossary">
        <label for="definition"><input type="text" name="definition" id="definition">
        <p><!-- input type="submit" name="previewterm" value="Preview" class="submit" /-->
        <input type="submit" name="submitterm" value="Post" class="submit"></p></label>
        <p><small>Only &lt;b&gt; and &lt;i&gt; tags are allowed. URLs and email addresses will automatically be turned into links.</small></p>
    </div>
<?php
    }

    function glossary_atoz(&$GLOSSARY) {
    // Print out a nice list of lettered links to glossary pages

        $letters = array ();

        foreach ($GLOSSARY->alphabet as $letter => $eps) {
            // if we're writing out the current letter (list or item)
            if ($letter == $GLOSSARY->current_letter) {
                // if we're in item view - show the letter as "on" but make it a link
                if ($GLOSSARY->current_term != '') {
                    $URL = new URL('glossary');
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
                $URL = new URL('glossary');
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
        for($n=0; $n<13; $n++) {
            print $letters[$n];
        }
        ?>
                        </ul>
                        <ul>
    <?php
        for($n=13; $n<26; $n++) {
            print $letters[$n];
        }
        ?>
                        </ul>
                    </div>
        <?php
    }

    function glossary_display_term(&$GLOSSARY) {
    // Display a single glossary term
        global $this_page;

        $term = $GLOSSARY->current_term;

        $term['body'] = $GLOSSARY->glossarise($term['body'], 0, 1);

        // add some extra controls for the administrators
        if ($this_page == "admin_glossary"){
            print "<a id=\"gl".$term['glossary_id']."\"></a>";
            print "<h3>" . $term['title'] . "</h3>";
            $URL = new URL('admin_glossary');
            $URL->insert(array("delete_confirm" => $term['glossary_id']));
            $delete_url = $URL->generate();
            $admin_links = "<br><small><a href=\"".$delete_url."\">delete</a></small>";
        }
        else {
            $admin_links = "";
        }

        if (isset($term['user_id'])) {
            $URL = new URL('userview');
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
            $URL = new URL('search');
            // remember to quote the term for phrase matching in search
            $URL->insert(array('s' => '"'.$term['title'].'"'));
            $search_url = $URL->generate();
            printf ("\t\t\t\t<p>Search hansard for \"<a href=\"%s\" title=\"View search results for this glossary item\">%s</a>\"</p>", $search_url, $term['title']);
        }
    }



    function glossary_display_match_list(&$GLOSSARY) {
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
            <ul class="glossary"><?
            foreach ($GLOSSARY->search_matches as $match) {
                $URL = new URL('glossary');
                $URL->insert(array('gl' => $match['glossary_id']));
                $URL->remove(array('g'));
                $term_link = $URL->generate('url');
                ?><li><a href="<?php echo $term_link ?>"><?php echo $match['title']?></a></li><?
            }
            ?></ul>
<?php
    }

    function glossary_addterm_link() {
        // print a link to the "add glossary term" page
        $URL = new URL('glossary_addterm');
        $URL->remove(array("g"));
        $glossary_addterm_link = $URL->generate('url');
        print "<small><a href=\"" . $glossary_addterm_link . "\">Add a term to the glossary</a></small>";
    }

    function glossary_addlink_link() {
        // print a link to the "add external link" page
        $URL = new URL('glossary_addlink');
        $URL->remove(array("g"));
        $glossary_addlink_link = $URL->generate('url');
        print "<small><a href=\"" . $glossary_addlink_link . "\">Add an external link</a></small>";
    }


    function glossary_link() {
        // link to the glossary with no epobject_id - i.e. show all entries
        $URL = new URL('glossary');
        $URL->remove(array("g"));
        $glossary_link = $URL->generate('url');
        print "<small><a href=\"" . $glossary_link . "\">Browse the glossary</a></small>";
    }

    function glossary_links() {
        print "<div>";
        $this->glossary_link();
        print "<br>";
        $this->glossary_addterm_link();
        print "</div>";
    }

    function page_links ($pagedata) {
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
            $URL = new URL($this_page);
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



    function comment_form ($commentdata) {
        // Comment data must at least contain an epobject_id.
        // Comment text is optional.
        // 'return_page' is either 'debate' or 'wran'.
        /* array (
            'epobject_id' => '7',
            'gid' => '2003-02-02.h34.2',
            'body' => 'My comment text is here.',
            'return_page' => 'debate'
          )
        */
        global $THEUSER, $this_page;

        if (!isset($commentdata['epobject_id']) || !is_numeric($commentdata['epobject_id'])) {
            $this->error_message("Sorry, we need an epobject id");
            return;
        }

        if (!$THEUSER->isloggedin()) {
            // The user is not logged in.

            // The URL of this page - we'll want to return here after joining/logging in.
            $THISPAGEURL = new URL($this_page);

            // The URLs to login / join pages.
            $LOGINURL = new URL('userlogin');
            $LOGINURL->insert(array('ret'=>$THISPAGEURL->generate().'#addcomment'));
            $JOINURL = new URL('userjoin');
            $JOINURL->insert(array('ret'=>$THISPAGEURL->generate().'#addcomment'));

            ?>
                <p><a href="<?php echo $LOGINURL->generate(); ?>">Sign in</a> or <a href="<?php echo $JOINURL->generate(); ?>">join</a> to post a public annotation.</p>
<?php
            return;

        } else if (!$THEUSER->is_able_to('addcomment')) {
            // The user is logged in but not allowed to post a comment.

            ?>
                <p>You are not allowed to post annotations.</p>
<?php
            return;
        }

        // We can post a comment...

        $ADDURL = new URL('addcomment');
        $RULESURL = new URL('houserules');
        ?>
                <h4>Type your annotation</h4>
                <a name="addcomment"></a>

                <p><small>
Please read our <a href="<?php echo $RULESURL->generate(); ?>"><strong>House Rules</strong></a> before posting an annotation.
Annotations should be information that adds value to the contribution, not opinion, rants, or messages to a politician.
</small></p>

                <form action="<?php echo $ADDURL->generate(); ?>" method="post">
                    <p><textarea name="body" rows="15" cols="55"><?php
        if (isset($commentdata['body'])) {
            echo htmlentities($commentdata['body']);
        }
        ?></textarea></p>

                    <p><input type="submit" value="Preview" class="submit">
<?php
        if (isset($commentdata['body'])) {
            echo '<input type="submit" name="submitcomment" value="Post" class="submit">';
        }
?>
</p>
                    <input type="hidden" name="epobject_id" value="<?php echo $commentdata['epobject_id']; ?>">
                    <input type="hidden" name="gid" value="<?php echo $commentdata['gid']; ?>">
                    <input type="hidden" name="return_page" value="<?php echo $commentdata['return_page']; ?>">
                </form>
<?php
    }


    function display_commentreport ($data) {
        // $data has key value pairs.
        // Called from $COMMENT->display_report().

        if ($data['user_id'] > 0) {
            $USERURL = new URL('userview');
            $USERURL->insert(array('id'=>$data['user_id']));
            $username = '<a href="' . $USERURL->generate() . '">' . htmlentities($data['user_name']) . '</a>';
        } else {
            $username = htmlentities($data['user_name']);
        }
        ?>
                <div class="comment">
                    <p class="credit"><strong>Annotation report</strong><br>
                    <small>Reported by <?php echo $username; ?> on <?php echo $data['reported']; ?></small></p>

                    <p><?php echo htmlentities($data['body']); ?></p>
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


    function display_commentreportlist ($data) {
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

            $EDITURL = new URL('admin_commentreport');

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
                    htmlentities($report['firstname'] . ' ' . $report['lastname']),
                    htmlentities($body),
                    $report['reported'],
                    $editlink
                );

            }

            $this->display_table($tabledata);

        } else {

            print "<p>There are no outstanding annotation reports.</p>\n";
        }

    }



    function display_calendar_month ($month, $year, $dateArray, $page) {
        // From http://www.zend.com/zend/trick/tricks-Oct-2002.php
        // Adjusted for style, putting Monday first, and the URL of the page linked to.

        // Used in templates/html/hansard_calendar.php

        // $month and $year are integers.
        // $dateArray is an array of dates that should be links in this month.
        // $page is the name of the page the dates should link to.

        // Create array containing abbreviations of days of week.
        $daysOfWeek = array('Mon','Tue','Wed','Thu','Fri','Sat','Sun');

        // What is the first day of the month in question?
        $firstDayOfMonth = mktime(0,0,0,$month,1,$year);

        // How many days does this month contain?
        $numberDays = date('t',$firstDayOfMonth);

        // Retrieve some information about the first day of the
        // month in question.
        $dateComponents = getdate($firstDayOfMonth);

        // What is the name of the month in question?
        $monthName = $dateComponents['month'];

        // If this calendar is for this current, real world, month
        // we get the value of today, so we can highlight it.
        $nowDateComponents = getdate();
        if ($nowDateComponents['mon'] == $month && $nowDateComponents['year'] == $year) {
            $toDay = $nowDateComponents['mday'];
        } else {
            $toDay = '';
        }

        // What is the index value (0-6) of the first day of the
        // month in question.

        // Adjusted to cope with the week starting on Monday.
        $dayOfWeek = $dateComponents['wday'] - 1;

        // Adjusted to cope with the week starting on Monday.
        if ($dayOfWeek < 0) {
            $dayOfWeek = 6;
        }

        // Create the table tag opener and day headers

        $calendar  = "\t\t\t\t<div class=\"calendar\">\n";
        $calendar .= "\t\t\t\t<table border=\"0\">\n";
        $calendar .= "\t\t\t\t<caption>$monthName $year</caption>\n";
        $calendar .= "\t\t\t\t<thead>\n\t\t\t\t<tr>";

        // Create the calendar headers

        foreach($daysOfWeek as $day) {
            $calendar .= "<th>$day</th>";
        }

        // Create the rest of the calendar

        // Initiate the day counter, starting with the 1st.

        $currentDay = 1;

        $calendar .= "</tr>\n\t\t\t\t</thead>\n\t\t\t\t<tbody>\n\t\t\t\t<tr>";

        // The variable $dayOfWeek is used to
        // ensure that the calendar
        // display consists of exactly 7 columns.

        if ($dayOfWeek > 0) {
            $calendar .= "<td colspan=\"$dayOfWeek\">&nbsp;</td>";
        }

        $DAYURL = new URL($page);

        while ($currentDay <= $numberDays) {

            // Seventh column (Sunday) reached. Start a new row.

            if ($dayOfWeek == 7) {

                $dayOfWeek = 0;
                $calendar .= "</tr>\n\t\t\t\t<tr>";
            }


            // Is this day actually Today in the real world?
            // If so, higlight it.
            if ($currentDay == $toDay) {
                $calendar .= '<td class="on">';
            } else {
                $calendar .= '<td>';
            }

            // Is the $currentDay a member of $dateArray? If so,
            // the day should be linked.
            if (in_array($currentDay,$dateArray)) {

                $date = sprintf("%04d-%02d-%02d", $year, $month, $currentDay);

                $DAYURL->insert(array('d'=>$date));

                $calendar .= "<a href=\"" . $DAYURL->generate() . "\">$currentDay</a></td>";

                // $currentDay is not a member of $dateArray.

            } else {

                $calendar .= "$currentDay</td>";
            }

            // Increment counters

            $currentDay++;
            $dayOfWeek++;
        }

        // Complete the row of the last week in month, if necessary

        if ($dayOfWeek != 7) {

            $remainingDays = 7 - $dayOfWeek;
            $calendar .= "<td colspan=\"$remainingDays\">&nbsp;</td>";
        }


        $calendar .= "</tr>\n\t\t\t\t</tbody>\n\t\t\t\t</table>\n\t\t\t\t</div> <!-- end calendar -->\n\n";

        return $calendar;

    }


    function display_table($data) {
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



    function admin_menu () {
        // Returns HTML suitable for putting in the sidebar on Admin pages.
        global $this_page, $DATA;

        $pages = array ('admin_home',
                'admin_comments','admin_trackbacks', 'admin_searchlogs', 'admin_popularsearches', 'admin_failedsearches',
                'admin_statistics',
                'admin_commentreports', 'admin_glossary', 'admin_glossary_pending', 'admin_badusers',
                'admin_alerts', 'admin_photos', 'admin_mpurls'
                );

        $links = array();

        foreach ($pages as $page) {
            $title = $DATA->page_metadata($page, 'title');

            if ($page != $this_page) {
                $URL = new URL($page);
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

