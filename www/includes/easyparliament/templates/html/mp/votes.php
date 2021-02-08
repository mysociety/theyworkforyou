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
                    <li><a href="<?= $member_url ?>/recent">Recent Votes</a></li>
                </ul>
            </div>
        </div>
        <div class="person-panels">
            <div class="sidebar__unit in-page-nav">
                <ul>
                    <?php if ($has_voting_record): ?>
                    <?php foreach ($key_votes_segments as $segment): ?>
                    <?php if (count($segment['votes']->positions) > 0): ?>
                    <li><a href="#<?= $segment['key'] ?>"><?= $segment['title'] ?></a></li>
                    <?php endif; ?>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="primary-content__unit">

                <?php if ($profile_message): ?>
                <div class="panel panel--profile-message">
                    <p><?= $profile_message ?></p>
                </div>
                <?php endif; ?>

                <?php include('_covid19_panel.php'); ?>

                <?php if ($party == 'Sinn Féin' && in_array(HOUSE_TYPE_COMMONS, $houses)): ?>
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
                        <?php $most_recent = ''; ?>

                        <div class="panel">

                            <h2 id="<?= $segment['key'] ?>">
                                How <?= $full_name ?> voted on <?= $segment['title'] ?>
                                <small><a class="nav-anchor" href="<?= $member_url ?>/votes#<?= $segment['key'] ?>">#</a></small>
                            </h2>

                            <ul class="vote-descriptions">
                              <?php foreach ($segment['votes']->positions as $key_vote) {
                                $policy_id = $key_vote['policy_id'];

                                if (isset($policy_last_update[$policy_id]) && $policy_last_update[$policy_id] > $most_recent) {
                                  $most_recent = $policy_last_update[$policy_id];
                                }

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
                                    $policy_id
                                );
                                $show_link = $key_vote['position'] != 'has never voted on';

                                include '_vote_description.php';

                              } ?>
                            </ul>

                            <div class="share-vote-descriptions">
                                <p>Share a <a href="<?= $abs_member_url ?>/policy_set_png?policy_set=<?= $segment['key'] ?>">screenshot</a> of these votes:</p>

                                <a href="#" class="facebook-share-button js-facebook-share" data-text="<?= $single_policy_page ? '' : $segment['title'] . ' ' ?><?= $page_title ?>" data-url="<?= $abs_member_url ?>/votes?policy=<?= $segment['key'] ?>">Share</a>

                                <a class="twitter-share-button" href="https://twitter.com/share" data-size="small" data-url="<?= $abs_member_url ?>/votes?policy=<?= $segment['key'] ?>">Tweet</a>
                            </div>

                            <p class="voting-information-provenance">
                                Last updated: <?= format_date($most_recent, LONGDATEFORMAT) ?>.
                                <a href="/voting-information">Please share these votes responsibly.</a>
                            </p>

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

                <?php include('_vote_footer.php'); ?>
            </div>
        </div>
    </div>
</div>
