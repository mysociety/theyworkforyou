<a href="<?= $item['list_url'] ?>" class="business-list__title">
    <h3>
      <?php if (isset($item['parent'])) { ?>
        <?= $item['parent']['body'] ?>
        <br><?= $item['body'] ?>
      <?php } else { ?>
        <?= $item['body'] ?>
      <?php } ?>
    </h3>
    <span class="business-list__meta">
      <?= format_date($item['hdate'], LONGERDATEFORMAT) ?>
      <?=  isset($item['contentcount']) ? '&middot; ' . sprintf(ngettext('%s speech', '%s speeches', $item['contentcount']), $item['contentcount']) : '' ?>
    </span>
</a>
<?php if (isset($item['child'])) { ?>
<p class="business-list__excerpt">
  <?php if (isset($item['child']['speaker']) && count($item['child']['speaker']) > 0) { ?>
    <a href="<?= $item['child']['speaker']['url'] ?>"><?= $item['child']['speaker']['name'] ?></a>
  <?php } ?>
  <?= strip_tags(trim_characters($item['child']['body'], 0, 200)) ?>
</p>
<?php } ?>
