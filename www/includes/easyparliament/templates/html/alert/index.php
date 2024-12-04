<div class="full-page">
    <div class="full-page__row">

      <?php if ($message) { ?>
        <div class="alert-section alert-section--feedback">
            <div class="alert-section__primary">
                <h3><?= $message['title'] ?></h3>
                <p class="alert-results">
                    <?= $message['text'] ?>
                </p>
            </div>
        </div>
      <?php } ?>

      <?php if ($results) { ?>
        <div class="alert-section alert-section--feedback">
            <div class="alert-section__primary">
              <?php if ($results == 'alert-confirmed') { ?>
                <h3><?= gettext('Your alert has been confirmed') ?></h3>
                <p>
                    <?= gettext('You will now receive email alerts for the following criteria:') ?>
                </p>
                <ul><?= _htmlspecialchars($display_criteria) ?></ul>
                <p>
                    <?= gettext('This is normally the day after, but could conceivably be later due to issues at our or the parliament’s end.') ?>
                </p>

                <!-- Google Code for TWFY Alert Signup Conversion Page -->
                <script type="text/javascript">
                /* <![CDATA[ */
                var google_conversion_id = 1067468161;
                var google_conversion_language = "en";
                var google_conversion_format = "3";
                var google_conversion_color = "ffffff";
                var google_conversion_label = "HEeDCOvPjmQQgYuB_QM";
                var google_remarketing_only = false;
                /* ]]> */
                </script>
                <script type="text/javascript" src="//www.googleadservices.com/pagead/conversion.js">
                </script>
                <noscript>
                <div style="display:inline;">
                <img height="1" width="1" style="border-style:none;" alt="" src="//www.googleadservices.com/pagead/conversion/1067468161/?label=HEeDCOvPjmQQgYuB_QM&amp;guid=ON&amp;script=0"/>
                </div>
                </noscript>

              <?php } elseif ($results == 'alert-suspended') { ?>
                <h3><?= gettext('Alert suspended') ?></h3>
                <p>
                    <?= gettext('You can reactivate the alert at any time, from the sidebar below.') ?>
                </p>

              <?php } elseif ($results == 'alert-resumed') { ?>
                <h3><?= gettext('Alert resumed') ?></h3>
                <p>
                    <?= gettext('You will now receive email alerts on any day when there are entries in Hansard that match your criteria.') ?>
                </p>

              <?php } elseif ($results == 'alert-deleted') { ?>
                <h3><?= gettext('Alert deleted') ?></h3>
                <p>
                    <?= gettext('You will no longer receive this alert.') ?>
                </p>

              <?php } elseif ($results == 'all-alerts-deleted') { ?>
                <h3><?= gettext('All alerts deleted') ?></h3>
                <p>
                    <?= gettext('You will no longer receive any alerts.') ?>
                </p>

              <?php } elseif ($results == 'alert-fail') { ?>
                <h3><?= gettext('Hmmm, something’s not right') ?></h3>
                <p>
                    <?= gettext('The link you followed to reach this page appears to be incomplete.') ?>
                </p>
                <p>
                    <?= gettext('If you clicked a link in your alert email you may need to manually copy and paste the entire link to the ‘Location’ bar of the web browser and try again.') ?>
                </p>
                <p>
                    <?= sprintf(gettext('If you still get this message, please do <a href="mailto:%s">email us</a> and let us know, and we’ll help out!'), str_replace('@', '&#64;', CONTACTEMAIL)) ?>
                </p>

              <?php } elseif ($results == 'alert-added') { ?>
                <h3><?= gettext('Your alert has been added') ?></h3>
                <p>
                    <?= sprintf(gettext('You will now receive email alerts on any day when %s in parliament.'), _htmlspecialchars($display_criteria)) ?>
                </p>

              <?php } elseif ($results == 'alert-confirmation') { ?>
                <h3><?= gettext('We’re nearly done…') ?></h3>
                <p>
                    <?= gettext('You should receive an email shortly which will contain a link. You will need to follow that link to confirm your email address and receive future alerts. Thanks.') ?>
                </p>

              <?php } elseif ($results == 'alert-exists') { ?>
                <h3><?= gettext('You’re already subscribed to that!') ?></h3>
                <p>
                    <?= gettext('It’s good to know you’re keen though.') ?>
                </p>

              <?php } elseif ($results == 'alert-already-signed') { ?>
                <h3><?= gettext('We’re nearly done') ?></h3>
                <p>
                    <?= gettext('You should receive an email shortly which will contain a link. You will need to follow that link to confirm your email address and receive future alerts. Thanks.') ?>
                </p>

              <?php } elseif ($results == 'changes-abandoned') { ?>
                <h3><?= gettext('Changes abandoned') ?></h3>
                <p>
                    <?= gettext('Those changes have been abandoned and your alerts are unchanged.') ?>
                </p>
              <?php } elseif ($results == 'alert-fail') { ?>
                <h3><?= gettext('Alert could not be created') ?></h3>
                <p>
                    <?= sprintf(gettext('Sorry, we were unable to create that alert. Please <a href="mailto:%s">let us know</a>. Thanks.'), str_replace('@', '&#64;', CONTACTEMAIL)) ?>
                </p>

              <?php } ?>
            </div>
        </div>
      <?php } ?>

      <?php if ($mp_step) { ?>
        <div class="alert-section">
            <div class="alert-section__primary">
              <h1><?= gettext("Create an MP Alert") ?></h1>
              <?php include '_mp_alert_form.php' ?>
              </div>
          </div>
      <?php } elseif ($step !== '') { ?>
        <?php include '_alert_form.php';
      } elseif ($this_step == '') {
          if(
              !$results && (
                $members ||
                (isset($constituencies) && count($constituencies) > 0) ||
                ($alertsearch)
              )
          ) {
              /* We need to disambiguate the user's instructions */
              $member_options = false;
              ?>
        <div class="alert-section alert-section--disambiguation">
            <div class="alert-section__primary">

              <?php if ($members) {
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
              <?php } ?>

              <?php if (isset($constituencies) && count($constituencies) > 0) {
                  $member_options = true; ?>
                <h3><?= sprintf(gettext('Sign up for alerts when MPs for constituencies matching <i>%s</i> speaks'), _htmlspecialchars($search_term)) ?></h3>
                <ul>
                <?php foreach ($constituencies as $constituency => $member) { ?>
                    <li>
                        <form action="<?= $actionurl ?>" method="post">
                            <input type="hidden" name="t" value="<?= _htmlspecialchars($token) ?>">
                            <input type="hidden" name="email" value="<?= _htmlspecialchars($email) ?>">
                            <input type="hidden" name="pid" value="<?= $member->person_id() ?>">
                            <?= $member->full_name() ?>
                            (<?= _htmlspecialchars($constituency) ?>)
                            <input type="submit" class="button small" value="<?= gettext('Subscribe') ?>"></form>
                        </li>
                <?php } ?>
                </ul>
              <?php } ?>

              <?php if ($alertsearch) {
                  if (!$member_options) { ?>
                <h3><?= gettext('That doesn’t match a person, postcode or constituency. Search again to refine your email alert.') ?></h3>
                <ul>
                    <?php if (isset($mistakes['postcode_and'])) { ?>
                    <li>
                        <em class="error"><?= gettext('
                            You have used a postcode and something else in your
                            search term – are you sure this is what you
                            want? You will only get an alert if all of these
                            are mentioned in the same debate.') ?>
                          <?php if (isset($member_alertsearch)) {
                              printf(gettext('Did you mean to get alerts for when your representative mentions something instead? If so maybe you want to subscribe to…'));
                          } ?>
                        </em>
                    </li>
                    <?php } ?>
                  <?php if (isset($member_alertsearch)) { ?>
                    <li>
                        <form action="<?= $actionurl ?>" method="post">
                            <input type="hidden" name="t" value="<?= _htmlspecialchars($token) ?>">
                            <input type="hidden" name="email" value="<?= _htmlspecialchars($email) ?>">
                            <input type="hidden" name="step" value="define">
                            <input type="hidden" name="words[]" value="<?= _htmlspecialchars($member_displaysearch) ?>">
                            <input type="hidden" name="representative" value="<?= $member->full_name() ?>">
                            <?= sprintf(gettext('Mentions of [%s] by your MP, %s'), _htmlspecialchars($member_displaysearch), $member->full_name()) ?>
                            <input type="submit" class="button small" value="<?= gettext('Create alert') ?>">
                        </form>
                    </li>
                  <?php } ?>

                  <?php if (isset($scottish_alertsearch)) { ?>
                    <li>
                        <form action="<?= $actionurl ?>" method="post">
                            <input type="hidden" name="t" value="<?= _htmlspecialchars($token) ?>">
                            <input type="hidden" name="email" valu
                            <input type="hidden" name="step" value="define">
                            <input type="hidden" name="words[]" value="<?= _htmlspecialchars($member_displaysearch) ?>">
                            <input type="hidden" name="representative" value="<?= $member->full_name() ?>">
                            <?= sprintf(gettext('Mentions of [%s] by your MSP, %s'), _htmlspecialchars($member_displaysearch), $scottish_member->full_name()) ?>
                            <input type="submit" class="button small" value="<?= gettext('Create alert') ?>">
                        </form>
                    </li>
                  <?php } ?>

                  <?php if (isset($welsh_alertsearch)) { ?>
                    <li>
                        <form action="<?= $actionurl ?>" method="post">
                            <input type="hidden" name="t" value="<?= _htmlspecialchars($token) ?>">
                            <input type="hidden" name="email" value="<?= _htmlspecialchars($email) ?>">
                            <input type="hidden" name="step" value="define">
                            <input type="hidden" name="words[]" value="<?= _htmlspecialchars($member_displaysearch) ?>">
                            <input type="hidden" name="representative" value="<?= $member->full_name() ?>">
                            <?= sprintf(gettext('Mentions of [%s] by your MS, %s'), _htmlspecialchars($member_displaysearch), $welsh_member->full_name()) ?>
                            <input type="submit" class="button small" value="<?= gettext('Create alert') ?>">
                        </form>
                    </li>
                  <?php } ?>
                </ul>
                <?php } else { ?>
                <h3><?= gettext('Do you want alerts for a word or phrase?') ?></h3>
                <ul>
                    <li>
                        <form action="<?= $actionurl ?>" method="post">
                            <input type="hidden" name="t" value="<?= _htmlspecialchars($token) ?>">
                            <input type="hidden" name="email" value="<?= _htmlspecialchars($email) ?>">
                            <input type="hidden" name="step" value="define">
                            <input type="hidden" name="words[]" value="<?= _htmlspecialchars($alertsearch) ?>">
                            <?= sprintf(gettext('Receive alerts when %s'), _htmlspecialchars($alertsearch_pretty)) ?>
                            <input type="submit" class="button small" value="<?= gettext('Create alert') ?>">
                        </form>
                    </li>
                </ul>
              <h3><?= gettext('Not quite right? Search again to refine your email alert.') ?></h3>
                <?php } ?>
              <?php } ?>
              <?php include '_mp_alert_form.php' ?>
            </div>
        </div>
        <?php } else { ?>

        <div class="alert-section">
            <div class="alert-section__primary">
              <?php if (!$email_verified) { ?>
                <p>
                    <?= sprintf(gettext('If you <a href="%s">join</a> or <a href="%s">sign in</a>, you can suspend, resume and delete your email alerts from your profile page.'), '/user/?pg=join', '/user/login/?ret=%2Falert%2F') ?>
                </p>
                <p>
                    <?= gettext('Plus, you won’t need to confirm your email address for every alert you set.') ?>
                </p>
              <div class="alert-page-header">
                <h3>Create an alert for a phrase or keyword</h3>
                <form action="<?= $actionurl ?>" method="post">
                    <input type="hidden" name="step" value="define">
                    <button type="submit" class="button small" value="<?= gettext('Create new keyword alert') ?>">
                      <span><?= gettext('Create new keyword alert') ?></span>
                      <i aria-hidden="true" class="fi-megaphone"></i>
                    </button>
                </form>

              </div>

              <h3>or</h3>

              <div class="alert-page-header">
                <h3>Create an alert when an MP speaks</h3>
                <form action="<?= $actionurl ?>" method="post">
                  <input type="hidden" name="mp_step" value="mp_alert">
                  <button type="submit" class="button small">
                    <?= gettext('Create new MP alert') ?>
                    <i aria-hidden="true" role="img" class="fi-megaphone"></i>
                  </button>
                </form>
              </div>
              <?php } else { ?>

              <div class="alert-page-header">
                <div>
                  <h2><?= gettext('Keywords alerts') ?></h2>
                  <!-- Go to Create alert page -->
                  <?php if (!$alerts) { ?>
                    <p><?= gettext('You haven´t created any keyword alerts.') ?></p>
                  <?php } ?>
                </div>
                <div class="alert-page-header__button-group">
                  <form action="<?= $actionurl ?>" method="POST" class="pull-right">
                      <input type="hidden" name="t" value="< ?= _htmlspecialchars($alert['token']) ?>">
                      <input type="submit" class="button button--negative small" name="action" value="<?= gettext('Delete All') ?>">
                  </form>
                  <form action="<?= $actionurl ?>" method="post">
                      <input type="hidden" name="step" value="define">
                      <button type="submit" class="button small" value="<?= gettext('Create new keyword alert') ?>">
                        <span><?= gettext('Create new keyword alert') ?></span>
                        <i aria-hidden="true" class="fi-megaphone"></i>
                      </button>
                  </form>
                </div>
              </div>

                <!-- The groups alerts should be sorted by default from most recent mention to oldest one -->
                <!-- Future functionality: The groups alerts can be sorted alphabetically-->

                <?php include '_list_accordian.php'; ?>
              <?php } ?>

          </div>
        </div>
        <?php } ?>
        <?php } ?>
    </div>
</div>
