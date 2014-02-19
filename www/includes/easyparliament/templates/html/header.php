<?php

include_once INCLUDESPATH . '../../commonlib/phplib/gaze.php';
//include_once INCLUDESPATH . 'easyparliament/member.php';

?>
<!DOCTYPE html>
<!--[if IE 8]><html class="no-js lt-ie9" lang="en"><![endif]-->
<!--[if gt IE 8]><!--><html class="no-js" lang="en"><!--<![endif]-->
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <title><?php echo preg_replace('#<[^>]*>#', '', $page_data['title']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <?=$page_data['meta_description'] ?>
    <meta name="keywords" content="<?php echo htmlentities($page_data['meta_keywords']); ?>">
    <?php
        if (DEVSITE) {
            echo '<meta name="robots" content="noindex,nofollow">';
        } elseif ($page_data['robots']) { 
            echo '<meta name="robots" content="' . $page_data['robots'] . '">';
        }
    ?>


    <link rel="author" title="Send feedback" href="mailto:<?php echo str_replace('@', '&#64;', CONTACTEMAIL); ?>">
    <link rel="home" title="Home" href="http://<?php echo DOMAIN; ?>/">

    <meta property="og:title" content="TheyWorkForYou">
    <meta property="og:type" content="website">
    <meta property="og:url" content="http://<?php echo DOMAIN; ?>">
    <meta property="og:image" content="http://<?php echo DOMAIN; ?>/images/favicon-256.png">
    <meta property="og:description" content="TheyWorkForYou is a website which makes it easy to keep track of your local MP's activities.">
    <meta property="fb:admins" content="143203489083755">

    <script type="text/javascript" src="/js/jquery.js"></script>
    <script type="text/javascript" src="/js/jquery.cookie.js"></script>
    <script type="text/javascript" src="/js/jquery.fittext.js"></script>
    <script type="text/javascript" src="/jslib/share/share.js"></script>
    <script type="text/javascript" src="/js/main.js"></script>
    <script type="text/javascript" src="/js/bar.js"></script>
    <script type="text/javascript" src="/js/custom.modernizr.js"></script>
    <?=$page_data['linkshtml'] ?>
    <link rel="stylesheet" href="<?php echo WEBPATH; ?>style/stylesheets/app.css" type="text/css">
    <!--[if IE 8]><link rel="stylesheet" href="<?php echo WEBPATH; ?>style/stylesheets/ie8.css" type="text/css"><![endif]-->
    <link rel="stylesheet" href="/jslib/share/share.css" type="text/css" media="screen">
    <script type="text/javascript" src="/js/respond.min.js"></script>
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

<!-- Load GA experiments API for MP alert CTA test -->
<script src="//www.google-analytics.com/cx/api.js?experiment=qjU6dnQpTO6imB8iAdsISg"></script>

<script type="text/javascript">

    // Select GA experiment variation
    var chosenVariation = cxApi.chooseVariation();

    // Text variations to use
    var pageVariations = [
    function () {},  // Original
    function () {    // Verbose
      document.getElementById('mp-alert-cta-text').innerHTML = '<strong>Receive an update</strong><small> whenever this person is active in Parliament</small>';
    },
    function () {    // Slash Separated
      document.getElementById('mp-alert-cta-text').innerHTML = '<strong>Get email alerts</strong><small> on this person&rsquo;s activity</small>';
    },
    function () {    // Variant 3
      document.getElementById('mp-alert-cta-text').innerHTML = '<strong>Get email updates</strong><small> whenever this person is active in Parliament</small>';
    },
    function () {    // Variant 4
      document.getElementById('mp-alert-cta-text').innerHTML = '<strong>Receive an alert</strong><small> whenever this person is active in Parliament</small>';
    }
  ];

  // When page is ready, switch the content
  $(document).ready(
    pageVariations[chosenVariation]
  );

    (function (i,s,o,g,r,a,m) {i['GoogleAnalyticsObject']=r;i[r]=i[r]||function () {
    (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
    m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
    })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

    ga('create', 'UA-660910-1');  // Replace with your property ID.
    ga('send', 'pageview');

  public function trackFormSubmit(form, category, name, value) {
    try {
      ga('send', 'event', category, name, value);
    } catch (err) {}
    setTimeout(function () {
      form.submit();
    }, 100);
  }

  public function trackLinkClick(link, category, name, value) {
    try {
      ga('send', 'event', category, name, value);
    } catch (err) {}
    setTimeout(function () {
      document.location.href = link.href;
    }, 100);
  }
</script>

<?php      } ?>

</head>
<body class="antialiased">

    <div id="fb-root"></div>
    <script>
    window.fbAsyncInit = function () {
        FB.init({
        appId      : '227648394066332',
        xfbml      : true
        });

        FB.Event.subscribe('edge.create', function (targetUrl) {
            ga('send', 'social', 'facebook', 'like', targetUrl);
        });

        FB.Event.subscribe('edge.remove', function (targetUrl) {
            ga('send', 'social', 'facebook', 'unlike', targetUrl);
        });

    };

    (function (d, s, id) {
      var js, fjs = d.getElementsByTagName(s)[0];
      if (d.getElementById(id)) return;
      js = d.createElement(s); js.id = id;
      js.src = "//connect.facebook.net/en_GB/all.js";
      fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));
    </script>

<?php
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
?>

    <div class="ms-header">
        <nav class="ms-header__row">
            <a class="ms-header__logo" href="http://www.mysociety.org">mySociety</a>
        </nav>
    </div>
    <header class="brand-header">
        <div class="brand-header__row">
            <div class="brand-header__title-unit">
                <h1 class="brand-header__title"><a href="/">TheyWorkForYou</a></h1>
            </div>
            <nav class="primary-navigation-bar assembly">
                <span class="menu-dropdown">
                    <a href="#" class="button">UK</a>
                </span>
                <ul class="nav-menu closed">
                <?php foreach ($page_data['site_nav'] as $nav_link) {?>
                    <li><?php print $nav_link['link']; ?></li>
                <?php } ?>
                </ul>
            </nav>
            <nav class="primary-navigation-bar sections">
                <span class="menu-dropdown">
                    <a href="#" class="button menu-dropdown--button">Menu</a>
                </span>
                <ul class="nav-menu closed">
                <?php foreach ($page_data['site_nav'] as $nav_link) {?>
                    <li><?php print $nav_link['link']; ?></li>
                <?php } ?>
                </ul>
            </nav>
        </div>
    </header>

<?php

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

        return array(
            'top' => $top_highlight,
            'bottom' => $bottom_highlight,
            'top_selected' => $selected_top_link,
            'section' => $section,
        );
    }

    function menu() {
        global $this_page, $DATA, $THEUSER;

        // Page names mapping to those in metadata.php.
        // Links in the top menu, and the sublinks we see if
        // we're within that section.
        $items = array (
            array('home'),
            array('hansard', 'mps', 'peers', 'alldebatesfront', 'wranswmsfront', 'pbc_front', 'calendar_summary'),
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
        $SEARCH = new URL('search');
        $SEARCH->reset(); // do not want search in MP id etc
        /*
        ?>
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
        </div>-->
<?php */ ?>

<?php
    }

    function user_bar($top_highlight='') {
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
            <li><a href="<?php echo $LOGINURL->generate(); ?>" title="<?php echo $logintitle; ?>"<?php echo $loginclass; ?>><?php echo $logintext; ?></a></li>
            <li><a href="<?php echo $JOINURL->generate(); ?>" title="<?php echo $jointitle; ?>"<?php echo $joinclass; ?>><?php echo $jointext; ?></a></li>
<?php
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
                $logintitle = $menudata['title'];
                $URL = new URL($item);
                echo '<li><a href="' . $URL->generate() . '">' . $logintext . '</a></li>';
            }
        }
    }
