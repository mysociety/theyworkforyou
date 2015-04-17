    <div class="hero">
        <div class="row">
        <div class="hero__mp-search">
            <div class="hero__mp-search__wrap">
                <?php if (count($mp_data)) { ?>
                <h1>Does <?= $mp_data['name'] ?>, your <?= $mp_data['former'] ?> MP represent you?</h1>
                <div class="row collapse">
                    <div class="medium-8 columns">&nbsp;</div>
                    <div class="medium-3 columns">
                        <a href="<?= $mp_data['mp_url']?>" class="button homepage-search__button" />Find out &rarr;</a>
                    </div>
                    <div class="medium-1 columns">&nbsp;</div>
                </div>
                <div class="row">
                    <div class="medium-9 columns">
                        <a href="<?= $mp_data['change_url'] ?>"><?= $mp_data['postcode'] ?> not your postcode?</a>
                    </div>
                </div>
                <?php } else { ?>
                <h1>Does your MP represent you?</h1>
                <div class="row collapse">
                    <form action="/postcode/" class="mp-search__form">
                        <label>Your postcode</label>
                        <div class="medium-9 columns">
                            <input id="postcode" class="homepage-search__input" type="text" placeholder="TF1 8GH" />
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
                <h2>Democracy: it's for everyone</h2>
                <p>You shouldn't have to be an expert to understand what goes on in Parliament. Your politicians represent you&hellip; but what exactly do they do in your name?</p>
                <p>TheyWorkForYou takes open data from the UK Parliament, and presents it in a way that's easy to follow &ndash; for everyone. So now you can check, with just a few clicks: are They Working For You?</p>
                <a href="#" class="site-intro__more-link">Find out more about TheyWorkForYou <i>&rarr;</i></a>
            </div>
        </div>
        </div>
    </div>
    <div class="full-page__row">
        <div class="homepage-panels">
            <div class="panel panel--flushtop clearfix">
                <div class="row nested-row">
                    <div class="homepage-in-the-news homepage-content-section">
                        <?php include 'homepage/featured.php' ?>
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
                        <form action="<?= $urls['search'] ?>" method="GET"onsubmit="trackFormSubmit(this, 'Search', 'Submit', 'Home'); return false;">
                            <label>Search debates, written questions and hansard</label>
                            <div class="row collapse">
                                <div class="medium-9 columns">
                                    <input name="q" id="postcode" class="homepage-search__input" type="text" placeholder="Enter a keyword, phrase, or person" />
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
                            <?php for ( $i = 0; $i < 3; $i++ ) {
                                $recent = $debates['recent'][$i];
                                include 'homepage/recent-debates.php';
                            } ?>
                        </ul>
                        <ul class="recently__list recently__list-more">
                            <?php for ( $i = 3; $i < count($debates['recent']); $i++ ) {
                                $recent = $debates['recent'][$i];
                                include 'recent-debates.html';
                            } ?>
                        </ul>
                        <a href="#" class="button button--show-all button--full-width" onclick="$('.recently__list-more').show(); return false;">Show more</a>
                    </div>
                    <div class="homepage-upcoming homepage-content-section">
                        <h2>Upcoming</h2>
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
                                    <a href="#"><?= format_date(array_keys($calendar)[0], SHORTDATEFORMAT); ?></a>
                                </div>
                                <div class="small-2 columns">
                                    <a href="#" class="controls__next">&rarr;</a>
                                </div>
                            </div>
                        </div>
                        <?php $first = true; $count = 0;
                            foreach ( $calendar as $date => $events ) {
                            $count++;
                            $commons = $events['Commons: Main Chamber']; ?>
                            <div class="cal-wrapper <?= $first ? 'visible' : 'hidden' ?>" id="day-<?= $count ?>" data-count="<?= $count ?>" data-date="<?= format_date($date, SHORTDATEFORMAT); ?>">
                        <?php $first = false; ?>
                        <h3>Commons: Main Chamber</h3>
                        <ul class="upcoming__list">
                            <?php for ( $i = 0; $i < 3; $i++ ) {
                                if ( isset( $commons[$i] ) ) { ?>
                            <li>
                                <h4 class="upcoming__title"><a href="<?= $commons[$i]['link_external'] ?>"><?= $commons[$i]['title'] ?></a></h4>
                                <p class="meta"><?= $commons[$i]['debate_type'] ?><?= $commons[$i]['time_start'] != '00:00:00' ? ';' . $commons[$i]['time_start'] : '' ?></p>
                            </li>
                            <?php } ?>
                            <?php } ?>
                        </ul>
                        <?php if ( count($commons) - 3 > 0 ) { ?>
                        <a href="#" class="upcoming__more">And <?= count($commons) - 3 ?> more</a><!-- (just links to relevant upcoming page) -->
                        <?php } ?>
                        </div>
                        <?php } ?>
                    </div>
                    <script>
                        $(function setupCalendar() {
                            $('.controls__prev').on('click', function prevDay() {
                                swapCalendar(-1);
                                return false;
                            });
                            $('.controls__next').on('click', function nextDay() {
                                swapCalendar(1);
                                return false;
                            });
                            function swapCalendar(direction) {
                                var current = $('.cal-wrapper.visible');
                                var num = parseInt(current.attr('data-count'), 10);
                                var new_num = num + direction;
                                var next = $('#day-' + new_num);
                                if ( next.length ) {
                                    var date = next.attr('data-date');
                                    current.addClass('hidden').removeClass('visible');
                                    next.addClass('visible').removeClass('hidden');
                                    $('.controls__current a').text(date);
                                }
                            }
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>
