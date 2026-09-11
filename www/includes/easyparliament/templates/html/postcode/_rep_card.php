<a href="<?= $rep['mp_url'] ?>" class="people-list__person">
    <?php if ($rep['image']) { ?>
    <img class="people-list__person__image" src="<?= $rep['image'] ?>" loading="lazy" alt="">
    <?php } ?>
    <h2 class="people-list__person__name"><?= $rep['name'] ?></h2>
    <p class="people-list__person__memberships">
        <span class="people-list__person__constituency"><?= $rep['constituency'] ?></span>
        <span class="people-list__person__party <?= slugify($rep['party']) ?>"><?= $rep['party'] ?></span>
    </p>
</a>
<details class="rep-detail"<?= !empty($expand) ? ' open' : '' ?>>
    <summary><?= gettext('More details') ?></summary>
    <?php if (!empty($rep['committees'])) { ?>
        <h4><?= gettext('Committees') ?></h4>
        <ul>
        <?php foreach ($rep['committees'] as $committee) { ?>
            <li><?= $committee ?></li>
        <?php } ?>
        </ul>
    <?php } ?>
    <?php if (!empty($rep['appgs'])) { ?>
        <h4><?= $rep['appgs_label'] ?></h4>
        <ul>
        <?php foreach ($rep['appgs'] as $appg) { ?>
            <li><?= htmlspecialchars($appg['title']) ?> (<?= htmlspecialchars($appg['role']) ?>)</li>
        <?php } ?>
        </ul>
    <?php } ?>
    <?php if (empty($rep['committees']) && empty($rep['appgs'])) { ?>
        <p><?= sprintf(gettext('No group memberships available, see their <a href="%s">whole profile</a>.'), $rep['mp_url']) ?></p>
    <?php } ?>
</details>
