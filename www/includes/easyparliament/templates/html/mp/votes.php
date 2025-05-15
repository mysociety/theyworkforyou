<?php
include_once INCLUDESPATH . "easyparliament/templates/html/mp/header.php";

# fetch covid_policy_list

/** @var MySociety\TheyWorkForYou\PolicyDistributionCollection[] $key_votes_segments */
/** @var MySociety\TheyWorkForYou\PolicyDistributionCollection $sig_diff_policy */
/** @var \MySociety\TheyWorkForYou\PolicyComparisonPeriod[] $available_periods */
/** @var \MySociety\TheyWorkForYou\PolicyComparisonPeriod $comparison_period */



?>

<div class="full-page">
    <div class="full-page__row">
        <div class="full-page__unit">
            <?php include '_person_navigation.php'; ?>
        </div>
        <div class="person-panels">
            <div class="sidebar__unit in-page-nav">
                <div>

                <h3 class="browse-content">Comparison periods</h3>
                    <ul> 
                    <?php foreach($available_periods as $period) { ?>
                        <li class="active"><a href="?comparison_period=<?= $period->lslug() ?>"><?= $period->description ?></a></li>
                    <?php } ?>
                    </ul>


                    <h3 class="browse-content">Policy groups</h3>
                    <ul> 
                        <?php if ($has_voting_record): ?>
                        <?php foreach ($key_votes_segments as $segment): ?>
                        <?php if (count($segment->policy_pairs) > 0): ?>
                        <li><a href="#<?= $segment->group_slug ?>"><?= $segment->group_name ?></a></li>
                        <?php endif; ?>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>


                    
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
                    <h3>For period: <?= $comparison_period->description ?></h3>
                    <p>
                        MPs have many roles, but one of the most important is that they make decisions. These decisions shape the laws that govern us, and can affect every aspect of how we live our lives. 
                        One of the ways MPs make decisions is by voting.
                    </p>
                    <p>
                        On TheyWorkForYou, we create voting summaries that group a set of decisions together, show how an MP has generally voted on a set of related votes, and if they differ from their party.
                    </p>
                    <p>
                        You can see these groups, randomly ordered, below.
                    </p>
                    <p>
                        You can read more about <a href="/voting-information/#voting-summaries">how this works</a>, <a href="/voting-information/#what-kind-of-votes-are-included-in-theyworkforyou-s-policies">the kinds of votes we include</a>, <a href="/voting-information/#comparison-to-parties">how we compare MPs to parties</a>, and <a href="/voting-information/#votes-are-not-opinions-but-they-matter">why we think this is important</a>.</a>
                    </p>
                    <hr />
                    <p>
                        These summaries are created by the team at TheyWorkForYou. We are independent of Parliament and receive no public funding for this work.
                    </p>
                    <div class="inline-donation-box">
                        <a href="/support-us/?how-often=monthly&how-much=5" class="button">Become a TheyWorkForYou Supporter for £5/month</a>
                        <a href="/support-us/" class="button">Donate another amount</a>
                    </div>
                    <p>Learn more about <a href="/support-us/#why-does-mysociety-need-donations-for-these-sites">how we'll use your donation</a> and <a href="/support-us/#i-want-to-be-a-mysociety-supporter">other ways to help</a>.</p>
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

                    
                    <?php if (count($sig_diff_policy->policy_pairs) > 0) { ?>
                    <?php if ($party_switcher == true) { ?>
                        <p>
                        However, <?= $full_name ?> sometimes <b>differs</b> from <?= $unslugified_comparison_party ?> MPs, such as:
                        </p>
                    <?php } else { ?>
                        <p>
                        However, <?= $full_name ?> sometimes <b>differs</b> from their party colleagues, such as:
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
