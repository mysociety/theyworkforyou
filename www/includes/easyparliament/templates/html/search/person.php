<div class="search-result search-result--person">
    <img src="<?= $member->image()['url'] ?>" alt="">
    <h3 class="search-result__title"><a href="<?= $member->url() ?>"><?= $member->full_name() ?></a></h3>
<?php
    $latest_membership = $member->getMostRecentGroupedMembership();
    if ($latest_membership && $latest_membership['house'] != HOUSE_TYPE_ROYAL) { ?>
        <p class="search-result__description">
            <?= $latest_membership['current'] ? '' : 'Former' ?>
            <?= $latest_membership['party'] == 'Bishop' ? '' : $latest_membership['party'] ?>
            <?= $latest_membership['rep_name'] ?>
            <?php if ($latest_membership['constituency']) {
                printf(gettext('for %s'), $latest_membership['constituency']);
            } ?>
    (<?php
        print $latest_membership['rep_name'] . ' ';
        $out = [];
        foreach ($member->grouped_memberships[$latest_membership['house']] as $mship) {
            $out[] = sprintf('%s – %s', format_date($mship['start_date'], SHORTDATEFORMAT), $mship['end_date'] == '9999-12-31' ? gettext('current') : format_date($mship['end_date'], SHORTDATEFORMAT));
        }
        print join(', ', array_reverse($out));
        ?>)
    </p>
<?php
    }
    ?>
</div>
