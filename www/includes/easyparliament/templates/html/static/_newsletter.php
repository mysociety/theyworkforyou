<h1 aria-label="<?= gettext('About mySociety') ?>">
    <?= gettext('About') ?>
    <br>
    <img src="/style/img/logo-mysociety-black.svg" alt="" width="225">
</h1>
<h3><?= gettext('Sign up for updates on our democracy and Parliaments work')?></h3>
<form method="post" class="footer__newsletter-form" action="//mysociety.us9.list-manage.com/subscribe/post?u=53d0d2026dea615ed488a8834&amp;id=287dc28511" onsubmit="trackFormSubmit(this, 'FooterNewsletterSignup', 'submit', null); return false;">
    <div class="row collapse">
        <div class="small-8 columns">
            <input type="email" placeholder="Your email address" name="EMAIL"/>
        </div>
        <div class="small-4 columns">
            <label style="position: absolute; left: -5000px;">
                Leave this box empty: <input type="text" name="b_53d0d2026dea615ed488a8834_287dc28511" tabindex="-1" value="" />
            </label>
            <input type="hidden" name="group[11745][4]" value="1">
            <input type="submit" value="Subscribe" name="subscribe" class="button prefix">
        </div>
    </div>
    <div class="row collapse">
        <div class="small-12 columns">
            <label>
                <input type="checkbox" name="group[11745][32]" value="1" style="height: 1em;" checked>
            Also sign up to the monthly mySociety newsletter
            </label>
        </div>
    </div>
</form>
<hr>
<p><?= gettext('TheyWorkForYou is run by <a href="https://www.mysociety.org/">mySociety</a>, a UK charity that helps people access information and participate in democracy. We enable people across the UK to become changemakers by providing technology, research and data, openly and for free.') ?></p>
<p><?= gettext('Through <strong>TheyWorkForYou</strong> and <strong>WriteToThem</strong> we have made elected representatives more transparent and contactable. Every year, hundreds of thousands of reports are made through <strong>FixMyStreet</strong>, and over a million Freedom of Information requests have been made through <strong>WhatDoTheyKnow</strong>.') ?></p>

<p><a href="https://www.mysociety.org/about/funding/"><?= gettext('Find out more about how mySociety is funded.') ?></a></p>
