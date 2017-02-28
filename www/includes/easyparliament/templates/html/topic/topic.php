<div class="topic-header">
    <div class="full-page">
        <div class="full-page__row">

            <div class="topic-name">
                <h1><?= $title ?></h1>
                <h1 class="subheader">&amp; the UK Parliament</h1>
                <p class="lead"><?= $description ?> Here are some places you might want to start.</p>
            </div>

          <?php if ($display_postcode_form): ?>
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

            <?php if (count($actions) > 0) { ?>
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

                                    <p><?= $action['description'] ?></p>

                                </div>

                            </div>

                        </div>

                    </li>

                <?php endforeach; ?>

                </ul>

            </div>
            <?php } ?>

            <?php if (isset($positions)): ?>

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

                    <div class="row">
                        <div class="medium-12 columns unpad-left">
                            <?php if ($total_votes == 0): ?>

                            <p><a href="<?= $member_url ?>"><?= $member_name ?></a> hasn't voted on any of the key issues on <?= $title ?>. You may want to <a href="<?= $member_url ?>/votes">see all their votes</a>.</p>
                            <?php else: ?>
                            <ul class="policies-list">
                            <?php $policy_ids = array(); ?>
                            <?php foreach ($positions as $position) { ?>

                                <?php if (!in_array($position['policy_id'], $policy_ids)) { ?>
                                <li><?= ucfirst($position['desc']) ?><a class="dream_details" href="http://www.publicwhip.org.uk/mp.php?mpid=<?= $member_id ?>&dmp=<?= $position['policy_id'] ?>">Show votes</a></li>

                                <?php $policy_ids[] = $position['policy_id']; ?>
                                <?php } ?>

                            <?php } ?>
                            </ul>

                            <?php endif; ?>

                            <?php endif; ?>
                            <?php endif; ?>

                    </div>

                </div>


        </div>
    </div>
</div>
