<?php
include_once INCLUDESPATH . "easyparliament/templates/html/mp/header.php";

// if this is set to a year for which we have WTT responsiveness stats then
// it'll display a banner with the MPs stats, assuming we have them for the
// year
$display_wtt_stats_banner = '2015';
?>

<div class="full-page">
    <div class="full-page__row">
        <div class="person-panels">
            <div class="sidebar__unit in-page-nav">
                <div>
                    <?php include '_sidebar.php'; ?>
                </div>
            </div>

            <div class="primary-content__unit">

                <?php include '_donation_banner.php'; ?>

                <div class="panel">
                    <a name="interests"></a>
                    <h2 id="posts"><?=gettext('Committees') ?></h2>
                    <?php foreach ($memberships['committee_intro'] as $paragraph): ?>
                    <p><?= $paragraph ?></p>
                    <?php endforeach; ?>
                    <?php if (array_key_exists('posts', $memberships)): ?>
                    <p><?= sprintf(gettext('%s is currently a member of the following committees:'), $full_name) ?></p>
                    <?php foreach ($memberships['posts'] as $office): ?>
                    <h4><?= $office ?></h4>
                    <div class="committee-more-info">
                    <?= $office->htmlDesc() ?>

                    <?php if (!empty($office->external_url)): ?>
                        <p><a href="<?= $office->external_url ?>"><?=gettext('Learn more about this committee') ?></a></p>
                    <?php endif; ?>
                    </div>
                    <hr/>
                    <?php endforeach; ?>


                    <?php endif; ?>
                    <?php if (array_key_exists('previous_posts', $memberships)): ?>

                    <a ></a>
                    <h3 id="previous_posts"><?=gettext('Committee memberships held in the past') ?></h3>

                    <?php foreach ($memberships['previous_posts'] as $office): ?>
                    <h4><?= $office ?> <small>(<?= $office->pretty_dates() ?>)</small></h4>
                    <div class="committee-more-info">
                    <?= $office->htmlDesc() ?>

                    <?php if (!empty($office->external_url)): ?>
                        <p><a href="<?= $office->external_url ?>"><?=gettext('Learn more about this committee') ?></a></p>
                    <?php endif; ?>
                    </div>
                    <hr/>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                    <?php if (array_key_exists('appg_membership', $memberships)): ?>
                        <div class="panel">
                        <h2><?= $memberships['groups_name'] ?></h2>
                        <?php if ($memberships['house'] == HOUSE_TYPE_SCOTLAND || $memberships['house'] == HOUSE_TYPE_WALES): ?>
                        <p><?=gettext('Cross-Party Groups are informal groups made up of members from more than one party who share an interest in a particular country or subject.') ?></p>
                        <p><?=gettext('They are not committees of the parliament and have no formal powers, but they are registered and may receive support from outside organisations.') ?></p>
                        <?php else: ?>
                        <p><?=gettext('All-Party Parliamentary Groups (APPGs) are informal cross-party groups made up of MPs and Peers who share an interest in a particular country or subject.') ?></p>
                        <p><?=gettext('They do not have formal powers or funding, but can book rooms on the parliamentary estate and may receive funding from outside organisations and companies.') ?></p>
                        <p>We source information on APPG memberships from lists on APPG websites or asking APPGs for unpublished lists. Please <a href="https://survey.alchemer.com/s3/8446196/TheyWorkForYou-APPG-data">report any incorrect or outdated information</a>.</p>
                        <?php endif; ?>
                        <?php
                        $appg_roles = [
                            'is_officer_of' => sprintf(gettext('%s is an officer of the following groups'), $full_name),
                            'is_ordinary_member_of' => sprintf(gettext('%s is a member of the following groups'), $full_name),
                        ];
?>

                        <?php foreach ($appg_roles as $role_key => $role_title): ?>

                            <?php if (!$memberships['appg_membership']->$role_key->isEmpty()): ?>
                                <h3 id="appg_<?= $role_key ?>"><?= $role_title ?></h3>
                                <?php /** @var MySociety\TheyWorkForYou\DataClass\APPGs\APPGMembership $membership */ ?>

                                <?php foreach ($memberships['appg_membership']->$role_key as $membership): ?>
                                    <hr>
                                    <p>
                                        <span><?= $membership->appg->title ?> <?= $membership->role ? '(' . $membership->role . ')' : '' ?></span>
                                        <details>
                                            <summary>More info</summary>
                                            <div class="appg-more-info">
                                                <ul>
                                                    <li><span class="appg-property-label">Purpose:</span> <?= $membership->appg->purpose ?></li>
                                                    <li><span class="appg-property-label">Membership Source:</span> 
                                                        <?php if (!empty($membership->membership_source_url)) { ?>
                                                            <a href="<?= $membership->membership_source_url ?>">Source</a>
                                                        <?php } else { ?>
                                                            E-mail correspondence with the group
                                                        <?php } ?>
                                                    </li>
                                                    <li><span class="appg-property-label">Website:</span> <?php if ($membership->appg->website): ?><a href="<?= $membership->appg->website ?>"><?= $membership->appg->website ?></a><?php else: ?>N/A<?php endif; ?></li>
                                                    <li><span class="appg-property-label">Register:</span> <a href="<?= $membership->appg->source_url ?>">Parliament website</a></li>
                                                </ul>
                                            </div>
                                        </details>
                                    </p>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                <?php include('_profile_footer.php'); ?>

            </div>
        </div>
    </div>
</div>
