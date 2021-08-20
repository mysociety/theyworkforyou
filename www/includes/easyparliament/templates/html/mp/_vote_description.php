<li class = "vote-description" data-policy-id="<?= isset($policy_id) ? $policy_id : '' ?>" data-policy-desc="<?= isset($policy_desc) ? $policy_desc : '' ?>" data-policy-group="<?= isset($policy_group) ? $policy_group : '' ?>" data-policy-direction="<?= isset($policy_direction) ? $policy_direction : '' ?>" data-policy-party-name="<?= isset($party) ? $party : '' ?>" data-policy-party-direction="<?= isset($party_position) ? $party_position : '' ?>" data-policy-party-score-distance="<?= isset($party_score_difference) ? $party_score_difference : '' ?>">

    <?= $description ?>
    <?php if ( $show_link ) { ?>
        <a class="vote-description__source" href="<?= $link ?>">Show votes</a>
        <?php if (isset($key_vote) || isset($party_voting_line)) { ?>
        <a class="vote-description__evidence" href="<?= $link ?>">
            <?= isset($key_vote) ? "$key_vote[summary]." : '' ?>
            <?= isset($party_voting_line) ? $party_voting_line : '' ?>
        </a>
        <?php } ?>
    <?php } ?>
</li>
