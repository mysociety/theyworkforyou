<?php
include_once INCLUDESPATH . "easyparliament/templates/html/mp/header.php";

// if this is set to a year for which we have WTT responsiveness stats then
// it'll display a banner with the MPs stats, assuming we have them for the
// year
$display_wtt_stats_banner = '2015';
?>

<div class="full-page">
    <div class="full-page__row">
        <div class="full-page__unit">
            <?php include '_person_navigation.php'; ?>
        </div>
        <div class="person-panels">
            <div class="sidebar__unit in-page-nav">
                <div>
                    <h3 class="browse-content"><?= gettext('Browse content') ?></h3>
                    <ul>
                        <?php if (array_key_exists('posts', $memberships)): ?>
                          <li><a href="#posts"><?= gettext('Memberships') ?></a></li>
                        <?php endif; ?>
                        <?php if (array_key_exists('previous_posts', $memberships)): ?>
                          <li><a href="#previous_posts"><?= gettext('Previous Memberships') ?></a></li>
                        <?php endif; ?>
                        <?php if (array_key_exists('appg_membership', $memberships)): ?>
                            <?php if ($memberships['appg_membership']->is_an_officer()): ?>
                              <li><a href="#appg_officer"><?= gettext('APPG Offices held') ?></a></li>
                            <?php endif; ?>
                            <?php if ($memberships['appg_membership']->is_a_member()): ?>
                              <li><a href="#appg_memberships"><?= gettext('APPG memberships') ?></a></li>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if (array_key_exists('letters_signed', $memberships)): ?>
                          <li><a href="#letters_signed"><?= gettext('Recent open letters') ?></a></li>
                        <?php endif; ?>
                        <?php if (array_key_exists('edms_signed', $memberships)): ?>
                          <li><a href="#edms_signed"><?= gettext('Recent EDMs') ?></a></li>
                        <?php endif; ?>
                        <?php if (array_key_exists('topics_of_interest', $memberships) || array_key_exists('eu_stance', $memberships)): ?>
                          <li><a href="#topics"><?= gettext('Topics of interest') ?></a></li>
                        <?php endif; ?>
                      </ul>
                      <?php include '_featured_content.php'; ?>
                      <?php include '_donation.php'; ?>
                </div>
            </div>

            <div class="primary-content__unit">

                <div class="panel">
                    <a name="interests"></a>
                    <h2><?=gettext('Interests') ?></h2>


                    <?php if (array_key_exists('posts', $memberships)): ?>

                    <h3 id="posts"><?=gettext('Current committee memberships') ?></h3>

                    <ul class='list-dates'>

                        <?php foreach ($memberships['posts'] as $office): ?>
                        <li><?= $office ?> <small>(<?= $office->pretty_dates() ?>)</small></li>
                        <?php endforeach; ?>

                    </ul>

                    <?php endif; ?>

                    <?php if (array_key_exists('previous_posts', $memberships)): ?>

                    <a ></a>
                    <h3 id="previous_posts"><?=gettext('Committee memberships held in the past') ?></h3>

                    <ul class='list-dates'>

                        <?php foreach ($memberships['previous_posts'] as $office): ?>
                        <li><?= $office ?> <small>(<?= $office->pretty_dates() ?>)</small></li>
                        <?php endforeach; ?>

                    </ul>

                    <?php endif; ?>

                    <?php if (array_key_exists('appg_membership', $memberships)): ?>
                        <?php if ($memberships['appg_membership']->is_an_officer()): ?>
                            <h3 id="appg_officer"><?=gettext('APPG Offices held') ?></h3>
                            <ul class='list-dates'>
                                <?php foreach ($memberships['appg_membership']->is_officer_of as $membership): ?>
                                    <li><?= $membership->appg->title ?> <?= $membership->role ? '(' . $membership->role . ')' : '' ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <?php if ($memberships['appg_membership']->is_a_member()): ?>
                            <h3 id="appg_memberships"><?=gettext('APPG memberships') ?></h3>
                            <ul class='list-dates'>
                                    <?php foreach ($memberships['appg_membership']->is_ordinary_member_of as $membership): ?>
                                        <li><?= $membership->appg->title ?></li>
                                    <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if (array_key_exists('letters_signed', $memberships)): ?>
                        <h3 id="letters_signed"><?=gettext('Recent open letters signed') ?></h3>

                        <ul class='list-dates'>
                            <?php foreach ($memberships['letters_signed'] as $signature): ?>
                            <li><?= $signature->date ?>: <a href="<?= $signature->statement->link() ?>"><?= $signature->statement->title ?></a> (+<?= $signature->statement->total_signatures ?> others)</li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if (array_key_exists('edms_signed', $memberships)): ?>
                        <h3 id="edms_signed"><?=gettext('Recent Early Day Motions signed') ?></h3>

                        <ul class='list-dates'>
                            <?php foreach ($memberships['edms_signed'] as $signature): ?>
                            <li><?= $signature->date ?>: <a href="<?= $signature->statement->link() ?>"><?= $signature->statement->title ?></a> (+<?= $signature->statement->total_signatures ?> others)</li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if (array_key_exists('edms_signed', $memberships) || array_key_exists('letters_signed', $memberships)): ?>
                        <p>
                        <a href="https://votes.theyworkforyou.com/person/<?= $person_id ?>/statements"><?= gettext('All open letters and EDMs signed') ?></a>.
                        </p>
                    <?php endif; ?>

                    <?php if (array_key_exists('topics_of_interest', $memberships) || array_key_exists('eu_stance', $memberships)): ?>

                        <h3 id="topics"><?= gettext('Topics of interest') ?></h3>

                        <?php if (array_key_exists('eu_stance', $memberships)): ?>
                            <p>
                                <?php if ($memberships['eu_stance'] == 'Leave' || $memberships['eu_stance'] == 'Remain') { ?>
                                    <strong><?= $full_name ?></strong> campaigned to <?= $memberships['eu_stance'] == 'Leave' ? 'leave' : 'remain in' ?> the European Union
                                <?php } else { ?>
                                    We don't know whether <strong><?= $full_name ?></strong> campaigned to leave, or stay in the European Union
                                <?php } ?>
                                <small>Source: <a href="https://www.bbc.co.uk/news/uk-politics-eu-referendum-35616946">BBC</a></small>
                            </p>
                        <?php endif; ?>

                        <?php if (array_key_exists('topics_of_interest', $memberships)): ?>
                            <ul class="comma-list">

                                <?php foreach ($memberships['topics_of_interest'] as $topic): ?>
                                <li><?= $topic ?></li>
                                <?php endforeach; ?>

                            </ul>
                        <?php endif; ?>
                    <?php endif; ?>

                </div>

                <?php include('_profile_footer.php'); ?>

            </div>
        </div>
    </div>
</div>
