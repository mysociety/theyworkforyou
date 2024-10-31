
<?php foreach ($own_member_alerts as $alert) { ?>
<p class="alert-page-subsection--subtitle"><?= _htmlspecialchars($alert['criteria']) ?></p>
<div class="alert-page-alert-controls">
  <form action="<?= $actionurl ?>" method="POST">
    <input type="hidden" name="t" value="<?= _htmlspecialchars($alert['token']) ?>">
    <?php if ($alert['status'] == 'unconfirmed') { ?>
      <button type="submit" class="button small" name="action" value="Confirm">
        <span><?= gettext('Confirm alert') ?></span>
        <i aria-hidden="true" class="fi-save"></i>
      </button>
    <?php } elseif ($alert['status'] == 'suspended') { ?>
    <button type="submit" class="button small" name="action" value="Resume">
      <span><?= gettext('Resume alert') ?></span>
      <i aria-hidden="true" class="fi-play"></i>
    </button>
    <?php } else { ?>
    <button type="submit" class="button small" name="action" value="Suspend">
      <span><?= gettext('Suspend alert') ?></span>
      <i aria-hidden="true" class="fi-pause"></i>
    </button>
    <button type="submit" class="button small red" name="action" value="Delete">
      <span><?= gettext('Delete alert') ?></span>
      <i aria-hidden="true" class="fi-trash"></i>
    </button>
  </form>
  <form action="<?= $actionurl ?>" method="POST">
    <input type="hidden" name="step" value="define">
    <input type="hidden" name="shown_related" value="1">
    <input type="hidden" name="t" value="<?= _htmlspecialchars($alert['token']) ?>">
    <button type="submit" class="button small" value="Edit">
      <span><?= gettext('Edit alert') ?></span>
      <i aria-hidden="true" class="fi-page-edit"></i>
    </button>
  </form>
    <?php } ?>
</div>
<?php } ?>

<?php if (!in_array(implode('', $own_member_alerts[0]['spokenby']), $all_keywords)) { ?>
<p class="alert-page-subsection--subtitle">Alert when <?= _htmlspecialchars(implode('', $own_member_alerts[0]['spokenby'])) ?> is <strong>mentioned</strong></p>
<form action="<?= $actionurl ?>" method="post">
  <input type="hidden" name="keyword" value="<?= _htmlentities(implode('', $own_member_alerts[0]['spokenby'])) ?>">
  <button type="submit" class="button small" name="action" value="Subscribe">
    <?= gettext('Create new alert') ?>
    <i aria-hidden="true" role="img" class="fi-megaphone"></i>
  </button>
</form>
<?php } ?>
