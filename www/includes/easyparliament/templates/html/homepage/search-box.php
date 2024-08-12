<div class="panel panel--primary  <?= $search_box->homepage_panel_class ?>">
    <div class ="row nested-row">
        <div class="home__search">
            <div>
                <h1 class="site-home__logo">TheyWorkFor<strong>You</strong></h1>
            </div>
            <?php if ($search_box->homepage_subhead) { ?>
            <div>
                <h2 class="site-home__logo" style="color:#fff"><?= $search_box->homepage_subhead ?></h2>
            </div>
            <?php } ?>
            <?php if ($search_box->homepage_desc) { ?>
            <p><?= $search_box->homepage_desc ?></p>
            <?php } ?>
        </div>
    </div>
    <div class="row nested-row ">
        <div class="home__search">
            <form action="<?= $urls['search'] ?>" method="GET" onsubmit="trackFormSubmit(this, 'Search', 'Submit', 'Home'); return false;">
            <?php if ($search_box->search_section) { ?>
            <input type="hidden" name="section" value="<?= $search_box->search_section ?>">  
            <?php } ?>  
            <div class="row collapse">
                    <div class="medium-9 columns">
                        <input name="q" id="q" class="homepage-search__input" type="text" placeholder="Enter your postcode, person, or topic" />
                    </div>
                    <div class="medium-3 columns">
                        <input type="submit" value="Search" class="button homepage-search__button" />
                    </div>
                </div>
            </form>
        </div>

</div>
<div class="home__search-suggestions">
    <?php if (count($popular_searches)) { ?>
    <p>Popular searches today</p>
    <ul class="search-suggestions__list">
        <?php foreach ($popular_searches as $i => $popular_search) { ?>
        <li><?= $popular_search['display']; ?></li>
        <?php } ?>
    </ul>
    <?php } ?>
</div>
<div class="home__quick-links">
<h3>Quick links</h3>
    <ul class="quick-links__list">
        <?php foreach ($search_box->quick_links as $search_box->quick_link) { ?>
        <li><div class="quick-links__item"><a href="<?= $search_box->quick_link['url'] ?>"><?= $search_box->quick_link['title'] ?></a></div></li>
        <?php } ?>
    </ul>

</div>
<div>
    <p style="margin-bottom:0px">Built by
    <a href="https://www.mysociety.org?utm_source=theyworkforyou.com&amp;utm_medium=link&amp;utm_campaign=twfy_search_box" class="mysoc__org__logo mysoc__org__logo--mysociety">mySociety</a>
    </p>    
</div>
</div>