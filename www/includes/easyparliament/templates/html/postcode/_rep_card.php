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
