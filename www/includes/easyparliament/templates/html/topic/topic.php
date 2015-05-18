<div class="topic-header">
    <div class="full-page">
        <div class="full-page__row">

            <div class="topic-name">
                <h1><?= $title ?></h1>
                <h1 class="subheader">&amp; the UK Parliament</h1>
                <p class="lead"><?= $blurb ?> Here are some places you might want to start.</p>
            </div>

          <?php if (isset($policytitle) AND $display_postcode_form): ?>
            <div class="topic-postcode-search">
                <h3>What does your MP think?</h3>

                <form action="#yourrep" method="get">
                    <div class="row collapse">
                        <div class="small-10 columns">
                            <input type="text" name="pc" value="" maxlength="10" size="10" placeholder="Your postcode">
                        </div>
                        <div class="small-2 columns">
                            <input type="submit" value="GO" class="button prefix">
                        </div>
                    </div>
                </form>
            </div>
          <?php endif; ?>

        </div>
    </div>
</div>

<div class="full-page">
    <div class="full-page__row">
        <div class="topic-content">

            <div class="topic-block">


                <ul class="large-block-grid-2">

                <?php foreach ($actions as $action): ?>

                    <li>

                        <div class="panel">

                            <div class="row">

                                <div class="medium-3 columns show-for-medium-up">

                                    <img src="/images/icons/topic-<?= $action['icon'] ?>.png">

                                </div>

                                <div class="medium-9 columns">

                                    <h3><a href="<?= $action['href'] ?>"><?= $action['title'] ?></a></h3>

                                    <p><?= $action['blurb'] ?></p>

                                </div>

                            </div>

                        </div>

                    </li>

                <?php endforeach; ?>

                </ul>

            </div>

            <?php if (isset($policytitle)): ?>

                <div class="topic-block policies">

                    <div class="row">

                        <div class="medium-8 columns unpad-left">

                            <h2 id="yourrep">Your Representative</h2>

                            <?php if ($display_postcode_form): ?>

                            <p>Find our how your representative has voted on key issues around <?= $policytitle ?>.</p>

                        </div>

                        <div class="medium-4 columns topic-postcode-search">

                            <h3>What does your MP think?</h3>

                            <form action="#yourrep" method="get">

                                <div class="row collapse">
                                    <div class="small-10 columns">
                                        <input type="text" name="pc" value="" maxlength="10" size="10" placeholder="Your postcode">
                                    </div>
                                    <div class="small-2 columns">
                                        <input type="submit" value="GO" class="button prefix">
                                    </div>
                                </div>
                            </form>

                        </div>

                            <?php endif; ?>

                            <?php if (isset($member_name)): ?>

                            <?php if (count($positions) > 0): ?>

                            <p>How they voted on <?= $policytitle ?><?= $sinceString ?>.</p>

                            <?php else: ?>

                            <p><a href="<?= $member_url ?>"><?= $member_name ?></a> hasn't voted on any of the key issues on <?= $policytitle ?>. You may want to <a href="<?= $member_url ?>/votes">see all their votes</a>.</p>

                            <?php endif; ?>

                        </div>

                        <div class="medium-4 columns topic-mp-info show-for-medium-up">
                            <div class="row">
                                <div class="small-3 columns">
                                    <img src="<?= $member_image['url'] ?>">
                                </div>
                                <div class="small-9 columns">
                                    <h3><?= $member_name ?></h3>
                                    <p><?= $member_constituency ?></p>
                                </div>
                            </div>
                        </div>

                    </div>

                            <?php if (count($positions) > 0): ?>

                            <ul class="policies-list">

                                <?php foreach ($positions as $position): ?>

                                <li><?= $position['desc'] ?><a class="dream_details" href="http://www.publicwhip.org.uk/mp.php?mpid=<?= $member_id ?>&dmp=<?= $position['policy_id'] ?>">Details</a></li>

                                <?php endforeach; ?>

                            </ul>

                            <?php endif; ?>

                            <?php endif; ?>

                    </div>

                </div>

            <?php endif; ?>

        </div>
    </div>
</div>
