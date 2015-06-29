<div class="full-page">
    <div class="full-page__row">
        <div class="person-panels">
            <div class="primary-content__unit">

                <h1><?= $party ?></h1>

                <?php if (($party == 'Sinn Fein' || $party == utf8_decode('Sinn Féin')) && in_array(HOUSE_TYPE_COMMONS, $houses)): ?>
                <div class="panel">
                    <p>Sinn F&eacute;in MPs do not take their seats in Parliament.</p>
                </div>
                <?php endif; ?>

                <div class="panel">
                    <a name="votes"></a>

                        <h2><?= $policy['title'] ?></h2>

                        <h3>Party position is <?= $position ?></h3>

                        <ul class="vote-descriptions">
                          <?php foreach ($member_votes as $member): ?>
                            <li>
                                <?= $member['details']->full_name() ?> : <?= $member['position'] ?>
                                <a class="vote-description__source" href="<?= $member['details']->url() ?>/divisions?policy=<?= $policy['policy_id'] ?>">Details</a>
                            </li>
                          <?php endforeach; ?>
                        </ul>

                </div>

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
