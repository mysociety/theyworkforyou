<h3><?= gettext('Your current email alerts') ?></h3>

<ul class="alerts-manage__list">
  <?php foreach ($alerts as $alert) { ?>
    <li>
        <?= sprintf(gettext('When %s'), _htmlspecialchars($alert['criteria'])) ?>.
        <form action="<?= $actionurl ?>" method="POST">
            <input type="hidden" name="t" value="<?= _htmlspecialchars($alert['token']) ?>">
          <?php if ($alert['status'] == 'unconfirmed') { ?>
            <input type="submit" class="button small" name="action" value="<?= gettext('Confirm') ?>">
          <?php } elseif ($alert['status'] == 'suspended') { ?>
            <input type="submit" class="button small" name="action" value="<?= gettext('Resume') ?>">
          <?php } else { ?>
            <input type="submit" class="button button--secondary small" name="action" value="<?= gettext('Suspend') ?>">
            <input type="submit" class="button button--negative small" name="action" value="<?= gettext('Delete') ?>">
          <?php } ?>
        </form>
    </li>
  <?php } ?>
</ul>

<div class="clearfix">
    <form action="<?= $actionurl ?>" method="POST" class="pull-right">
        <input type="hidden" name="t" value="<?= _htmlspecialchars($alert['token']) ?>">
        <input type="submit" class="button button--negative small" name="action" value="<?= gettext('Delete All') ?>">
    </form>
</div>
