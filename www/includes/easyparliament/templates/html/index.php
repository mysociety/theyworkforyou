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
