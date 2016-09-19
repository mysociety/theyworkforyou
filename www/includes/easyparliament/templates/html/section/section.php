<?php
    global $hansardmajors;

    $previous_speech_time = null;

?>

<div id="speech_wrapper">
<div class="debate-header regional-header regional-header--<?= $current_assembly ?>">
    <div class="regional-header__overlay"></div>
    <div class="full-page__row">
        <div class="debate-header__content full-page__unit" id="debate-title">
            <h1><?= $heading ?></h1>
            <p class="lead">
                <?= $intro ?> <?= $location ?>
                <?php if ($debate_time_human) { ?>at <?= $debate_time_human ?><?php } ?>
                on <a href="<?= $debate_day_link ?>"><?= $debate_day_human ?></a>.
            </p>
            <p class="cta rs_skip">
                <a class="button alert" href="/alerts/?alertsearch=<?= urlencode($email_alert_text) ?>">Alert me about debates like this</a>
            </p>
            <div id="readspeaker_button1" class="rs_skip rsbtn rs_preserve">
                <a rel="nofollow" class="rsbtn_play" accesskey="L" title="Listen to this page using ReadSpeaker" href="//app-eu.readspeaker.com/cgi-bin/rsent?customerid=5&lang=en_uk&amp;voice=Alice&readid=speech_wrapper&url=<?= urlencode($page_url) ?>">
                        <span class="rsbtn_left rsimg rspart"><span class="rsbtn_text"><span>Listen</span></span></span>
                        <span class="rsbtn_right rsimg rsplay rspart"></span>
                    </a>
                </div>

        </div>
    </div>
    <?php $section = true; include '_section_nav.php'; ?>
</div>

<div class="full-page">

<?php $section = true; include '_section_content.php'; ?>

</div>
</div>

<?php include '_section_footer.php'; ?>
