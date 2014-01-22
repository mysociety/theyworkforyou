<?php

include_once '../../includes/easyparliament/init.php';

$NEWPAGE->page_start();
$NEWPAGE->stripe_start('side');

?>

<div class="topic-header">

    <h1>The NHS</h1>
    <h1 class="subheader">&amp; the UK Parliament</h1>

    <p>The NHS is a major political issue right now &mdash; it's mentioned a lot
    in Parliament, so it can be hard to know exactly where to find the important
    debates. Here are some places you might want to start:</p>

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
                        <a href="http://www.theyworkforyou.com/debates/?id=2011-01-31b.605.0">
                            Health and Social Care Bill
                        </a>
                    </h4>

                    <p>Andrew Lansley, Secretary of State for Health, sets out plans for a reorganisation of the NHS, which MPs then debate and vote on.</p>

                </div>

            </div>
        </div>

        <div class="large-6 columns">
            <div class="row">

                <div class="large-3 columns">
                    <i class="cta-icon fi-comment-quotes"></i>
                </div>

                <div class="large-9 columns">

                    <h4>
                        <a href="http://www.theyworkforyou.com/debates/?id=2012-01-16a.536.0">
                            NHS (Private Sector)
                        </a>
                    </h4>

                    <p>A year later, the opposition puts forward its concerns with the model, ending in a further vote.</p>

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
                        <a href="http://www.theyworkforyou.com/search/?s=%22nhs%22">
                            Search the whole site
                        </a>
                    </h4>

                    <p>Search TheyWorkForYou to find mentions of the NHS from all areas of the UK parliament. You may also filter your results by time, speaker and section.</p>

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
                        <a href="http://www.theyworkforyou.com/alert/?alertsearch=%22nhs%22">
                            Sign up for email alerts
                        </a>
                    </h4>

                    <p>We'll let you know every time the NHS is mentioned in Parliament.</p>

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
            <img src="/images/topics/nhs.jpg">
        </div>

        <div>
            <p class="large">We are TheyWorkForYou.com, the website that makes UK parliament easy to follow.</p>
        </div>

        <div>
            <h4>Give us feedback!</h4>
            <p>How are we doing? Did you find what you were looking for? <a href="#">Drop us a line</a> and let us know!</p>
        </div>

    </div>'
);

$NEWPAGE->stripe_end(array($sidebar));
$NEWPAGE->page_end();
