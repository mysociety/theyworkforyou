<?php

include_once "../../includes/easyparliament/init.php";

$NEWPAGE->page_start();
$NEWPAGE->stripe_start('side');

?>

<div class="topic-header">

    <h1>Benefits</h1>
    <h1 class="subheader">&amp; the UK Parliament</h1>

    <p>Benefits are a major political issue right now - they are mentioned a lot
        in Parliament, so it can be hard to know exactly where to find the
        important debates.</p>

    <p>Here are some places you might want to start:</p>

</div>

<hr>

<div class="topic-ctas">
<!-- Begin CTAs -->

    <div class="row">

        <div class="large-6 columns">
            <div class="row">

                <div class="large-3 columns">
                    <i class="cta-icon fi-comment-quotes"></i>
                </div>

                <div class="large-9 columns">

                    <h4>
                        <a href="http://www.theyworkforyou.com/lords/?id=2013-02-13a.664.3">
                            Universal Credit Regulations
                        </a>
                    </h4>

                    <p>Lords debate, and approve, the consolidation of all benefits into the Universal Credit system.</p>

                </div>

            </div>
        </div>

        <div class="large-6 columns">
            <div class="row">

                <div class="large-3 columns">
                    <i class="cta-icon fi-page"></i>
                </div>

                <div class="large-9 columns">

                    <h4>
                        <a href="http://www.theyworkforyou.com/lords/?id=2013-02-11a.457.8">
                            Welfare Benefits Up-rating Bill
                        </a>
                    </h4>

                    <p>Lords debate a cap on annual increases to working-age benefits.</p>

                </div>

            </div>
        </div>

    </div>

    <div class="row">

        <div class="large-6 columns">
            <div class="row">

                <div class="large-3 columns">
                    <i class="cta-icon fi-magnifying-glass"></i>
                </div>

                <div class="large-9 columns">

                    <h4>
                        <a href="http://www.theyworkforyou.com/search/?s=%22benefits%22">
                            Search the whole site
                        </a>
                    </h4>

                    <p>Search TheyWorkForYou to find mentions of benefits from all areas of the UK parliament. You may also filter your results by time, speaker and section.</p>

                </div>

            </div>
        </div>

        <div class="large-6 columns">
            <div class="row">

                <div class="large-3 columns">
                    <i class="cta-icon fi-megaphone"></i>
                </div>

                <div class="large-9 columns">

                    <h4>
                        <a href="http://www.theyworkforyou.com/alert/?alertsearch=%22benefits%22">
                            Sign up for email alerts
                        </a>
                    </h4>

                    <p>We'll let you know every time benefits are mentioned in Parliament.</p>

                </div>

            </div>
        </div>

    </div>

<!-- End CTAs -->
</div>

<script>
  $(".cta-icon").fitText(0.08);
</script>

<?php

$sidebar = array(
    'type' => 'html',
    'content' => '<div class="topic-sidebar">

        <div class="callout-image">
            <img src="/images/topics/benefits.jpg">
        </div>

        <div>
            <p class="large">We are TheyWorkForYou.com, the website that makes UK parliament easy to follow.</p>
        </div>

        <div>
            <h4>Give us feedback!</h4>
            <p>How are we doing? Did you find what you were looking for? <a href="#">Drop us a line</a> and let us know!</p>
        </div>

        <div>

            <h4>Need practical information or help with benefits?</h4>

            <p>You\'re probably better off visiting <a href="https://www.gov.uk/browse/benefits" onclick="trackLinkClick(this, \'Links\', \'GOVUK\', \'Benefits\'); return false;"> "Benefits" on GOV.UK</a>.</p>

        </div>

    </div>'
);

$NEWPAGE->stripe_end(array($sidebar));
$NEWPAGE->page_end();
