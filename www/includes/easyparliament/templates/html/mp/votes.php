<?php
include_once INCLUDESPATH . "easyparliament/templates/html/mp/header.php";

# fetch covid_policy_list

/** @var MySociety\TheyWorkForYou\PolicyDistributionCollection[] $key_votes_segments */
/** @var MySociety\TheyWorkForYou\PolicyDistributionCollection|null $sig_diff_policy */
/** @var \MySociety\TheyWorkForYou\PolicyComparisonPeriod[] $available_periods */
/** @var \MySociety\TheyWorkForYou\PolicyComparisonPeriod $comparison_period */
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

                <?php if ($profile_message): ?>
                <div class="panel panel--profile-message">
                    <p><?= $profile_message ?></p>
                </div>
                <?php endif; ?>


                <div class="panel">
                    <h2>Voting summaries</h2>
                    <?php if (!empty($available_periods)): ?>
                        <nav class="subpage-content-list js-accordion" aria-label="Comparison periods">
                            <h3 class="js-accordion-button">For period: <?= $comparison_period->description ?></h3>
                            <ul class="js-accordion-content">
                                <?php foreach($available_periods as $period) { ?>
                                    <li><a href="?comparison_period=<?= $period->lslug() ?>" class="<?= $period->lslug() === $comparison_period->lslug() ? 'active-comparison-period' : '' ?>"><?= $period->description ?></a></li>
                                <?php } ?>
                            </ul>
                        </nav>
                    <?php endif; ?>

                    <p>
                        MPs have many roles, but one of the most important is that they make decisions. These decisions shape the laws that govern us, and can affect every aspect of how we live our lives. 
                        One of the ways MPs make decisions is by voting.
                    </p>
                    <p>
                        On TheyWorkForYou, we create voting summaries that group a set of decisions together, show how an MP has generally voted on a set of related votes, and if they differ from their party.
                    </p>


                    <p>
                        You can read more about <a href="/voting-information/#voting-summaries">our process</a>, <a href="/voting-information/#what-kind-of-votes-are-included-in-theyworkforyou-s-policies">the kinds of votes we include</a>, <a href="/voting-information/#comparison-to-parties">how we compare MPs to parties</a>, and <a href="/voting-information/#votes-are-not-opinions-but-they-matter">why we think this is important</a>.</a>
                    </p>

                            <?php if ($has_voting_record): ?>
                                <hr>
                                <p>Below are summaries of how <?= $full_name ?> has voted on key issues, grouped by policy area (randomly ordered).</p>
                                <nav aria-label="Key issues navigation">
                                    <ul class="votes-navigation-menu">
                                    <?php foreach ($key_votes_segments as $segment): ?>
                                        <?php if (count($segment->policy_pairs) > 0): ?>
                                        <li><a href="#<?= $segment->group_slug ?>"><?= $segment->group_name ?></a></li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    </ul>
                                </nav>
                    <?php if ($comparison_period->lslug() === 'labour_2024' && in_array('all_time', array_map(fn($period) => $period->lslug(), $available_periods))) { ?>
                        <p>This page shows relevant votes in the current Parliament, you can also view an <a href="?comparison_period=ALL_TIME">all time voting summary</a>.</p>
                    <?php } elseif ($comparison_period->lslug() === 'all_time' && in_array('labour_2024', array_map(fn($period) => $period->lslug(), $available_periods))) { ?>
                        <p>This page shows relevant votes while <?= ucfirst($full_name) ?> has been in Parliament, you can also view a <a href="?comparison_period=labour_2024">summary just for the current Parliament</a>.</p>
                    <?php } ?>
                            <?php endif; ?>

                            </div>

                <?php if ($party_switcher == true): ?>
                    <?php include('_cross_party_mp_panel.php'); ?>
                <?php endif; ?>

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

                <?php if ($party_member_count > 1 && $party != "Independent") { ?>
                <div class="panel">
                    <a name="votes"></a>
                    <h2><?= $full_name ?>&rsquo;s voting in Parliament</h2>

                    <?php if ($party_switcher == true or $current_member[HOUSE_TYPE_COMMONS] == false) { ?>
                        <p> 
                        <?= $full_name ?> was previously a <?= $unslugified_comparison_party ?> MP, and on the <b>vast majority</b> of issues <a href="/voting-information/#party-and-individual-responsibility-for-decisions">would have followed instructions from their party</a> and voted the <b>same way</b> as <?= $unslugified_comparison_party ?> MPs.
                        </p>
                    <?php } else { ?>
                        <p> 
                        <?= $full_name ?> is a <?= $unslugified_comparison_party ?> MP, and on the <b>vast majority</b> of issues <a href="/voting-information/#party-and-individual-responsibility-for-decisions">follow instructions from their party</a> and vote the <b>same way</b> as other <?= $unslugified_comparison_party ?> MPs.
                        </p>
                    <?php } ?>

                    
                    <?php if ($sig_diff_policy !== null && count($sig_diff_policy->policy_pairs) > 0) { ?>
                    <?php if ($party_switcher == true) { ?>
                        <p>
                        However, <?= $full_name ?> sometimes <b>differs</b> from <?= $unslugified_comparison_party ?> MPs, such as:
                        </p>
                    <?php } else { ?>
                        <p>
                        Where MPs <b>differ</b> is either because they have made a decision not to follow the party whip (rebelling), or where they have differed from the majority of their colleagues in a free vote.
                        </p>
                        <p>
                        <?= $full_name ?> sometimes differs from their party colleagues, such as:
                        </p>
                    <?php } ?>

                    <ul class="vote-descriptions">
                              <?php foreach ($sig_diff_policy->policy_pairs as $policy_pair) {
                                  include '_vote_description.php';
                              } ?>
                            </ul>

                    <?php } ?>

                <?php if ($rebellion_rate) { ?>
                    <p><?= $rebellion_rate ?></p>
                <?php } ?>

                </div>
                <?php } ?>

                <?php if ($has_voting_record): ?>
                    
                    <?php $displayed_votes = false; ?>

                    <?php foreach ($key_votes_segments as $segment): ?>
                        
                        <?php if (count($segment->policy_pairs) > 0): ?>
                        <?php $most_recent = ''; ?>

                        <div class="panel">

                            <h2 class="policy-name" id="<?= $segment->group_slug ?>">
                                How <?= $full_name ?> voted on <?= $segment->group_name ?>&nbsp;<small><a class="nav-anchor" href="<?= $member_url ?>/votes#<?= $segment->group_slug ?>">#</a></small>
                            </h2>

                            <p>For votes held while they were in office:</p>

                            <ul class="vote-descriptions">
                              <?php foreach ($segment->policy_pairs as $policy_pair) {

                                  include '_vote_description.php';

                              } ?>
                            </ul>

                            <p class="voting-information-provenance">

                                Last updated: <?= format_date($segment->latestUpdate($policy_last_update), LONGDATEFORMAT) ?>.
                                <a href="/voting-information">Learn more about our voting records and what they mean.</a>
                            </p>

                        </div>

                            <?php $displayed_votes = true; ?>

                        <?php endif; ?>

                    <?php endforeach; ?>

                    <?php if (!$displayed_votes): ?>

                        <div class="panel">
                            <p>This person has not voted on any of the key issues which we keep track of.</p>
                        </div>

                    <?php endif; ?>

                <?php endif; ?>
                <?php include('_covid19_panel.php'); ?>

                <?php include('_profile_footer.php'); ?>
            </div>
        </div>
    </div>
</div>
