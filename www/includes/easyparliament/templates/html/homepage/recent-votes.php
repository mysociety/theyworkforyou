<?php if (count($divisions) > 0) { ?>
    <h2 style="position: relative;">
        <?= gettext('Recent Votes') ?>
        <div class="meta excerpt__category"><a href="/divisions/"><?= gettext('Show all recent votes') ?></a></div>
    </h2>
    <ul class="recently__list">
      <?php foreach ($divisions as $debate) { ?>
        <li class="parliamentary-excerpt parliamentary-excerpt--no-category">
            <h3 class="excerpt__title">
                <a href="<?= $debate['url'] ?>">
                    <?= $debate['title'] ?>
                </a>
            </h3>
            <p class="meta">
                <?php if ($debate['major'] == 1) { ?>Commons, <?php } ?>
                <?php if ($debate['major'] == 101) { ?>Lords, <?php } ?>
                <?= format_date($debate['date'], LONGERDATEFORMAT) ?>
            </p>
      </li>
      <?php } ?>
    </ul>
<?php } ?>
