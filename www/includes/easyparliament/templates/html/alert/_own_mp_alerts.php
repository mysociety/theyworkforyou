
<?php foreach ($own_member_alerts as $alert) { ?>
<p class="alert-page-subsection--subtitle"><?= _htmlspecialchars($alert['criteria']) ?></p>
<div class="alert-page-alert-controls">
  <form action="<?= $actionurl ?>" method="POST">
    <input type="hidden" name="t" value="<?= _htmlspecialchars($alert['token']) ?>">
    <input type="hidden" name="pid" value="<?= _htmlspecialchars($alert['pid']) ?>">
    <?php if ($alert['status'] == 'unconfirmed') { ?>
      <button type="submit" class="button button--outline small" name="action" value="Confirm">
        <i aria-hidden="true" class="fi-save"></i>
        <span><?= gettext('Confirm alert') ?></span>
      </button>
    <?php } elseif ($alert['status'] == 'suspended') { ?>
    <button type="submit" class="button button--outline small" name="action" value="Resume">
      <span><?= gettext('Resume alert') ?></span>
      <i aria-hidden="true" class="fi-play"></i>
    </button>
    <?php } else { ?>
    <button type="submit" class="button button--outline small" name="action" value="Suspend">
      <i aria-hidden="true" class="fi-pause"></i>
      <span><?= gettext('Suspend alert') ?></span>
    </button>
    <button type="submit" class="button button--outline-red small" name="action" value="Delete">
      <i aria-hidden="true" class="fi-trash"></i>
      <span><?= gettext('Delete alert') ?></span>
    </button>
  </form>
  <form action="<?= $actionurl ?>" method="POST">
    <input type="hidden" name="t" value="<?= _htmlspecialchars($alert['token']) ?>">
    <?php if (count($alert['words'])) { ?>
    <input type="hidden" name="step" value="define">
    <input type="hidden" name="shown_related" value="1">
    <button type="submit" class="button button--outline small" value="Edit">
      <i aria-hidden="true" class="fi-page-edit"></i>
      <span><?= gettext('Edit alert') ?></span>
    </button>
    <?php } else { ?>
    <input type="hidden" name="mp_step" value="mp_confirm">
    <input type="hidden" name="pid" value="<?= $alert['pid'] ?>">
      <?php if ($alert['ignore_speaker_votes'] == 1) { ?>
        <input type="hidden" name="ignore_speaker_votes" value="0">
        <button type="submit" class="button button--outline small" value="Edit">
          <i aria-hidden="true" class="fi-page-edit"></i>
          <span><?= gettext('Include votes') ?></span>
        </button>
      <?php } else { ?>
        <input type="hidden" name="ignore_speaker_votes" value="1">
        <button type="submit" class="button button--outline small" value="Edit">
          <i aria-hidden="true" class="fi-page-edit"></i>
          <span><?= gettext('Ignore votes') ?></span>
        </button>
      <?php } ?>
    <?php } ?>
  </form>
    <?php } ?>
</div>
<?php } ?>

<?php if (!in_array($own_mp_criteria, $all_keywords)) { ?>
<p class="alert-page-subsection--subtitle">Alert when <?= _htmlspecialchars($own_mp_criteria) ?> is <strong>mentioned</strong></p>
<form action="<?= $actionurl ?>" method="post">
  <input type="hidden" name="step" value="confirm">
  <input type="hidden" name="words[]" value="<?= _htmlentities($own_mp_criteria) ?>">
  <button type="submit" class="button button--outline small" name="action" value="Subscribe">
    <?= gettext('Create new alert') ?>
    <i aria-hidden="true" role="img" class="fi-megaphone"></i>
  </button>
</form>
<?php } ?>
