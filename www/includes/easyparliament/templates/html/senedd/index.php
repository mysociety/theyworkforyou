    <div class="hero">
        <div class="row">
        <div class="hero__mp-search">
            <div class="hero__mp-search__wrap">
                <h1><?= gettext('Find out more about your MSs') ?></h1>
                <div class="row collapse">
                    <?php if ( count($data['regional']) > 0 ) { ?>
                        <ul class="homepage-rep-list">
                            <li><?= gettext('Your MSs:') ?></li>
                        <?php foreach ( $data['regional'] as $ms ) { ?>
                            <li class="homepage-rep-list__rep"><a href="/ms/?p=<?= $ms['person_id'] ?>"><?= $ms['name'] ?></a></li>
                        <?php } ?>
                        </ul>
                    <?php } else { ?>
                    <form action="/postcode/" class="mp-search__form"  onsubmit="trackFormSubmit(this, 'PostcodeSearch', 'Submit', 'WalesHome'); return false;">
                        <label for="postcode"><?= gettext('Your Welsh postcode') ?></label>
                        <div class="medium-9 columns">
                            <input id="postcode" name="pc" class="homepage-search__input" type="text" placeholder="SY23 4AD" />
                        </div>
                        <div class="medium-3 columns">
                            <input type="submit" value="<?= gettext('Find out') ?> &rarr;" class="button homepage-search__button" />
                        </div>
                    </form>
                    <?php } ?>
                </div>
            </div>
        </div>
        <div class="hero__site-intro">
            <div class="hero__site-intro__wrap">
                <h2><?= gettext('Democracy: it’s for everyone') ?></h2>
                <p><?= gettext('You shouldn’t have to be an expert to understand what goes on in the Senedd.') ?></p>
                <p><?= gettext('TheyWorkForYou takes open data from the Senedd, and presents it in a way that’s easy to follow – for everyone.') ?></p>
                <a href="/about/" class="site-intro__more-link"><?= gettext('About TheyWorkForYou') ?><i>&rarr;</i></a>
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
                        <h2><?= gettext('Create an alert') ?></h2>
                        <h3 class="create-alert__heading"><?= gettext('Stay informed!') ?></h3>
                        <p><?= gettext('Get an email every time an issue you care about is mentioned in the Senedd (and more)') ?></p>
                        <a href="<?= $urls['alert'] ?>" class="button create-alert__button button--violet"><?= gettext('Create an alert') ?> &rarr;</a>
                    </div>
                </div>
            </div>
            <div class="panel panel--inverted">
                <div class="row nested-row">
                    <div class="home__search">
                        <form action="<?= $urls['search'] ?>" method="GET"onsubmit="trackFormSubmit(this, 'Search', 'Submit', 'Home'); return false;">
                        <input type="hidden" name="section" value="ni">
                            <label for="q"><?= gettext('Search debates') ?></label>
                            <div class="row collapse">
                                <div class="medium-9 columns">
                                    <input name="q" id="q" class="homepage-search__input" type="text" placeholder="<?= gettext('Enter a keyword, phrase, or person') ?>" />
                                </div>
                                <div class="medium-3 columns">
                                    <input type="submit" value="<?= gettext('Search') ?>" class="button homepage-search__button" />
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="home__search-suggestions">
                        <?php if (count($popular_searches)) { ?>
                        <h3><?= gettext('Popular searches today') ?></h3>
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
                        <h2><?= gettext('Recently in the Senedd') ?></h2>
                        <ul class="recently__list"><?php
                            foreach ( $debates['recent'] as $recent ) {
                                include dirname(__FILE__) . '/../homepage/recent-debates.php';
                            }
                        ?></ul>
                    </div>
                    <div class="homepage-upcoming homepage-content-section">
                        <h2><?= gettext('What is the Senedd?') ?></h2>
                        <?php include dirname(__FILE__) . '/../section/_senedd_desc.php'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
