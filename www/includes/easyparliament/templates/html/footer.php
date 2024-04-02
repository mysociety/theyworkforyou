<div id="footer" class="mysoc-footer" role="contentinfo">
    <div class="row">

        <div class="medium-5 columns">
            <h2 class="mysoc-footer__site-name">TheyWorkForYou</h2>
            <div class="mysoc-footer__site-description">
                <p><?= gettext('Making it easy to keep an eye on the UK’s parliaments. Discover who represents you, how they’ve voted and what they’ve said in debates – simply and clearly.') ?></p>
            </div>
            <form method="post" class="footer__newsletter-form" action="//mysociety.us9.list-manage.com/subscribe/post?u=53d0d2026dea615ed488a8834&amp;id=287dc28511" onsubmit="trackFormSubmit(this, 'FooterNewsletterSignup', 'submit', null); return false;">
                <p><?= gettext('Sign up to mySociety’s newsletter') ?></p>
                <div class="row collapse">
                    <div class="small-8 columns">
                        <input type="email" placeholder="<?= gettext('Your email address') ?>" name="EMAIL"/>
                    </div>
                    <div class="small-4 columns">
                        <label style="position: absolute; left: -5000px;">
                          Leave this box empty: <input type="text" name="b_53d0d2026dea615ed488a8834_287dc28511" tabindex="-1" value="" />
                        </label>
                        <input type="hidden" name="group[11745][32]" value="1">
                        <input type="submit" value="<?=gettext('Subscribe') ?>" name="subscribe" class="button prefix">
                    </div>
                </div>
                <p><a href="https://www.mysociety.org/privacy#newsletter"><?= gettext('Your data') ?></a></p>
            </form>
        </div>

        <div class="medium-4 columns">
            <nav class="mysoc-footer__links">
                <ul>
                  <?php foreach ($footer_links['about'] as $footer_link): ?>
                    <li role="presentation"><a href="<?= $footer_link['href'] ?>" title="<?= $footer_link['title'] ?>" class="<?= $footer_link['classes'] ?>"><?= $footer_link['text'] ?></a></li>
                  <?php endforeach; ?>
                </ul>
                <ul>
                  <?php foreach ($footer_links['tech'] as $footer_link): ?>
                    <li role="presentation"><a href="<?= $footer_link['href'] ?>" title="<?= $footer_link['title'] ?>" class="<?= $footer_link['classes'] ?>"><?= $footer_link['text'] ?></a></li>
                  <?php endforeach; ?>
                </ul>
            </nav>
        </div>

        <div class="medium-3 columns">
            <div class="mysoc-footer__donate">
              <p><?= gettext('This site is not publicly funded. Support our mission of making UK politics more transparent and accessible.') ?></p>
              <a href=/support-us/?utm_source=theyworkforyou.com&utm_content=footer+donate+now&utm_medium=link&utm_campaign=mysoc_footer" class="mysoc-footer__donate__button"><?= gettext('Donate now') ?></a>
            </div>
        </div>

    </div>
    <div class="row">

        <hr class="mysoc-footer__divider" role="presentation">

    </div>
    <div class="row">

        <div class="medium-5 columns">
            <div class="mysoc-footer__orgs">
                <p class="mysoc-footer__org">
                    <?= gettext('Built by') ?>
                    <a href="https://www.mysociety.org?utm_source=theyworkforyou.com&utm_content=footer+logo&utm_medium=link&utm_campaign=mysoc_footer" class="mysoc-footer__org__logo mysoc-footer__org__logo--mysociety">mySociety</a>
                </p>
            </div>
        </div>

        <div class="medium-4 columns">
            <div class="mysoc-footer__legal">
            <p><?= sprintf(gettext('%s is a registered charity in England and Wales (1076346) and a limited company (03277032). We provide commercial services through our wholly owned subsidiary %s (05798215).'), '<a href="https://www.mysociety.org?utm_source=theyworkforyou.com&utm_content=footer+full+legal+details&utm_medium=link&utm_campaign=mysoc_footer">mySociety</a>', '<a href="https://www.societyworks.org?utm_source=theyworkforyou.com&utm_content=footer+full+legal+details&utm_medium=link&utm_campaign=mysoc_footer">SocietyWorks Ltd</a>') ?></p>
            </div>
        </div>

        <div class="medium-3 columns">
            <ul class="mysoc-footer__badges">
                <li role="presentation"><a href="https://github.com/mysociety/theyworkforyou" class="mysoc-footer__badge mysoc-footer__badge--github">Github</a></li>
                <li role="presentation"><a href="https://twitter.com/theyworkforyou" class="mysoc-footer__badge mysoc-footer__badge--twitter">Twitter</a></li>
                <li role="presentation"><a href="https://www.facebook.com/TheyWorkForYou" class="mysoc-footer__badge mysoc-footer__badge--facebook">Facebook</a></li>
            </ul>
        </div>

    </div>

    <?php

        // DAMN, this really shouldn't be in PAGE.
        $db = new ParlDB;
        $db->display_total_duration();

        $duration = getmicrotime() - STARTTIME;
        twfy_debug("TIME", "Total time for page: $duration seconds.");
        if (!isset($_SERVER['WINDIR'])) {
            $rusage = getrusage();
            $duration = $rusage['ru_utime.tv_sec'] * 1000000 + $rusage['ru_utime.tv_usec'] - STARTTIMEU;
            twfy_debug('TIME', "Total user time: $duration microseconds.");
            $duration = $rusage['ru_stime.tv_sec'] * 1000000 + $rusage['ru_stime.tv_usec'] - STARTTIMES;
            twfy_debug('TIME', "Total system time: $duration microseconds.");
        }

?>

</div> <!-- end #footer -->
</div> <!-- end #container -->

<script src="<?= cache_version("js/jquery-1.11.3.min.js") ?>"></script>
<script src="<?= cache_version("js/jquery.cookie.js") ?>"></script>
<script src="<?= cache_version("js/accessible-autocomplete.min.js") ?>"></script>
<script src="<?= cache_version("js/main.js") ?>"></script>

<script>
window.twttr = (function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0], t = window.twttr || {};
  if (d.getElementById(id)) return t;
  js = d.createElement(s);
  js.id = id;
  js.src = "https://platform.twitter.com/widgets.js";
  fjs.parentNode.insertBefore(js, fjs);
  t._e = [];
  t.ready = function(f) {
    t._e.push(f);
  };
  return t;
}(document, "script", "twitter-wjs"));

twttr.ready(function() {
  twttr.events.bind('tweet', function() {
    ga('send', 'social', 'twitter', 'tweet', window.location.href);
  });
  twttr.events.bind('follow', function() {
    ga('send', 'social', 'twitter', 'follow', window.location.href);
  });
});

window.fbAsyncInit = function () {
  FB.init({
    appId: <?= json_encode(FACEBOOK_APP_ID) ?>,
    autoLogAppEvents: true,
    xfbml: true,
    version: 'v9.0'
  });

  FB.Event.subscribe('edge.create', function (targetUrl) {
    ga('send', 'social', 'facebook', 'like', targetUrl);
  });

  FB.Event.subscribe('edge.remove', function (targetUrl) {
    ga('send', 'social', 'facebook', 'unlike', targetUrl);
  });

  FB.Event.subscribe('message.send', function (targetUrl) {
    ga('send', 'social', 'facebook', 'share', targetUrl);
  });
};
</script>
<script async defer crossorigin="anonymous" src="https://connect.facebook.net/en_GB/sdk.js"></script>

</body>
</html>
