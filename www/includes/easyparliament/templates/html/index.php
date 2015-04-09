    <div class="hero">
        <div class="row">
        <div class="hero__mp-search">
            <div class="hero__mp-search__wrap">
                <?php if (count($mp_data)) { ?>
                <h1>Does <?= $mp_data['former'] ? 'your former MP ' : '' ?><?= $mp_data['name'] ?> represent you?</h1>
                <div class="row collapse">
                    <div class="medium-4 columns">
                        <a href="<?= $mp_data['mp_url']?>" class="button homepage-search__button" />Find out &rarr;</a>
                    </div>
                </div>
                <div class="row">
                    <div class="medium-9 columns">
                        <a class="homepage-search__change-postcode" href="<?= $mp_data['change_url'] ?>">Change postcode</a>
                    </div>
                </div>
                <?php } else { ?>
                <h1>Does your MP represent you?</h1>
                <div class="row collapse">
                    <form action="/postcode/" class="mp-search__form">
                        <label for="postcode">Your postcode</label>
                        <div class="medium-9 columns">
                            <input name="pc" id="postcode" class="homepage-search__input" type="text" placeholder="TF1 8GH" />
                        </div>
                        <div class="medium-3 columns">
                            <input type="submit" value="Find out &rarr;" class="button homepage-search__button" />
                        </div>
                    </form>
                </div>
                <?php } ?>
            </div>
        </div>
        <div class="hero__site-intro">
            <div class="hero__site-intro__wrap">
                <h2>Democracy: it&rsquo;s for everyone</h2>
                <p>You shouldn&rsquo;t have to be an expert to understand what goes on in Parliament. Your politicians represent you&hellip; but what exactly do they do in your name?</p>
                <p>TheyWorkForYou takes open data from the UK Parliament, and presents it in a way that&rsquo;s easy to follow &ndash; for everyone. So now you can check, with just a few clicks: are They Working For You?</p>
                <a href="/about/" class="site-intro__more-link">Find out more about TheyWorkForYou <i>&rarr;</i></a>
            </div>
        </div>
        </div>
    </div>
    <div class="full-page__row">
        <div class="homepage-panels">
            <div class="panel panel--flushtop clearfix">
                <div class="row nested-row">
                    <div class="homepage-in-the-news homepage-content-section">
                        <?php if ( count($featured) > 0 ) {
                            include 'homepage/featured.php';
                        } else { ?>
                            No debates found.
                        <?php } ?>
                    </div>
                    <div class="homepage-create-alert homepage-content-section">
                        <h2>Create an alert</h2>
                        <h3 class="create-alert__heading">Stay informed!</h3>
                        <p>Get an email every time an issue you care about is mentioned in Parliament (and more)</p>
                        <a href="<?= $urls['alert'] ?>" class="button create-alert__button button--violet">Create an alert &rarr;</a>
                    </div>
                </div>
            </div>
            <div class="panel panel--inverted">
                <div class="row nested-row">
                    <div class="home__search">
                        <form action="<?= $urls['search'] ?>" method="GET" onsubmit="trackFormSubmit(this, 'Search', 'Submit', 'Home'); return false;">
                            <label for="q">Search debates, written questions and hansard</label>
                            <div class="row collapse">
                                <div class="medium-9 columns">
                                    <input name="q" id="q" class="homepage-search__input" type="text" placeholder="Enter a keyword, phrase, or person" />
                                </div>
                                <div class="medium-3 columns">
                                    <input type="submit" value="Search" class="button homepage-search__button" />
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="home__search-suggestions">
                        <?php if (count($popular_searches)) { ?>
                        <h3>Popular searches today</h3>
                        <ul class="search-suggestions__list">
                            <?php foreach ($popular_searches as $i => $popular_search) { ?>
                            <li><?= $popular_search['display']; ?></li>
                            <?php } ?>
                        </ul>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <div class="panel panel--flushtop clearfix">
                <div class="row nested-row">
                    <div class="homepage-recently homepage-content-section">
                        <h2>Recently in Parliament</h2>
                        <ul class="recently__list">
                            <?php $max_count = count($debates['recent']) > 3 ? 3 : count($debates['recent']);
                            for ( $i = 0; $i < $max_count; $i++ ) {
                                $recent = $debates['recent'][$i];
                                include 'homepage/recent-debates.php';
                            } ?>
                        </ul>
                        <?php if ( $max_count >= 3 ) { ?>
                        <ul class="recently__list recently__list-more">
                            <?php for ( $i = 3; $i < count($debates['recent']); $i++ ) {
                                $recent = $debates['recent'][$i];
                                include 'homepage/recent-debates.php';
                            } ?>
                        </ul>
                        <a href="#" class="button button--show-all button--full-width">Show more</a>
                        <?php } ?>
                    </div>
                    <div class="homepage-upcoming homepage-content-section">
                        <h2>Upcoming</h2>
                        <?php if ( count($calendar) ) { ?>
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
                        <?php $first = true; $count = 0;
                            foreach ( $calendar as $date => $places ) {
                                $count++; ?>
                                <div class="cal-wrapper <?= $first ? 'visible' : 'hidden' ?>" id="day-<?= $count ?>" data-count="<?= $count ?>" data-date="<?= format_date($date, SHORTDATEFORMAT); ?>">
                                <?php foreach ($places as $place => $events) { ?>
                                    <?php $first = false; ?>
                                    <h3><?= $place ?></h3>
                                    <ul class="upcoming__list">
                                        <?php for ( $i = 0; $i < 3; $i++ ) {
                                            if ( isset( $events[$i] ) ) { ?>
                                        <li>
                                            <h4 class="upcoming__title"><a href="<?= $events[$i]['link_external'] ?>"><?= $events[$i]['title'] ?></a></h4>
                                            <p class="meta"><?= $events[$i]['debate_type'] ?>
                                            <?= isset($events[$i]['time_start']) && $events[$i]['time_start'] != '00:00:00' ? '; ' . format_time($events[$i]['time_start'], 'g:i a') : '' ?>
                                            <?= isset($events[$i]['time_end']) && $events[$i]['time_end'] != '00:00:00' ? ' - ' . format_time($events[$i]['time_end'], 'g:i a') : '' ?></p>
                                        </li>
                                        <?php } ?>
                                        <?php } ?>
                                    </ul>
                                    <?php if ( count($events) - 3 > 0 ) { ?>
                                    <a href="#" class="upcoming__more">And <?= count($events) - 3 ?> more</a><!-- (just links to relevant upcoming page) -->
                                    <?php } ?>
                            <?php } ?>
                                </div>
                        <?php } ?>
                        <?php } else { ?>
                        <p>
                        We don&rsquo;t have details of any upcoming events. Perhaps Parliament&rsquo;s on holiday.
                        </p>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
