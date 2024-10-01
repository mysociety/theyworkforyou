<?php
# To include a promo banner, uncomment this line and update the text in the corresponding template
# include 'homepage/promo-banner.php';
?>

<?php include 'homepage/search-box.php'; ?>

<div class="full-page__row">
    <div class="homepage-panels">

        <div class="panel panel--flushtop clearfix">
            <div class="row nested-row">
            <div class="homepage-content-section homepage-parl-list">
            <h2>The UK's Parliaments and Assemblies</h2>
            <?php include 'homepage/devolved-list.php'; ?>    
            </div>
            </div>
        </div>

        <div class="panel panel--flushtop clearfix">
            <div class="row nested-row">
                <div class="homepage-featured-content homepage-content-section">
                    <?php $featured_debate_shown = false; ?>
                    <?php include 'homepage/featured-content.php'; ?>
            </div>
            
            <div class="homepage-create-alert homepage-content-section">
                    <h2>Create an alert</h2>
                    <h3 class="create-alert__heading">Stay informed!</h3>
                    <p>Get an email every time an issue you care about is mentioned in Parliament (and more)</p>
                    <a href="<?= $urls['alert'] ?>" class="button create-alert__button button--violet">Create an alert &rarr;</a>
                </div>
                </div>
        </div>

        <?php if ($featured_debate_shown == false && count($topics) > 0) { ?>
        <div class="panel panel--flushtop clearfix">
            <div class="row nested-row">
                <div class="homepage-featured-content homepage-content-section">
                    <?php if ($featured_debate_shown == false) { ?>
                    <?php if (count($featured) > 0) {
                        include 'homepage/featured.php';
                    } else { ?>
                        No debates found.
                    <?php } ?>
                    <?php } ?>
                    <?php if (count($topics) > 0) { ?>
                        <h2>Topics in the news</h2>
                        <ul class="inline-list">
                        <?php foreach ($topics as $topic) { ?>
                            <li><a href="<?= $topic->url() ?>" class="button tertiary"><?= $topic->title() ?></a></li>
                        <?php } ?>
                        </ul>
                    <?php } ?>
                </div>
                
            </div>
        </div>
        <?php } ?>

        <div class="panel panel--flushtop clearfix">
            <div class="row nested-row">
                <div class="homepage-recently homepage-content-section">
                    <?php include 'homepage/recent-votes.php'; ?>

                    <h2>Recently in Parliament</h2>
                    <ul class="recently__list"><?php
                        foreach ($debates['recent'] as $recent) {
                            include 'homepage/recent-debates.php';
                        }
?></ul>
                </div>
                <div class="homepage-upcoming homepage-content-section">
                    <h2>Upcoming</h2>
                    <?php if (count($calendar)) { ?>
                    <div class="upcoming__controls">
                        <!--
                            These controls should make the upcoming section slide to the next day.
                            We should have a weeks' work of upcoming content, before we show a message that links to upcoming
                        -->
                        <div class="row nested">
                            <div class="small-2 columns">
                                <a href="#" class="controls__prev">&larr;</a>
                            </div>
                            <div class="small-8 columns controls__current">
                                <a href="/calendar/?d=<?= array_keys($calendar)[0] ?>"><?= format_date(array_keys($calendar)[0], SHORTDATEFORMAT); ?></a>
                            </div>
                            <div class="small-2 columns">
                                <a href="#" class="controls__next">&rarr;</a>
                            </div>
                        </div>
                    </div>
                    <?php $first = true;
                        $count = 0;
                        foreach ($calendar as $date => $places) {
                            $count++; ?>
                            <div class="cal-wrapper <?= $first ? 'visible' : 'hidden' ?>" id="day-<?= $count ?>" data-count="<?= $count ?>" data-date="<?= format_date($date, SHORTDATEFORMAT); ?>">
                            <?php foreach ($places as $place => $events) { ?>
                                <?php $first = false; ?>
                                <h3><?= $place ?></h3>
                                <ul class="upcoming__list">
                                    <?php for ($i = 0; $i < 3; $i++) {
                                        if (isset($events[$i])) {
                                            [$event_title, $meta_items] = MySociety\TheyWorkForYou\Utility\Calendar::meta($events[$i]);
                                            ?>
                                    <li>
                                        <h4 class="upcoming__title"><a href="<?= $events[$i]['link_calendar'] ?>"><?= $event_title ?></a></h4>
                                        <p class="meta"><?= implode('; ', array_filter($meta_items)) ?></p>
                                    </li>
                                    <?php } ?>
                                    <?php } ?>
                                </ul>
                                <?php if (count($events) - 3 > 0) { ?>
                                <a href="/calendar/?d=<?= format_date($date, '%Y-%m-%d') ?>" class="upcoming__more">And <?= count($events) - 3 ?> more</a><!-- (just links to relevant upcoming page) -->
                                <?php } ?>
                        <?php } ?>
                            </div>
                    <?php } ?>
                    <?php } else {
                        [$recess, $from, $to] = recess_prettify(date('j'), date('n'), date('Y'), 1);
                        if ($recess) { ?>
                            <p>
                            Parliament is on holiday until <?= format_date($to, LONGERDATEFORMAT) ?>.
                            Follow us on <a href="https://twitter.com/theyworkforyou">Twitter</a> and you'll
                            be the first to know when they're back in session.
                            </p>
                        <?php } else { ?>
                            <p>
                            We don&rsquo;t have details of any upcoming events.
                            </p>
                        <?php }
                        } ?>
                </div>
            </div>
        </div>
    </div>
</div>
