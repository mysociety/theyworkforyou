<form action="<?= $actionurl ?>" method="post" class="">
  <?php if (!$email_verified) { ?>
    <p>
      <?php if (isset($errors["email"]) && $submitted) { ?>
        <span class="alert-page-error"><?= $errors["email"] ?></span>
      <?php } ?>
        <input type="email" class="form-control" placeholder="<?= gettext('Your email address') ?>" name="email" id="email" value="<?= _htmlentities($email) ?>">
    </p>
  <?php } ?>

    <p>
      <?php if ($pid) { ?>
        <input type="text" class="form-control" name="alertsearch" id="alertsearch" disabled="disabled"
            value="<?= $pid_member->full_name() ?><?php if ($pid_member->constituency()) { ?> (<?= _htmlspecialchars($pid_member->constituency()) ?>)<?php } ?>">
      <?php } elseif ($keyword) { ?>
        <input type="text" class="form-control" name="alertsearch" id="alertsearch" disabled="disabled" value="<?= _htmlspecialchars($display_keyword) ?>">
      <?php } else { ?>
        <input type="text" class="form-control" placeholder="<?= gettext('Search postcode, or MP name') ?>" name="alertsearch" id="alertsearch" value="<?= _htmlentities($search_text) ?>">
      <?php } ?>
    </p>

    <p>
        <input type="submit" class="button" value="<?= ($pid || $keyword) ? gettext('Subscribe') : gettext('Search') ?>">
        <button type="submit" class="button red" name="action" value="Abandon">
          <span><?= gettext('Abandon changes') ?></span>
          <i aria-hidden="true" class="fi-trash"></i>
        </button>
    </p>

    <input type="hidden" name="t" value="<?= _htmlspecialchars($token) ?>">
    <input type="hidden" name="submitted" value="1">

  <?php if ($pid) { ?>
    <input type="hidden" name="pid" value="<?= _htmlspecialchars($pid) ?>">
  <?php } ?>
  <?php if ($keyword) { ?>
    <input type="hidden" name="keyword" value="<?= _htmlspecialchars($keyword) ?>">
  <?php } ?>

  <?php if ($sign) { ?>
    <input type="hidden" name="sign" value="<?= _htmlspecialchars($sign) ?>">
  <?php } ?>

  <?php if ($site) { ?>
    <input type="hidden" name="site" value="<?= _htmlspecialchars($site) ?>">
  <?php } ?>
</form>