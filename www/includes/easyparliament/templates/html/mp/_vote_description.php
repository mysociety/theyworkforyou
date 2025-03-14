<?php
/** @var MySociety\TheyWorkForYou\PolicyPairCollection $segment */
/** @var MySociety\TheyWorkForYou\PolicyDistributionPair $policy_pair */

$pp = $policy_pair;
$md = $policy_pair->member_distribution;
$cd = $policy_pair->comparison_distribution;
?>


<li class="vote-description" data-policy-id="<?= $pp->policy_id ?>" data-policy-group="<?= $segment->group_slug ?>" data-policy-direction="<?= $md->distance_score ?>" data-policy-party-name="<?= $md->party_slug ?>" data-policy-party-direction="<?= $cd->distance_score ?>" data-policy-party-score-distance="<?= $pp->scoreDifference() ?> ?>">

    <?= $md->getVerboseScore() ?> <?= $pp->policy_desc ?>
    <a class="vote-description__source" href="<?= $pp->getMoreDetailsLink() ?>"><?= $md->totalVotes() > 0 ? 'Show votes' : "Details" ?></a>
    <a class="vote-description__evidence" href="<?= $pp->getMoreDetailsLink() ?>">
        <?= $md->num_strong_votes_same ?> votes for, 
        <?= $md->num_strong_votes_different ?> against, 
        <?= $md->num_strong_votes_absent ?> absences
        between <?= $md->start_year ?> and <?= $md->end_year ?>.
        <?php if ($policy_pair->comparison_distribution) { ?>
            Comparable <?= $cd->party_name ?> MPs <?= $cd->getVerboseScoreLower() ?>.
        <?php } ?>
    </a>
    <?php if ($pp->covid_affected) { ?>
    <span style="font-size:60%">Absences for this policy may be affected <a href="<?= $member_url ?>/votes#covid-19">COVID-19 restrictions</a>.</span>
    <?php } ?>
</li>
