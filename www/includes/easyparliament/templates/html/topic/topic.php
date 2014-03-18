<div class="topic-header">
    <div class="full-page">
        <div class="full-page__row">
            <div class="topic-header__content page-content__row">
                <div class="topic-name">
                    <h1><?= $title ?></h1>
                    <h1 class="subheader">&amp; the UK Parliament</h1>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="full-page">
    <div class="full-page__row">
        <div class="full-page__unit">
            <div class="topic-panels">

                <p class="lead"><?= $blurb ?></p>

                <p class="lead">Here are some places you might want to start:</p>

                <ul class="small-block-grid-2">

                <?php foreach ($actions as $action): ?>

                    <li>

                        <div class="panel">

                            <h3><a href="<?= $action['href'] ?>"><?= $action['title'] ?></a></h3>

                            <p><?= $action['blurb'] ?></p>

                        </div>

                    </li>

                <?php endforeach; ?>

                </ul>

                <?php if (isset($policytitle)): ?>

                    <hr>

                    <h2 id="myrep">How Your MP voted on <?= $policytitle ?></h2>

                    <?php if ($display_postcode_form): ?>

                        <p>Find our how your representative has voted on key issues around <?= $policytitle ?>.</p>

                        <form action="#myrep" method="get">
                            <p><strong>Enter your UK postcode: </strong>

                                <input type="text" name="pc" value="" maxlength="10" size="10"> <input type="submit" value="GO" class="submit"> <small>(e.g. BS3 1QP)</small>
                            </p>
                        </form>

                    <?php endif; ?>

                    <?php if (isset($member_name)): ?>

                        <?php if (count($positions) > 0): ?>

                        <p>Here&rsquo;s how <a href="<?= $member_url ?>"><?= $member_name ?></a> voted on <?= $policytitle ?><?= $sinceString ?>. <a href="<?= $member_url ?>/votes">See all their votes</a>.</p>

                        <div class="panel policies">

                            <ul>

                                <?php foreach ($positions as $position): ?>

                                <li><?= $position['desc'] ?><a class="dream_details" href="http://www.publicwhip.org.uk/mp.php?mpid=<?= $member_id ?>&dmp=<?= $position['policy_id'] ?>">Details</a></li>

                                <?php endforeach; ?>

                            </ul>

                        </div>

                        <?php else: ?>

                        <p><a href="<?= $member_url ?>"><?= $member_name ?></a> hasn't voted on any of the key issues on <?= $policytitle ?>. You may want to <a href="<?= $member_url ?>/votes">see all their votes</a>.</p>

                        <?php endif; ?>

                    <?php endif; ?>

                <?php endif; ?>

            </div>
        </div>
    </div>
</div>
