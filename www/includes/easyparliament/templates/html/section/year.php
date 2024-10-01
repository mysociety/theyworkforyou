<div class="full-page__row">

    <div class="business-section">
        <div class="business-section__header">
            <h1 class="business-section__header__title">
                <?= $parent_title ?> &ndash; <?= $title ?>
            </h1>
        </div>
        <div class="business-section__solo">
            <div class="calendar__controls">
                <?php if (isset($prev)) { ?>
                <a href="<?= $prev['url'] ?>" class="calendar__controls__previous">&larr; <?= $prev['title'] ?></a>
                <?php } else { ?>
                <span class="calendar__controls__previous"></span>
                <?php } ?>
                <span class="calendar__controls__current"><?= $year ?></span>
                <?php if (isset($next)) { ?>
                <a href="<?= $next['url'] ?>" class="calendar__controls__next"><?= $next['title'] ?> &rarr;</a>
                <?php } else { ?>
                <span class="calendar__controls__next"></span>
                <?php } ?>
            </div>
            <?php if (isset($years)) {
                foreach ($years as $year => $months) { ?>
            <div class="calendar-year">
                <?php foreach ($months as $month => $dates) {
                    include '_calendar.php';
                } ?>
            </div>
            <?php }
                } else { ?>
                <?= sprintf(gettext('We don’t seem to have any %s for this year.'), $parent_title) ?>
            <?php } ?>
        </div>
    </div>

    <?php $search_title = sprintf(gettext("Search %s"), $title);
                include '_search.php'; ?>

</div>
