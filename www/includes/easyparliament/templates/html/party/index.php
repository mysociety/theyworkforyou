<div class="full-page">
    <div class="full-page__row">
        <div class="person-panels">
            <div class="primary-content__unit">

                <h1><?= $party ?></h1>

                <?php if (($party == 'Sinn Fein' || $party == utf8_decode('Sinn Féin'))): ?>
                <div class="panel">
                    <p>Sinn F&eacute;in MPs do not take their seats in Parliament.</p>
                </div>
                <?php else: ?>

                <?php if (count($policies) > 0): ?>
                <div class="panel">
                    <a name="votes"></a>

                        <ul class="vote-descriptions">
                          <?php foreach ($policies as $policy_id => $policy): ?>
                            <li>
                                <?= $policy['desc'] ?> : <?= $policy['position'] ?>
                                <a class="vote-description__source" href="/party/?party=<?= $party ?>&amp;policy=<?= $policy_id ?>">Details</a>
                            </li>
                          <?php endforeach; ?>
                        </ul>

                    <?php else: ?>

                        <p>No policies to display.</p>

                    <?php endif; ?>

                </div>
                <?php endif; ?>

                <div class="about-this-page">
                    <div class="about-this-page__one-of-one">
                        <div class="panel--secondary">
                            <p>Please feel free to use the data on this page, but if
                                you do you must cite TheyWorkForYou.com in the body
                                of your articles as the source of any analysis or
                                data you get off this site.</p>

                            <p>This data was produced by TheyWorkForYou from a variety
                                of sources. Voting information from
                                <a href="http://www.publicwhip.org.uk/">Public Whip</a>.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
