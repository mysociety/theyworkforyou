<!DOCTYPE html>
<!--[if IE 8]><html class="no-js lt-ie9" lang="en"><![endif]-->
<!--[if gt IE 8]><!--><html class="no-js" lang="en"><!--<![endif]-->
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <title><?= preg_replace('#<[^>]*>#', '', $page_title) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <?php if (isset($meta_description)): ?>
    <meta name="description" content="<?= _htmlentities($meta_description) ?>">
    <?php endif; ?>
    <meta name="keywords" content="<?= _htmlentities($meta_keywords); ?>">
    <?php
        if (DEVSITE) {
            echo '<meta name="robots" content="noindex,nofollow">';
        } elseif (!empty($page_data['robots'])) {
            echo '<meta name="robots" content="' . $page_data['robots'] . '">';
        }
    ?>


    <link rel="author" title="Send feedback" href="mailto:<?= str_replace('@', '&#64;', CONTACTEMAIL) ?>">
    <link rel="home" title="Home" href="http://<?= DOMAIN ?>/">

    <meta property="og:title" content="TheyWorkForYou">
    <meta property="og:type" content="website">
    <meta property="og:url" content="http://<?= DOMAIN ?>">
    <meta property="og:image" content="http://<?= DOMAIN ?>/images/favicon-256.png">
    <meta property="og:description" content="TheyWorkForYou is a website which makes it easy to keep track of your local MP's activities.">
    <meta property="fb:admins" content="143203489083755">

    <script type="text/javascript" src="/js/jquery.js"></script>
    <script type="text/javascript" src="/js/jquery.cookie.js"></script>
    <script type="text/javascript" src="/js/jquery.fittext.js"></script>
    <script type="text/javascript" src="/jslib/share/share.js"></script>
    <script type="text/javascript" src="/js/main.js"></script>
    <script type="text/javascript" src="/js/bar.js"></script>
    <script type="text/javascript" src="/js/custom.modernizr.js"></script>

    <?php foreach ($header_links as $link): ?>
    <link rel="<?= $link['rel'] ?>" title="<?= $link['title'] ?>" href="<?= $link['href'] ?>">
    <?php endforeach; ?>

    <link rel="stylesheet" href="<?= WEBPATH ?>style/stylesheets/app.css" type="text/css">
    <!--[if IE 8]><link rel="stylesheet" href="<?= WEBPATH ?>style/stylesheets/ie8.css" type="text/css"><![endif]-->
    <link rel="stylesheet" href="/jslib/share/share.css" type="text/css" media="screen">
    <script type="text/javascript" src="/js/respond.min.js"></script>

    <?php if (isset ($page_rss_url)): ?>
    <link rel="alternate" type="application/rss+xml" title="TheyWorkForYou RSS" href="<?= $page_rss_url ?>">
    <?php endif; ?>

    <link rel="apple-touch-icon" href="/images/apple-touch-60.png" />
    <link rel="apple-touch-icon" sizes="76x76" href="/images/apple-touch-76.png" />
    <link rel="apple-touch-icon" sizes="120x120" href="/images/apple-touch-120.png" />
    <link rel="apple-touch-icon" sizes="152x152" href="/images/apple-touch-152.png" />

    <?php if (!DEVSITE): ?>

    <script type="text/javascript">

        (function (i,s,o,g,r,a,m) {i['GoogleAnalyticsObject']=r;i[r]=i[r]||function () {
        (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
        m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
        })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

        ga('create', 'UA-660910-1');  // Replace with your property ID.
        ga('send', 'pageview');

        function trackFormSubmit(form, category, name, value) {
            try {
                ga('send', 'event', category, name, value);
            } catch (err) {}
            setTimeout(function () {
                form.submit();
            }, 100);
        }

        function trackLinkClick(link, category, name, value) {
            try {
                ga('send', 'event', category, name, value);
            } catch (err) {}
            setTimeout(function () {
                document.location.href = link.href;
            }, 100);
        }
    </script>

<?php endif; ?>

</head>

<body>

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
        if (isset($country)) {
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
                    <a href="#" class="button"><?= $assembly_nav_current ?></a>
                </span>
                <ul class="nav-menu closed">
                <?php foreach ($assembly_nav_links as $nav_link) {?>
                    <li><a href="<?= $nav_link['href']; ?>" title="<?= $nav_link['title']; ?>" class="<?= $nav_link['classes']; ?>"><?= $nav_link['text'] ?></a></li>
                <?php } ?>
                </ul>
            </nav>
            <nav class="primary-navigation-bar sections">
                <span class="menu-dropdown">
                    <a href="#" class="button menu-dropdown--button">Menu</a>
                </span>
                <ul class="nav-menu closed">
                <?php foreach ($section_nav_links as $nav_link): ?>
                    <li><a href="<?= $nav_link['href']; ?>" title="<?= $nav_link['title']; ?>" class="<?= $nav_link['classes']; ?>"><?= $nav_link['text'] ?></a></li>
                <?php endforeach; ?>
                    <li class="user-menu">
                        <span class="menu-dropdown">
                            <a href="#" class="button"><img src="/style/img/user.png"></a>
                        </span>
                        <ul class="nav-menu closed">
                        <?php foreach ($user_nav_links as $nav_link): ?>
                            <li><a href="<?= $nav_link['href']; ?>" title="<?= $nav_link['title']; ?>" class="<?= $nav_link['classes']; ?>"><?= $nav_link['text'] ?></a></li>
                        <?php endforeach; ?>
                        </ul>
                    </li>
                    <li id="search-fallback">
                        <a href="/search/" id="fallback"><img src="/style/img/search.png"></a>
                    </li>
                    <li id="search-wrapper">
                        <form action="/search/" method="get">
                            <label for="header_search_input"><img src="/style/img/search.png"></label>
                            <input type="text" id="header_search_input" name="q" placeholder="Type search terms and hit enter...">
                        </form>
                    </li>
                    <li class="assembly-sub-menu">
                        <ul>
                        <?php foreach ($assembly_nav_links as $nav_link) {?>
                            <li><a href="<?= $nav_link['href']; ?>" title="<?= $nav_link['title']; ?>" class="<?= $nav_link['classes']; ?>"><?= $nav_link['text'] ?></a></li>
                        <?php } ?>
                        </ul>
                    </li>
                </ul>
            </nav>
        </div>
    </header>
