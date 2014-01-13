<?php

include_once "../../includes/easyparliament/init.php";

$NEWPAGE->page_start();
$NEWPAGE->stripe_start('side');

?>

<div class="topic-header">

    <h1>Crime Statistics</h1>
    <h1 class="subheader">&amp; the UK Parliament</h1>

    <p>MPs and Lords often talk about Crime Statistics, because they're a major
        political issue. Here are some places you might want to start:</p>

</div>

<hr>

<div class="topic-ctas">
<!-- Begin CTAs -->

    <div class="row">

        <div class="large-6 columns">
            <div class="row">

                <div class="large-3 columns">
                    <i class="cta-icon fi-page"></i>
                </div>

                <div class="large-9 columns">

                    <h4>
                        <a href="http://www.theyworkforyou.com/lords/?id=2013-10-29a.1482.5">
                            Anti-social Behaviour Crime and Policing Bill (second reading)
                        </a>
                    </h4>

                    <p>The House of Lords debate a proposed law, making many references to crime statistics.</p>

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
                        <a href="http://www.theyworkforyou.com/lords/?id=2013-11-28a.1576.0">
                            Police and Public trust
                        </a>
                    </h4>

                    <p>A debate on police misconduct and how much the general public trust the police not to cover up crime statistics, mistakes and misbehaviour</p>

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
                        <a href="http://www.theyworkforyou.com/search/?s=%22crime+statistics%22">
                            Search the whole site
                        </a>
                    </h4>

                    <p>Search TheyWorkForYou to find mentions of crime statistics from all areas of the UK parliament. You may also filter your results by time, speaker and section.</p>

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
                        <a href="http://www.theyworkforyou.com/alert/?alertsearch=%22crime+statistics%22">
                            Sign up for email alerts
                        </a>
                    </h4>

                    <p>We'll let you know every time crime statistics are mentioned in Parliament.</p>

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
            <img src="/images/topics/crime-stats.jpg">
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
