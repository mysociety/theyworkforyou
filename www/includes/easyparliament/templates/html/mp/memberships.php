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
                    <?php include '_person_navigation.php'; ?>
                    <?php include '_featured_content.php'; ?>
                    <?php include '_donation.php'; ?>
                </div>
            </div>

            <div class="primary-content__unit">

                <div class="panel">
                    <a name="interests"></a>
                    <h2 id="posts"><?=gettext('Committees') ?></h2>
                    <p>
                    In the UK Parliament, committees are groups of MPs or Peers who examine specific issues in more detail than can be done in debates.</p>
                    <p>Some committees focus on checking the government’s decisions and spending, while others investigate specific topics and proposed legislation.</p>
                    <?php if (array_key_exists('posts', $memberships)): ?>
                    <p><?= $full_name ?> is currently a member of the following committees:</p>
                    <?php foreach ($memberships['posts'] as $office): ?>
                    <h4><?= $office ?></h4>
                    <div class="committee-more-info">
                    <?= $office->htmlDesc() ?>

                    <?php if (!empty($office->external_url)): ?>
                        <p><a href="<?= $office->external_url ?>">Learn more about this committee</a></p>
                    <?php endif; ?>
                    </div>
                    <hr/>
                    <?php endforeach; ?>


                    <?php endif; ?>
                    <?php if (array_key_exists('previous_posts', $memberships)): ?>

                    <a ></a>
                    <h3 id="previous_posts"><?=gettext('Committee memberships held in the past') ?></h3>

                    <ul class='list-dates'>

                        <?php foreach ($memberships['previous_posts'] as $office): ?>
                        <li><?= $office ?> <small>(<?= $office->pretty_dates() ?>)</small></li>
                        <?php endforeach; ?>

                    </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (array_key_exists('appg_membership', $memberships)): ?>
                        <div class="panel">
                        <h2><?=gettext('All-Party Parliamentary Groups (APPGs)') ?></h2>
                        <p>All-Party Parliamentary Groups (APPGs) are informal cross-party groups made up of MPs and Peers who share an interest in a particular country or subject.</p>
                        <p>They do not have formal powers or funding, but can book rooms on the parliamentary estate and may receive funding from outside organisations and companies.</p>
                        <p>We source information on APPG memberships from lists on APPG websites or asking APPGs for unpublished lists. Please <a href="https://survey.alchemer.com/s3/8446196/TheyWorkForYou-APPG-data">report any incorrect or outdated information</a>.</p>
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
                                                    <li><span class="appg-property-label">Membership Source:</span> <a href="<?= $membership->membership_source_url ?>">Source</a></li>
                                                    <li><span class="appg-property-label">APPG Website:</span> <?php if ($membership->appg->website): ?><a href="<?= $membership->appg->website ?>"><?= $membership->appg->website ?></a><?php else: ?>N/A<?php endif; ?></li>
                                                    <li><span class="appg-property-label">APPG register:</span> <a href="<?= $membership->appg->source_url ?>">Parliament website</a></li>
                                                </ul>
                                            </div>
                                        </details>
                                    </p>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    
                    <?php if (array_key_exists('letters_signed', $memberships) || array_key_exists('edms_signed', $memberships)): ?>
                        <div class="panel">
                        <h2 id="letters-and-edms">Letters and EDMs</h2>
                        <p>Early Day Motions are when MPs can propose and co-sign statements for discussion. In practice, these work as internal petitions where MPs can signal their support for a particular issue or cause.</p>

                        <p>We're starting to collect and display when MPs sign open letters outside Parliament - if there are examples we're missing, <a href="https://survey.alchemer.com/s3/8440376/TheyWorkForYou-Open-Letter">please let us know</a>. </p>
                    <?php endif; ?>
                    <?php if (array_key_exists('letters_signed', $memberships)): ?>
                        <h3 id="letters_signed"><?=gettext('Recent open letters signed') ?></h3>
                        <ul class='list-dates'>
                            <?php foreach ($memberships['letters_signed'] as $signature): ?>
                            <li><?= $signature->date ?>: <a href="<?= $signature->statement->link() ?>"><?= $signature->statement->title ?></a> (+<?= $signature->statement->total_signatures - 1 ?> others)</li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if (array_key_exists('edms_signed', $memberships)): ?>
                        <h3 id="edms_signed"><?=gettext('Recent Early Day Motions signed') ?></h3>
                        <ul class='list-dates'>
                            <?php foreach ($memberships['edms_signed'] as $signature): ?>
                            <li><?= $signature->date ?>: <a href="<?= $signature->statement->link() ?>"><?= $signature->statement->title ?></a> (+<?= $signature->statement->total_signatures - 1  ?> others)</li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if (array_key_exists('edms_signed', $memberships) || array_key_exists('letters_signed', $memberships)): ?>
                        <p>
                        <a href="https://votes.theyworkforyou.com/person/<?= $person_id ?>/statements"><?= gettext('All open letters and EDMs signed') ?></a>.
                        </p>
                        <?php endif; ?>
 
                    <?php if (array_key_exists('letters_signed', $memberships) || array_key_exists('edms_signed', $memberships)): ?>
                        </div>
                    <?php endif; ?>

                    <?php if (array_key_exists('topics_of_interest', $memberships) || array_key_exists('eu_stance', $memberships)): ?>
                        <div class="panel">
                            <h2 id="topics"><?= gettext('Topics of interest') ?></h2>

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
                        </div>
                    <?php endif; ?>

                <?php include('_profile_footer.php'); ?>

            </div>
        </div>
    </div>
</div>
