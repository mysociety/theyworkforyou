<?php
include_once INCLUDESPATH . "easyparliament/templates/html/mp/header.php";
?>

<div class="full-page">
    <div class="full-page__row">
        <div class="full-page__unit">
            <div class="person-navigation">
                <ul>
                    <li><a href="<?= $member_url ?>">Overview</a></li>
                    <li class="active"><a href="<?= $member_url ?>/votes">Voting Record</a></li>
                </ul>
            </div>
        </div>
        <div class="person-panels">
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
                <div class="magellan-placeholder">&nbsp;</div>
            </div>
            <div class="primary-content__unit">

                <?php if ($party == 'Sinn Fein' && in_array(HOUSE_TYPE_COMMONS, $houses)): ?>
                <div class="panel">
                    <p>Sinn F&eacute;in MPs do not take their seats in Parliament.</p>
                </div>
                <?php elseif (isset($is_new_mp) && $is_new_mp && !$has_voting_record): ?>
                <div class="panel panel--secondary">
                    <h3><?= $full_name ?> is a recently elected MP - elected on <?= format_date($entry_date, LONGDATEFORMAT) ?></h3>

                    <p>When <?= $full_name ?> starts to vote on bills, that information will appear on this page.</p>
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
                              <?php foreach ($segment['votes']->positions as $key_vote) {

                                if ( $key_vote['has_strong'] || $key_vote['position'] == 'has never voted on' ) {
                                    $description = ucfirst($key_vote['desc']);
                                } else {
                                    $description = sprintf(
                                        'We don&rsquo;t have enough information to calculate %s&rsquo;s position on %s.',
                                        $full_name,
                                        $key_vote['policy']
                                    );
                                }
                                $link = sprintf(
                                    '%s/divisions?policy=%s',
                                    $member_url,
                                    $key_vote['policy_id']
                                );
                                $show_link = $key_vote['position'] != 'has never voted on';

                                include '_vote_description.php';

                              } ?>
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
                    <p>Note for journalists and researchers: The data on this page may be used freely,
                       on condition that TheyWorkForYou.com is cited as the source.</p>

                    <p>For an explanation of the vote descriptions please see the FAQ entries on
                    <a href="/help/#vote-descriptions">vote descriptions</a> and
                    <a href="/help/#votingrecord">how the voting record is decided</a></p>
                </div>

            </div>
        </div>
    </div>
</div>
