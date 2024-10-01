<div class="search-result search-result--person">
    <img src="<?= $member->image()['url'] ?>" alt="">
    <h3 class="search-result__title"><a href="<?= $member->url() ?>"><?= $member->full_name() ?></a></h3>
    <?php $latest_membership = $member->getMostRecentMembership(); ?>
    <?php if ($latest_membership && $latest_membership['house'] != HOUSE_TYPE_ROYAL) { ?>
        <p class="search-result__description">
            <?= $latest_membership['current'] ? '' : 'Former' ?>
            <?= $latest_membership['party'] == 'Bishop' ? '' : $latest_membership['party'] ?>
            <?= $latest_membership['rep_name'] ?>
            <?php if ($latest_membership['constituency']) { ?>
                <?= sprintf(gettext('for %s'), $latest_membership['constituency']) ?>
            <?php } ?>
            (<?= format_date($latest_membership['start_date'], SHORTDATEFORMAT) ?> – <?= $latest_membership['current'] ? gettext('current') : format_date($latest_membership['end_date'], SHORTDATEFORMAT); ?>)
        </p>
    <?php } ?>
</div>
