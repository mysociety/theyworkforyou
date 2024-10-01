<li class="vote-description" data-policy-id="<?= $policy_id ?? '' ?>" data-policy-desc="<?= $policy_desc ?? '' ?>" data-policy-group="<?= $policy_group ?? '' ?>" data-policy-direction="<?= $policy_direction ?? '' ?>" data-policy-party-name="<?= $comparison_party ?? '' ?>" data-policy-party-direction="<?= $party_position ?? '' ?>" data-policy-party-score-distance="<?= $party_score_difference ?? '' ?>">

    <?= $description ?>
    <a class="vote-description__source" href="<?= $link ?>"><?= $link_text ?? 'Show votes' ?></a>
    <?php if (isset($key_vote) || isset($party_voting_line)) { ?>
    <a class="vote-description__evidence" href="<?= $link ?>">
        <?= isset($key_vote) ? "$key_vote[summary]." : '' ?>
        <?= $party_voting_line ?? '' ?>
    </a>
    <?php if (isset($covid_affected) && $covid_affected) { ?>
    <span style="font-size:60%">Absences for this policy may be affected <a href="<?= $member_url ?>/votes#covid-19">COVID-19 restrictions</a>.</span>
    <?php } ?>
    <?php } ?>
</li>
