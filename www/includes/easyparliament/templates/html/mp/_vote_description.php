<?php
/** @var MySociety\TheyWorkForYou\PolicyPairCollection $segment */
/** @var MySociety\TheyWorkForYou\PolicyDistributionPair $policy_pair */

$pp = $policy_pair;
$md = $policy_pair->member_distribution;
$cd = $policy_pair->comparison_distribution;
?>


<li class="vote-description" data-policy-id="<?= $pp->policy_id ?>" data-policy-group="<?= $segment->group_slug ?>" data-policy-direction="<?= $md->distance_score ?>" data-policy-party-name="<?= $md->party_slug ?>" <?php if ($cd) { ?>data-policy-party-direction="<?= $cd->distance_score ?>"<?php } ?> data-policy-party-score-distance="<?= $pp->scoreDifference() ?> ?>">

    <?= $md->getVerboseScore() ?> <?= $pp->policy_desc ?>
    <a class="vote-description__source" href="<?= $pp->getMoreDetailsLink() ?>"><?= $md->totalVotes() > 0 ? 'Show votes' : "Details" ?></a>
    <a class="vote-description__evidence" href="<?= $pp->getMoreDetailsLink() ?>">
        <?= $md->getVerboseDescription() ?>
        <?php if ($policy_pair->comparison_distribution) { ?>
            Comparable <?= $cd->party_name ?> MPs <?= $cd->getVerboseScoreLower() ?>.
        <?php } ?>
    </a>
    <?php if ($pp->annotation_count) { ?>
        <a class="vote-description__evidence" href="<?= $pp->getMoreDetailsLink() ?>">
        <span>📓 This MP has made public statements about <?= $pp->annotation_count ?> <?= make_plural("vote", $pp->annotation_count) ?> in this policy area.</span>
    </a>
    <?php } ?>
    
    <?php if ($pp->covid_affected) { ?>
    <span style="font-size:60%">Absences for this policy may be affected <a href="<?= $member_url ?>/votes#covid-19">COVID-19 restrictions</a>.</span>
    <?php } ?>
</li>
