    <div class="hero">
        <div class="row">
        <div class="hero__mp-search">
            <div class="hero__mp-search__wrap">
                <?php if (count($mp_data)) { ?>
                <h1>Does <?= $mp_data['former'] ? 'your former MSP ' : '' ?><?= $mp_data['name'] ?> represent you?</h1>
                <div class="row collapse">
                    <div class="medium-4 columns">
                        <a href="<?= $mp_data['mp_url']?>" class="button homepage-search__button" />Find out &rarr;</a>
                    </div>
                </div>
                <div class="row">
                    <div class="medium-9 columns">
                    <?php if ( count($data['regional']) > 0 ) { ?>
                        <ul class="homepage-rep-list">
                        <li>Your Regional MSPs:</li>
                        <?php foreach ( $data['regional'] as $msp ) { ?>
                            <li class="homepage-rep-list__rep"><a href="/msp/?p=<?= $msp['person_id'] ?>"><?= $msp['name'] ?></a></li>
                        <?php } ?>
                        </ul>
                    <?php } ?>
                    </div>
                </div>
                <div class="row">
                    <div class="medium-9 columns">
                        <a class="homepage-search__change-postcode" href="<?= $mp_data['change_url'] ?>">Change postcode</a>
                    </div>
                </div>
                <?php } else { ?>
                <h1>Do your MSPs represent you?</h1>
                <div class="row collapse">
                    <form action="/postcode/" class="mp-search__form">
                        <label for="postcode">Your Scottish postcode</label>
                        <div class="medium-9 columns">
                            <input id="postcode" name="pc" class="homepage-search__input" type="text" placeholder="TF1 8GH" />
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
                <p>You shouldn&rsquo;t have to be an expert to understand what goes on in the Scottish Parliament. Your politicians represent you&hellip; but what exactly do they do in your name?</p>
                <p>TheyWorkForYou takes open data from the Scottish Parliament, and presents it in a way that&rsquo;s easy to follow &ndash; for everyone. So now you can check, with just a few clicks: are They Working For You?</p>
                <a href="/about/" class="site-intro__more-link">Find out more about TheyWorkForYou <i>&rarr;</i></a>
            </div>
        </div>
        </div>
    </div>
    <div class="full-page__row">
        <div class="homepage-panels">
            <div class="panel panel--flushtop clearfix">
                <div class="row nested-row">
                    <div class="homepage-content-section">
                        <h2>&nbsp;</h2>
                        <p>
                            Due to changes made to the official Scottish Parliament, our parser that used to fetch their web pages and convert them into more structured
                            information has stopped working. We&rsquo;re afraid we cannot give a timescale as to when we will be able to cover the Scottish Parliament again.
                            Sorry for any inconvenience caused.
                        </p>
                    </div>
                </div>
            </div>
            <div class="panel panel--flushtop clearfix">
                <div class="row nested-row">
                    <div class="homepage-in-the-news homepage-content-section">
                        <?php if ( $featured ) {
                             include dirname(__FILE__) . "/../homepage/featured.php";
                        } ?>
                    </div>
                    <div class="homepage-create-alert homepage-content-section">
                        <h2>Create an alert</h2>
                        <h3 class="create-alert__heading">Stay informed!</h3>
                        <p>Get an email every time an issue you care about is mentioned in the Scottish Parliament (and more)</p>
                        <a href="<?= $urls['alert'] ?>" class="button create-alert__button button--violet">Create an alert &rarr;</a>
                    </div>
                </div>
            </div>
            <div class="panel panel--inverted">
                <div class="row nested-row">
                    <div class="home__search">
                        <form action="<?= $urls['search'] ?>" method="GET"onsubmit="trackFormSubmit(this, 'Search', 'Submit', 'Home'); return false;">
                        <input type="hidden" name="section" value="scotland">
                            <label for="q">Search debates and written questions</label>
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
                                include dirname(__FILE__) . '/../homepage/recent-debates.php';
                            } ?>
                        </ul>
                        <?php if ( $max_count >= 3 ) { ?>
                        <ul class="recently__list recently__list-more">
                            <?php for ( $i = 3; $i < count($debates['recent']); $i++ ) {
                                $recent = $debates['recent'][$i];
                                include dirname(__FILE__) . '/../homepage/recent-debates.php';
                            } ?>
                        </ul>
                        <a href="#" class="button button--show-all button--full-width" onclick="$('.recently__list-more').show(); return false;">Show more</a>
                        <?php } ?>
                    </div>
                    <div class="homepage-upcoming homepage-content-section">
                        <h2>What is the Scottish Parliament?</h2>
                        <p>The Scottish Parliament is the national legislature of Scotland, located in
                        Edinburgh. The Parliament consists of 129 members known as <a
                        href="/msps/">MSPs</a>, elected for four-year terms; 73 represent
                        individual geographical constituencies, and 56 are regional representatives.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
