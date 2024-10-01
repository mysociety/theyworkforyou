<div class="full-page legacy-page static-page">
  <div class="full-page__row">
    <div class="panel">
      <div class="stripe-side">
        <div class="main">
        <?php if (isset($error)) {?>
          <h1>Sorry...</h1>
           <p>We can&rsquo;t find that user.</p>
          </div>
        </div>
        <?php } else { ?>
          <h1><?= gettext('Your details') ?></h1>
          <?php if (isset($edited)) { ?>
            <p>
              <strong>have been updated<?= isset($email_changed) && $email_changed == true ? " and we&rsquo;ve sent a confirmation email to your new email address" : '' ?>.</strong>
            </p>
          <?php } else { ?>
          <p>
            <strong>This is how other people see you.</strong>
            <?php if ($facebook_user && !$postcode) { ?>
            </p>

            <p>
                <a href="/user/?pg=edit">Update your postcode</a> so we can show you information about your representatives.
            </p>

            <?php } else { ?>
                <a href="/user/?pg=edit"><?= gettext('Edit your details') ?></a>.
            <?php } ?>
          </p>
          <?php } ?>

          <div class="row">
            <span class="label"><?= gettext('Name') ?></span>
            <span class="formw"><?= _htmlentities($name) ?></span>
          </div>

          <div class="row">
            <span class="label"><?= gettext('Website') ?></span>
            <span class="formw"><?= $website == '' ? 'none' : '<a rel="nofollow" href="' . _htmlentities($website) . '">' . _htmlentities($website) . '</a>' ?></span>
          </div>

          <?php if ($facebook_user) { ?>
          <div class="row">
            <span class="label">Facebook login</span>
            <span class="formw">Yes</span>
          </div>
          <?php } ?>

          <div class="row">
            <span class="label"><?= gettext('Status') ?></span>
            <span class="formw"><?= _htmlentities($status) ?></span>
          </div>

          <div class="row">
            <span class="label"><?= gettext('Joined') ?></span>
            <span class="formw"><?= _htmlentities($registrationtime) ?></span>
          </div>
        </div> <!-- end .main -->

        <div class="sidebar"></div>
        <div class="break"></div>
      </div>

      <div class="stripe-side">
        <div class="main">
            <?php if ($alerts) { ?>
                <?php include(dirname(__FILE__) . '/../alert/_list.php'); ?>
            <?php } else { ?>
                <p>You currently have no email alerts set up. You can create alerts <a href="/alert/">here</a>.</p>
            <?php } ?>
        </div>

        <div class="sidebar">&nbsp;</div>
        <div class="break"></div>
      </div>
        <?php } ?>
        </div>
    </div>
</div>
