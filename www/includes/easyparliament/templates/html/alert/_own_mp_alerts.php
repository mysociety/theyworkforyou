
<?php foreach ($own_member_alerts as $alert) { ?>
  <p class="alert-form__subtitle"><?= _htmlspecialchars($alert['criteria']) ?></p>
  <?php include "_alert_controls.php" ?>
<?php } ?>

<?php if (!in_array($own_mp_criteria, $all_keywords)) { ?>
<p class="alert-form__subtitle">Alert when <?= _htmlspecialchars($own_mp_criteria) ?> is <strong>mentioned</strong></p>
<form action="<?= $actionurl ?>" method="post">
  <input type="hidden" name="step" value="confirm">
  <input type="hidden" name="words[]" value="<?= _htmlentities($own_mp_criteria) ?>">
  <button type="submit" class="button button--outline small" name="action" value="Subscribe">
    <i aria-hidden="true" role="img" class="fi-megaphone"></i>
    <?= gettext('Create new alert') ?>
  </button>
</form>
<?php } ?>
