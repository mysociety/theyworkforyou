<li id="<?= $division['division_id'] ?>">
    <span class="policy-vote__date">On <?= strftime('%e %b %Y', strtotime($division['date'])) ?>:</span>
    <span class="policy-vote__text"><?= $full_name ?><?= $division['text'] ?></span>
    <?php if ($division['date'] > '2020-06-01' && $division['date'] < '2020-06-10' && $division['vote'] == 'absent') { ?>
        <p class="vote-description__covid">This absence may have been affected by <a href="<?= $member_url ?>/votes#covid-19">COVID-19 restrictions</a>.</p> 
    <?php } ?>
    <a class="vote-description__source" href="/divisions/<?= $division['division_id'] ?>/mp/<?= $person_id ?>">Show vote</a>

    <?php
    # remove the current policy from the list of related policies
    if (isset($policy)) {
        $division['related_policies'] = array_filter($division['related_policies'], function ($related_policy) use ($policy) {
            return $related_policy['policy_id'] != $policy['policy_id'];
        });
    }

if (isset($policy) && count($division['related_policies'])) {
    # We want to split the related policies into two groups:
    # 1. those that are the same direction as the current policy (e.g. the vote is in favour of both policies)
    # 2. those that are the opposite direction to the current policy (e.g. the vote is read positive in one police, and negative in another)
    $same_direction = [];
    $other_direction = [];
    foreach ($division['related_policies'] as $related_policy) {
        $current_policy_direction_for_vote = $division["direction"];
        $related_policy_direction_for_vote = $related_policy["direction"];
        # remove " (strong)" from the end of the direction if it's present
        $current_policy_direction_for_vote = preg_replace("/ \(strong\)$/", "", $current_policy_direction_for_vote);
        $related_policy_direction_for_vote = preg_replace("/ \(strong\)$/", "", $related_policy_direction_for_vote);
        $is_same_direction = $current_policy_direction_for_vote == $related_policy_direction_for_vote;
        # if related direction is 'abstention' then it's the same direction
        if ($related_policy_direction_for_vote == "abstention") {
            $is_same_direction = true;
        }
        if ($is_same_direction) {
            $same_direction[] = $related_policy;
        } else {
            $other_direction[] = $related_policy;
        }
    }

    # get an array of the two sets so we can loop through them both in the same code
    $related_policies = [
        [
            "set" => $same_direction,
            "title" => "This vote is also related to:",
            "class" => "policy-vote__related-policies",
        ],
        [
            "set" => $other_direction,
            "title" => "This policy conflicts with:",
            "class" => "policy-vote__opposing-policies",
        ],
    ];

    foreach ($related_policies as $related_policy_batch) { ?>
            <?php if (count($related_policy_batch["set"]) > 0) { ?>
                <p class="policy-vote__related-policies-title"><?= $related_policy_batch["title"] ?></p>
                <ul class="<?= $related_policy_batch["class"] ?>">
                    <?php foreach ($related_policy_batch["set"] as $related_policy) { ?>
                        <li>
                            <a href="<?= $member_url ?>/divisions?policy=<?= $related_policy['policy_id'] ?>">
                                <?= $related_policy['policy_title'] ?>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
            <?php } ?>
        <?php } ?>
    <?php } ?>
</li>
