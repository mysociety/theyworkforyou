<?php
include_once INCLUDESPATH . "easyparliament/templates/html/mp/header.php";
?>

<div class="full-page">
    <div class="full-page__row">
        <div class="full-page__unit">
            <div class="person-navigation page-content__row">
                <ul>
                    <li><a href="<?= $member_url ?>">Overview</a></li>
                    <li class="active"><a href="<?= $member_url ?>/votes">Voting Record</a></li>
                </ul>
            </div>
            <div class="person-panels page-content__row">
                <div class="sidebar__unit in-page-nav">
                    <ul data-magellan-expedition="fixed">
                        <?php if ($has_voting_record): ?>
                        <?php foreach ($key_votes_segments as $segment): ?>
                        <?php if (count($segment['votes']->positions) > 0): ?>
                        <li data-magellan-arrival="<?= $segment['key'] ?>"><a href="#<?= $segment['key'] ?>"><?= $segment['title'] ?></a></li>
                        <?php endif; ?>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                    <div>&nbsp;</div>
                </div>
                <div class="primary-content__unit">

                    <?php if ($party == 'Sinn Fein' && in_array(HOUSE_TYPE_COMMONS, $houses)): ?>
                    <div class="panel">
                        <p>Sinn F&eacute;in MPs do not take their seats in Parliament.</p>
                    </div>
                    <?php endif; ?>

                    <?php if ($has_voting_record): ?>

                        <?php $displayed_votes = FALSE; ?>

                        <?php foreach ($key_votes_segments as $segment): ?>

                            <?php if (count($segment['votes']->positions) > 0): ?>

                                <div class="panel">

                                <h2 id="<?= $segment['key'] ?>" data-magellan-destination="<?= $segment['key'] ?>">
                                    How <?= $full_name ?> voted on <?= $segment['title'] ?>
                                    <small><a class="nav-anchor" href="<?= $member_url ?>/votes#<?= $segment['key'] ?>">#</a></small>
                                </h2>

                                <ul class="vote-descriptions">
                                  <?php foreach ($segment['votes']->positions as $key_vote): ?>
                                    <li>
                                        <?= $key_vote['desc'] ?>
                                        <a class="vote-description__source" href="http://www.publicwhip.org.uk/mp.php?mpid=<?= $member_id ?>&dmp=<?= $key_vote['policy_id'] ?>">Source</a>
                                    </li>
                                  <?php endforeach; ?>
                                </ul>

                                </div>

                                <?php $displayed_votes = TRUE; ?>

                            <?php endif; ?>

                        <?php endforeach; ?>

                        <?php if ($displayed_votes): ?>

                            <?php if (isset($segment['votes']->moreLinksString)): ?>

                                <div class="panel">
                                    <p><?= $segment['votes']->moreLinksString ?></p>
                                </div>

                            <?php endif; ?>

                        <?php else: ?>

                            <div class="panel">
                                <p>This person has not voted on any of the key issues which we keep track of.</p>
                            </div>

                        <?php endif; ?>

                    <?php endif; ?>

                    <div class="panel">
                        <p>Please feel free to use the data on this page, but if
                            you do you must cite TheyWorkForYou.com in the body
                            of your articles as the source of any analysis or
                            data you get off this site.</p>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
