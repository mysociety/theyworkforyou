    <div class="hero">
        <div class="row">
        <div class="hero__mp-search">
            <div class="hero__mp-search__wrap">
                <h1>Find out more about your AMs</h1>
                <div class="row collapse">
                    <?php if ( count($data['regional']) > 0 ) { ?>
                        <ul class="homepage-rep-list">
                            <li>Your Assembly Memberss: </li>
                        <?php foreach ( $data['regional'] as $member ) { ?>
                            <li class="homepage-rep-list__rep"><a href="/london-assembly-member/?p=<?= $member['person_id'] ?>"><?= $member['name'] ?></a></li>
                        <?php } ?>
                        </ul>
                    <?php } else { ?>
                    <form action="/postcode/" class="mp-search__form"  onsubmit="trackFormSubmit(this, 'PostcodeSearch', 'Submit', 'LondonHome'); return false;">
                        <label for="postcode">Your London postcode</label>
                        <div class="medium-9 columns">
                            <input id="postcode" name="pc" class="homepage-search__input" type="text" placeholder="TF1 8GH" />
                        </div>
                        <div class="medium-3 columns">
                            <input type="submit" value="Find out &rarr;" class="button homepage-search__button" />
                        </div>
                    </form>
                    <?php } ?>
                </div>
            </div>
        </div>
        <div class="hero__site-intro">
            <div class="hero__site-intro__wrap">
                <h2>Democracy: it&rsquo;s for everyone</h2>
                <p>You shouldn&rsquo;t have to be an expert to understand what goes on in the London Assembly. </p>
                <p>TheyWorkForYou takes open data from the London Assembly, and presents it in a way that&rsquo;s easy to follow &ndash; for everyone.</p>
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
                        <?php if ( $featured ) {
                             include dirname(__FILE__) . "/../homepage/featured.php";
                        } ?>
                    </div>
                    <div class="homepage-create-alert homepage-content-section">
                        <h2>Create an alert</h2>
                        <h3 class="create-alert__heading">Stay informed!</h3>
                        <p>Get an email every time an issue you care about is mentioned in questions to the Mayor of London (and more)</p>
                        <a href="<?= $urls['alert'] ?>" class="button create-alert__button button--violet">Create an alert &rarr;</a>
                    </div>
                </div>
            </div>
            <div class="panel panel--inverted">
                <div class="row nested-row">
                    <div class="home__search">
                        <form action="<?= $urls['search'] ?>" method="GET"onsubmit="trackFormSubmit(this, 'Search', 'Submit', 'Home'); return false;">
                        <input type="hidden" name="section" value="lmq">
                            <label for="q">Search questions to the Mayor of London</label>
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
                        <h2>Recently answered questions to the Mayor of London</h2>
                        <ul class="recently__list"><?php
                            foreach ( $debates['recent'] as $recent ) {
                                include dirname(__FILE__) . '/../homepage/recent-debates.php';
                            }
                        ?></ul>
                    </div>
                    <div class="homepage-upcoming homepage-content-section">
                        <h2>What is the London Assembly?</h2>

                        <p>The London Assembly is a 25-member elected body, part of the Greater London Authority, that scrutinises the activities of the Mayor of London.</p>

                        <p>The London Assembly was established in 2000 and meets at City Hall on the south bank of the River Thames, close to Tower Bridge. The Assembly is also able to investigate other issues of importance to Londoners (transport, environmental matters, etc.), publish its findings and recommendations, and make proposals to the Mayor.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
