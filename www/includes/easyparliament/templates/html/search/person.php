<div class="search-result search-result--person">
    <img src="<?= $member->image()['url'] ?>" alt="">
    <h3 class="search-result__title"><a href="<?= $member->url() ?>"><?= $member->full_name() ?></a></h3>
    <p class="search-result__description">
    <?php $details = $member->getMostRecentMembership(); ?>
    <?= $details['left_house'] != '9999-12-31' ? 'Former ' : '' ?><?= $details['party'] ? $details['party'] . ' ' : '' ?><?= $details['rep_name'] ?>, <?= $details['cons'] ? $details['cons'] . ', ' : ''?><?= format_date($details['entered_house'], SHORTDATEFORMAT) ?> &ndash; <?= $details['left_house'] != '9999-12-31' ? format_date($details['left_house'], SHORTDATEFORMAT) : '' ?>
    </p>
</div>
