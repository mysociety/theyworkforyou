<?php if (isset($constituencies) && count($constituencies) > 0) {
    $member_options = true; ?>
  <h3><?= sprintf(gettext('Sign up for alerts when Representatives for constituencies matching <i>%s</i> speaks'), _htmlspecialchars($search_term)) ?></h3>
  <ul>
    <?php foreach ($constituencies as $constituency => $member) { ?>
      <li>
          <form action="<?= $actionurl ?>" method="post">
              <input type="hidden" name="t" value="<?= _htmlspecialchars($token) ?>">
              <input type="hidden" name="email" value="<?= _htmlspecialchars($email) ?>">
              <input type="hidden" name="pid" value="<?= $member->person_id() ?>">
              <input type="hidden" name="ignore_speaker_votes" value="<?= $ignore_speaker_votes ?>">
              <?= $member->full_name() ?> <?= _htmlspecialchars($member->getMostRecentMembership()['rep_name']) ?>
              (<?= _htmlspecialchars($constituency) ?>)
              <input type="submit" class="button small" value="<?= gettext('Subscribe') ?>"></form>
          </li>
    <?php } ?>
  </ul>
<?php } elseif ($members) {
    $member_options = true; ?>
  <h3><?= sprintf(gettext('Sign up for alerts when people matching <i>%s</i> speaks'), _htmlspecialchars($search_term)) ?></h3>
  <ul>
    <?php
      foreach ($members as $row) {
          ?>
      <li>
          <form action="<?= $actionurl ?>" method="post">
              <input type="hidden" name="t" value="<?= _htmlspecialchars($token) ?>">
              <input type="hidden" name="email" value="<?= _htmlspecialchars($email) ?>">
              <input type="hidden" name="pid" value="<?= $row['person_id'] ?>">
              <input type="hidden" name="ignore_speaker_votes" value="<?= $ignore_speaker_votes ?>">
              <?php
                    $name = member_full_name($row['house'], $row['title'], $row['given_name'], $row['family_name'], $row['lordofname']);
          if ($row['constituency']) {
              $name .= ' (' . gettext($row['constituency']) . ')';
          }
          printf(gettext('When %s speaks.'), $name);
          ?>
              <input type="submit" class="button small" value="<?= gettext('Subscribe') ?>"></form>
          </form>
      </li>
    <?php } ?>
  </ul>
  <hr />
<?php } else { ?>
  <form action="<?= $actionurl ?>" method="post" class="alerts-form">
    <?php if (!$email_verified) { ?>
      <p>
        <?php if (isset($errors["email"]) && $submitted) { ?>
          <span class="alert-page-error"><?= $errors["email"] ?></span>
        <?php } ?>
          <input type="email" class="form-control" placeholder="<?= gettext('Your email address') ?>" name="email" id="email" value="<?= _htmlentities($email) ?>">
      </p>
    <?php } ?>

    <?php if ($token) { ?>
        <input type="hidden" name="t" value="<?= _htmlspecialchars($token) ?>">
    <?php } ?>

      <p>
        <?php if ($pid) { ?>
          <input type="text" class="form-control" name="mp_search" id="mp_search" disabled="disabled"
              value="<?= $pid_member->full_name() ?><?php if ($pid_member->constituency()) { ?> (<?= _htmlspecialchars($pid_member->constituency()) ?>)<?php } ?>">
        <?php } elseif ($keyword) { ?>
          <input type="text" class="form-control" name="mp_search" id="mp_search" value="<?= _htmlspecialchars($keyword) ?>">
        <?php } else { ?>
          <label for="mp-postcode">Search postcode, or MP name</label>
          <input id="mp-postcode" type="text" class="form-control" placeholder="<?= gettext('e.g. ‘B2 4QA’ or ‘John Doe’') ?>" name="mp_search" id="mp_search" value="<?= _htmlentities($search_text) ?>" style="min-width:300px;">
        <?php } ?>
      </p>

      <div class="alert-form__section">
        <div class="checkbox-wrapper">
          <input type="checkbox" id="ignore_speaker_votes" name="ignore_speaker_votes"<?= $ignore_speaker_votes ? ' checked' : ''?>>
          <label for="ignore_speaker_votes"><?= gettext('Do not include votes in the alert') ?></label>
        </div>
      </div>

      <p>
          <?php if ($pid || $keyword) { ?>
          <button type="submit" class="button" name="mp_step" value="mp_confirm">
            <i aria-hidden="true" class="fi-megaphone"></i>
            <span><?= gettext('Subscribe') ?></span>
          </button>
          <?php } else { ?>
          <button type="submit" class="button" name="mp_step" value="mp_search">
            <span><?= gettext('Search') ?></span>
            <i aria-hidden="true" class="fi-magnifying-glass"></i>
          </button>
          <?php } ?>
          <button type="submit" class="button button--red" name="action" value="Abandon">
            <i aria-hidden="true" class="fi-trash"></i>
            <?php if ($token) { ?>
              <span><?= gettext('Abandon changes') ?></span>
            <?php } else { ?>
              <span><?= gettext('Abandon create') ?></span>
            <?php } ?>
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
<?php } ?>
